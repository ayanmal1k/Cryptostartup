#!/usr/bin/env bash
#
# Route ONLY the deploy's SSH/SFTP traffic through Cloudflare WARP (free WireGuard VPN).
# Sourced by deploy.sh when run with --vpn (or XF_VPN=1).
#
# HOW
#   wgcf       registers a free WARP account and writes a WireGuard profile.
#              https://github.com/ViRb3/wgcf
#   wireproxy  runs that WireGuard tunnel entirely in userspace and exposes it as a local
#              SOCKS5 proxy — no admin rights, no network adapter, no system routes, so the
#              rest of the machine never touches the VPN.
#              https://github.com/windtf/wireproxy (formerly pufferffish/, whyvl/)
#   connect    (ships with Git for Windows) is ssh's ProxyCommand into that SOCKS5 port.
#
# WHY
#   The host's firewall bans an IP after bursts of SSH connections (see deploy.sh). If this
#   machine's IP is banned, the deploy can still go out through a WARP exit IP.
#
# Binaries are pinned and SHA-256 verified against the digests GitHub publishes for each
# release asset. Everything lives in $XF_VPN_DIR, outside the repo, because
# wgcf-account.toml and wgcf-profile.conf contain the WireGuard private key.
#

XF_VPN_DIR="${XF_VPN_DIR:-$HOME/.xf-deploy/vpn}"
XF_VPN_SOCKS="${XF_VPN_SOCKS:-127.0.0.1:25344}"

WGCF_URL="https://github.com/ViRb3/wgcf/releases/download/v2.2.32/wgcf_2.2.32_windows_amd64.exe"
WGCF_SHA256="2b3648a5d39550b6423be562e619805ed9f7a64bcda51cf36c60caeba97b1777"
WIREPROXY_URL="https://github.com/windtf/wireproxy/releases/download/v1.1.3/wireproxy_windows_amd64.tar.gz"
WIREPROXY_SHA256="bce041ea9fe0f8a3351301dcbe29cdf6a523bb25cf9c62f17ebb5699a8051d0f"

XF_VPN_PID=""

vpn_log() { printf '  [vpn] %s\n' "$*"; }

# Download $1 to $2 and refuse it unless its SHA-256 is exactly $3.
vpn_fetch() {
    local url="$1" dest="$2" sha="$3"
    curl -fsSL -m 180 -o "$dest.part" "$url" || { rm -f "$dest.part"; vpn_log "download failed: $url"; return 1; }
    if ! echo "$sha  $dest.part" | sha256sum -c --quiet - >/dev/null 2>&1; then
        rm -f "$dest.part"
        vpn_log "checksum mismatch for $url — refusing to use it"
        return 1
    fi
    mv "$dest.part" "$dest"
}

# One-time setup; every step is skipped once its output exists.
xf_vpn_setup() {
    mkdir -p "$XF_VPN_DIR" && chmod 700 "$XF_VPN_DIR"
    (
        cd "$XF_VPN_DIR" || exit 1

        if [ ! -f wgcf.exe ]; then
            vpn_log "downloading wgcf"
            vpn_fetch "$WGCF_URL" wgcf.exe "$WGCF_SHA256" || exit 1
        fi

        if [ ! -f wireproxy.exe ]; then
            vpn_log "downloading wireproxy"
            vpn_fetch "$WIREPROXY_URL" wireproxy.tar.gz "$WIREPROXY_SHA256" || exit 1
            tar -xzf wireproxy.tar.gz wireproxy.exe && rm -f wireproxy.tar.gz || exit 1
        fi

        if [ ! -f wgcf-account.toml ]; then
            vpn_log "registering a free Cloudflare WARP account"
            ./wgcf.exe register --accept-tos >/dev/null 2>&1 || { vpn_log "wgcf register failed"; exit 1; }
        fi

        if [ ! -f wgcf-profile.conf ]; then
            ./wgcf.exe generate >/dev/null 2>&1 || { vpn_log "wgcf generate failed"; exit 1; }
        fi

        # wireproxy.exe is a native Windows program, so it needs a Windows path.
        printf 'WGConfig = %s\n\n[Socks5]\nBindAddress = %s\n' \
            "$(cygpath -w "$XF_VPN_DIR/wgcf-profile.conf")" "$XF_VPN_SOCKS" > wireproxy.conf
    )
}

xf_vpn_start() {
    command -v connect >/dev/null 2>&1 || { vpn_log "'connect' not found (it ships with Git for Windows)"; return 1; }
    xf_vpn_setup || return 1

    local host="${XF_VPN_SOCKS%:*}" port="${XF_VPN_SOCKS##*:}"
    if (echo > "/dev/tcp/$host/$port") 2>/dev/null; then
        vpn_log "port $port is already in use — set XF_VPN_SOCKS to a free host:port"
        return 1
    fi

    "$XF_VPN_DIR/wireproxy.exe" -c "$(cygpath -w "$XF_VPN_DIR/wireproxy.conf")" \
        > "$XF_VPN_DIR/wireproxy.log" 2>&1 &
    XF_VPN_PID=$!

    local i
    for i in $(seq 1 40); do
        (echo > "/dev/tcp/$host/$port") 2>/dev/null && break
        kill -0 "$XF_VPN_PID" 2>/dev/null || { vpn_log "wireproxy exited — see $XF_VPN_DIR/wireproxy.log"; return 1; }
        sleep 0.5
    done
    (echo > "/dev/tcp/$host/$port") 2>/dev/null || { vpn_log "SOCKS proxy never came up"; xf_vpn_stop; return 1; }

    # Prove traffic really leaves through the tunnel before trusting it with the deploy.
    local exit_ip
    exit_ip=$(curl -s -m 30 --socks5-hostname "$XF_VPN_SOCKS" https://api.ipify.org) || exit_ip=""
    [ -n "$exit_ip" ] || { vpn_log "tunnel is up but carries no traffic"; xf_vpn_stop; return 1; }
    vpn_log "connected — deploy traffic exits via $exit_ip"

    XF_VPN_PROXY_COMMAND="connect -S $XF_VPN_SOCKS %h %p"
}

xf_vpn_stop() {
    [ -n "$XF_VPN_PID" ] || return 0
    # kill handles the MSYS wrapper; taskkill makes sure the native process is gone too.
    local winpid
    winpid=$(cat "/proc/$XF_VPN_PID/winpid" 2>/dev/null || true)
    kill "$XF_VPN_PID" 2>/dev/null || true
    [ -n "$winpid" ] && taskkill //F //PID "$winpid" >/dev/null 2>&1 || true
    XF_VPN_PID=""
    vpn_log "disconnected"
}

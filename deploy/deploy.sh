#!/usr/bin/env bash
#
# Deploy changed files to xtrafreaky.com in as few SSH connections as possible.
#
# WHY THIS EXISTS
# ---------------
# The host (GoDaddy cPanel) runs cPHulk/CSF, which auto-bans an IP after a burst of
# SSH/SFTP connections. A deploy that opened a separate connection per step — backup,
# upload, checksum, artisan, verify — tripped that ban and locked us out of ports
# 22/80/443/2083 entirely, from the same IP, while the site stayed up for everyone else.
#
# This script keeps the whole deploy to ONE ssh + ONE sftp session (and a single
# multiplexed connection when the local ssh supports ControlMaster). Never go back to
# one-connection-per-command.
#
# USAGE
#   XF_PASSPHRASE='...' ./deploy/deploy.sh <git-ref>     # deploy files changed since <git-ref>
#   XF_PASSPHRASE='...' ./deploy/deploy.sh --files a b c # deploy an explicit list
#   XF_PASSPHRASE='...' ./deploy/deploy.sh --dry-run <ref>
#
# The passphrase is read from the environment and never written to disk.
#
set -euo pipefail

HOST="wao3fxpfdect@132.148.176.103"
KEY="${XF_KEY:-$HOME/.ssh/new_ssh_key}"
REMOTE_APP="/home/wao3fxpfdect/laravel"
REMOTE_PUB="/home/wao3fxpfdect/public_html"
TMP="${TMPDIR:-/tmp}/xf-deploy-$$"
DRY_RUN=0

log()  { printf '  %s\n' "$*"; }
fail() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }

# --- Collect the file list ---------------------------------------------------------
if [ "${1:-}" = "--dry-run" ]; then DRY_RUN=1; shift; fi

declare -a DELETED=()

if [ "${1:-}" = "--files" ]; then
    shift
    FILES=("$@")
else
    REF="${1:-HEAD~1}"
    mapfile -t FILES   < <(git diff --name-only --diff-filter=ACMR "$REF"..HEAD)
    # Deletions need to be mirrored too. A file removed from the repo but left on the
    # server stays reachable if it sits in public_html (which is served directly, routes
    # or no routes) — that is exactly how a deleted dev page keeps answering 200.
    mapfile -t DELETED < <(git diff --name-only --diff-filter=D "$REF"..HEAD)
fi

[ "${#FILES[@]}" -gt 0 ] || [ "${#DELETED[@]}" -gt 0 ] || fail "no changed files to deploy"

# --- Map local paths to remote paths ------------------------------------------------
# public/*  ->  public_html/*   (public_html IS Laravel's public dir on this host)
# everything else -> laravel/*
declare -a UPLOADS=()
declare -a SKIPPED=()
declare -a REMOVALS=()
NEEDS_MIGRATE=0
NEEDS_VIEW_CLEAR=0
NEEDS_ROUTE_CLEAR=0
NEEDS_CONFIG_CLEAR=0

remote_path_for() {
    case "$1" in
        public/*) printf '%s/%s' "$REMOTE_PUB" "${1#public/}" ;;
        *)        printf '%s/%s' "$REMOTE_APP" "$1" ;;
    esac
}

for f in ${FILES[@]+"${FILES[@]}"}; do
    [ -f "$f" ] || { SKIPPED+=("$f (missing locally)"); continue; }

    remote=$(remote_path_for "$f")

    case "$f" in
        database/migrations/*) NEEDS_MIGRATE=1 ;;
    esac

    case "$f" in
        resources/views/*) NEEDS_VIEW_CLEAR=1 ;;
    esac

    # This app registers closure routes, so `route:cache` cannot be used and the route
    # file is read per request — but clear anyway, in case a cache was ever written by
    # hand. Cheap, and the alternative is a route change that silently does nothing.
    case "$f" in
        routes/*.php) NEEDS_ROUTE_CLEAR=1 ;;
    esac

    # Same reasoning for config: where bootstrap/cache/config.php exists, editing a
    # file under config/ changes nothing at all until the cache is cleared.
    case "$f" in
        config/*.php) NEEDS_CONFIG_CLEAR=1 ;;
    esac

    UPLOADS+=("$f|$remote")
done

for f in ${DELETED[@]+"${DELETED[@]}"}; do
    REMOVALS+=("$(remote_path_for "$f")")
    case "$f" in
        resources/views/*) NEEDS_VIEW_CLEAR=1 ;;
        routes/*.php)      NEEDS_ROUTE_CLEAR=1 ;;
        config/*.php)      NEEDS_CONFIG_CLEAR=1 ;;
    esac
done

echo "Deploying ${#UPLOADS[@]} file(s) to $HOST"
for u in ${UPLOADS[@]+"${UPLOADS[@]}"}; do log "${u%%|*}  ->  ${u##*|}"; done
if [ "${#REMOVALS[@]}" -gt 0 ]; then
    echo "Removing ${#REMOVALS[@]} file(s) on the server:"
    for r in "${REMOVALS[@]}"; do log "rm $r"; done
fi
[ "${#SKIPPED[@]}" -eq 0 ] || { echo "Skipped:"; for s in "${SKIPPED[@]}"; do log "$s"; done; }
[ "$NEEDS_MIGRATE" -eq 1 ]     && log "(migrations included — will run migrate --force)"
[ "$NEEDS_VIEW_CLEAR" -eq 1 ]  && log "(views included — will clear compiled views)"
[ "$NEEDS_ROUTE_CLEAR" -eq 1 ] && log "(routes changed — will clear the route cache)"
[ "$NEEDS_CONFIG_CLEAR" -eq 1 ] && log "(config changed — will clear the config cache)"

if [ "$DRY_RUN" -eq 1 ]; then echo "dry run — nothing sent"; exit 0; fi

# --- Load the key once --------------------------------------------------------------
[ -f "$KEY" ] || fail "ssh key not found at $KEY"
[ -n "${XF_PASSPHRASE:-}" ] || fail "set XF_PASSPHRASE (not stored on disk)"

mkdir -p "$TMP"
chmod 700 "$TMP"
cleanup() {
    ssh -O exit -o ControlPath="$TMP/cm" "$HOST" 2>/dev/null || true
    [ -n "${SSH_AGENT_PID:-}" ] && kill "$SSH_AGENT_PID" 2>/dev/null || true
    rm -rf "$TMP"
}
trap cleanup EXIT

printf '#!/bin/sh\nprintf "%%s\\n" "$XF_PASSPHRASE"\n' > "$TMP/askpass"
chmod 700 "$TMP/askpass"
eval "$(ssh-agent -s)" > /dev/null
DISPLAY=:0 SSH_ASKPASS="$TMP/askpass" SSH_ASKPASS_REQUIRE=force ssh-add "$KEY" 2>/dev/null \
    || fail "could not add key (wrong passphrase?)"
rm -f "$TMP/askpass"

# The protection that matters is batching: ONE sftp session + ONE ssh session, instead of
# a connection per command. ControlMaster would collapse those two into one, but MSYS2/Git
# Bash on Windows fails to create the control socket ("Failed to connect to new control
# master"), so it is opt-in via XF_MUX=1 rather than on by default.
SSH_MUX=(-o BatchMode=yes -o ConnectTimeout=30)
if [ "${XF_MUX:-0}" = "1" ]; then
    SSH_MUX+=(-o ControlMaster=auto -o ControlPath="$TMP/cm" -o ControlPersist=120)
fi

# --- Upload: ONE sftp batch ---------------------------------------------------------
if [ "${#UPLOADS[@]}" -gt 0 ]; then
    : > "$TMP/batch"

    # sftp's mkdir is not recursive and fails on existing dirs, so emit every ancestor of every
    # destination with a leading '-' (ignore errors). Without this, a file in a directory that
    # does not exist remotely — e.g. the first deploy of deploy/ — aborts the whole batch.
    {
        for u in "${UPLOADS[@]}"; do
            dir=$(dirname "${u##*|}")
            path=""
            IFS='/' read -ra parts <<< "${dir#/}"
            for part in "${parts[@]}"; do
                path="$path/$part"
                printf '%s\n' "$path"
            done
        done
    } | awk '!seen[$0]++' | while read -r d; do
        printf -- '-mkdir %s\n' "$d" >> "$TMP/batch"
    done

    for u in "${UPLOADS[@]}"; do
        printf 'put %s %s\n' "${u%%|*}" "${u##*|}" >> "$TMP/batch"
    done
    echo "bye" >> "$TMP/batch"

    echo "Uploading (single sftp session)..."
    sftp "${SSH_MUX[@]}" -b "$TMP/batch" "$HOST" > /dev/null || fail "upload failed"
    log "uploaded ${#UPLOADS[@]} file(s)"
fi

# --- Remote work: ONE ssh session ---------------------------------------------------
REMOTE_SCRIPT="set -e
cd $REMOTE_APP
echo '--- syntax check ---'
"
for u in ${UPLOADS[@]+"${UPLOADS[@]}"}; do
    case "${u%%|*}" in
        *.php) REMOTE_SCRIPT+="php -l '${u##*|}' | grep -v '^No syntax errors' || true
" ;;
    esac
done

if [ "${#REMOVALS[@]}" -gt 0 ]; then
    REMOTE_SCRIPT+="echo '--- remove deleted files ---'
"
    for r in "${REMOVALS[@]}"; do
        # Refuse anything that escapes the two known roots, so a malformed path can never
        # turn into an rm somewhere else on the account.
        case "$r" in
            "$REMOTE_APP"/*|"$REMOTE_PUB"/*) ;;
            *) fail "refusing to remove path outside the app roots: $r" ;;
        esac
        case "$r" in *..*) fail "refusing to remove path containing '..': $r" ;; esac
        REMOTE_SCRIPT+="rm -f '$r' && echo 'removed $r'
"
    done
fi

[ "$NEEDS_MIGRATE" -eq 1 ] && REMOTE_SCRIPT+="echo '--- migrate ---'
php artisan migrate --force 2>&1 | tail -5
"
[ "$NEEDS_VIEW_CLEAR" -eq 1 ] && REMOTE_SCRIPT+="echo '--- view:clear ---'
php artisan view:clear 2>&1 | grep -i info || true
"
[ "$NEEDS_ROUTE_CLEAR" -eq 1 ] && REMOTE_SCRIPT+="echo '--- route:clear ---'
php artisan route:clear 2>&1 | grep -i 'info\|error' || true
"
[ "$NEEDS_CONFIG_CLEAR" -eq 1 ] && REMOTE_SCRIPT+="echo '--- config:clear ---'
php artisan config:clear 2>&1 | grep -i 'info\|error' || true
"
REMOTE_SCRIPT+="echo '--- done ---'"

echo "Running remote steps (single ssh session)..."
ssh "${SSH_MUX[@]}" "$HOST" "$REMOTE_SCRIPT" 2>&1 | sed 's/^/  /'

# --- Verify over HTTP (no SSH needed) -----------------------------------------------
echo "Verifying..."
# Capture the status separately from curl's exit code: on a non-zero exit the old
# `$(curl ... || echo 000)` form concatenated BOTH outputs ("200" + "000" = "200000")
# and reported a healthy deploy as failed.
#
# Retried, because a single probe is not evidence. curl on Git Bash uses the Windows
# schannel TLS backend, which intermittently drops this connection and returns 000 --
# a transport failure on this machine, not a fault on the server. Reporting that as
# "deploy failed" is worse than useless: it invites a rollback of a perfectly healthy
# deploy. Three tries, and only a consistent failure counts.
code=000
for attempt in 1 2 3; do
    code=$(curl -s -o /dev/null -w '%{http_code}' -m 45 https://xtrafreaky.com/) || code=000
    [ -n "$code" ] || code=000
    [ "$code" = "200" ] && break
    [ "$attempt" -lt 3 ] && { log "attempt $attempt => HTTP $code, retrying..."; sleep 3; }
done
log "https://xtrafreaky.com/ => HTTP $code"
[ "$code" = "200" ] || fail "site did not return 200 after deploy (3 attempts)"

echo "Deploy complete."

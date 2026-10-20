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

if [ "${1:-}" = "--files" ]; then
    shift
    FILES=("$@")
else
    REF="${1:-HEAD~1}"
    mapfile -t FILES < <(git diff --name-only --diff-filter=ACMR "$REF"..HEAD)
fi

[ "${#FILES[@]}" -gt 0 ] || fail "no changed files to deploy"

# --- Map local paths to remote paths ------------------------------------------------
# public/*  ->  public_html/*   (public_html IS Laravel's public dir on this host)
# everything else -> laravel/*
declare -a UPLOADS=()
declare -a SKIPPED=()
NEEDS_MIGRATE=0
NEEDS_VIEW_CLEAR=0

for f in "${FILES[@]}"; do
    [ -f "$f" ] || { SKIPPED+=("$f (missing locally)"); continue; }

    case "$f" in
        public/*)            remote="$REMOTE_PUB/${f#public/}" ;;
        database/migrations/*) remote="$REMOTE_APP/$f"; NEEDS_MIGRATE=1 ;;
        *)                   remote="$REMOTE_APP/$f" ;;
    esac

    case "$f" in
        resources/views/*) NEEDS_VIEW_CLEAR=1 ;;
    esac

    UPLOADS+=("$f|$remote")
done

echo "Deploying ${#UPLOADS[@]} file(s) to $HOST"
for u in "${UPLOADS[@]}"; do log "${u%%|*}  ->  ${u##*|}"; done
[ "${#SKIPPED[@]}" -eq 0 ] || { echo "Skipped:"; for s in "${SKIPPED[@]}"; do log "$s"; done; }
[ "$NEEDS_MIGRATE" -eq 1 ]    && log "(migrations included — will run migrate --force)"
[ "$NEEDS_VIEW_CLEAR" -eq 1 ] && log "(views included — will clear compiled views)"

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

# --- Remote work: ONE ssh session ---------------------------------------------------
REMOTE_SCRIPT="set -e
cd $REMOTE_APP
echo '--- syntax check ---'
"
for u in "${UPLOADS[@]}"; do
    case "${u%%|*}" in
        *.php) REMOTE_SCRIPT+="php -l '${u##*|}' | grep -v '^No syntax errors' || true
" ;;
    esac
done
[ "$NEEDS_MIGRATE" -eq 1 ] && REMOTE_SCRIPT+="echo '--- migrate ---'
php artisan migrate --force 2>&1 | tail -5
"
[ "$NEEDS_VIEW_CLEAR" -eq 1 ] && REMOTE_SCRIPT+="echo '--- view:clear ---'
php artisan view:clear 2>&1 | grep -i info || true
"
REMOTE_SCRIPT+="echo '--- done ---'"

echo "Running remote steps (single ssh session)..."
ssh "${SSH_MUX[@]}" "$HOST" "$REMOTE_SCRIPT" 2>&1 | sed 's/^/  /'

# --- Verify over HTTP (no SSH needed) -----------------------------------------------
echo "Verifying..."
# Capture the status separately from curl's exit code: on a non-zero exit the old
# `$(curl ... || echo 000)` form concatenated BOTH outputs ("200" + "000" = "200000")
# and reported a healthy deploy as failed.
code=$(curl -s -o /dev/null -w '%{http_code}' -m 45 https://xtrafreaky.com/) || code=000
[ -n "$code" ] || code=000
log "https://xtrafreaky.com/ => HTTP $code"
[ "$code" = "200" ] || fail "site did not return 200 after deploy"

echo "Deploy complete."

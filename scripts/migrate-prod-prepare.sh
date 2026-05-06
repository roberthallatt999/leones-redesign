#!/bin/bash
#
# Phase 1 — Prepare leones-prod for atomic-deploy structure.
#
# Mirrors migrate-staging-prepare.sh with two differences:
#   - Targets the production server (leones-prod / /home/forge/leonescreamery.com)
#   - REUSES the staging deploy key (~/.ssh/leones-dev-deploy) instead of
#     generating a new one. The staging public key is appended to the prod
#     server's authorized_keys.
#
# Non-destructive: backs up the existing site, installs the staging public key
# on prod, creates the releases/ + shared/ skeleton, and seeds shared/ with
# config.local.php so the first GitHub Actions deploy can symlink to it.
#
# After this script completes:
#   1. Update .github/secrets.yaml with PROD_* values
#      (DB creds, PROD_SERVER_HOST, PROD_SERVER_USER, PROD_BASE_PATH)
#   2. Run .github/deploy-scripts/manage-secrets.sh
#   3. Disable Forge auto-deploy on the production site (Apps → Quick Deploy)
#   4. Trigger the first prod deploy:
#         gh workflow run lc-production.yml --ref main
#   5. After it succeeds, run scripts/migrate-prod-sync-images.sh
#
set -euo pipefail

# ----------------------------------------------------------------------------
# Configuration — edit if your SSH alias or remote path differ
# ----------------------------------------------------------------------------
SSH_ALIAS="leones-prod"
REMOTE_BASE_PATH="/home/forge/leonescreamery.com"
DEPLOY_KEY_PATH="${HOME}/.ssh/leones-dev-deploy"   # reused from staging

# ----------------------------------------------------------------------------
# Helpers
# ----------------------------------------------------------------------------
red()    { printf '\033[31m%s\033[0m\n' "$*"; }
green()  { printf '\033[32m%s\033[0m\n' "$*"; }
yellow() { printf '\033[33m%s\033[0m\n' "$*"; }
bold()   { printf '\033[1m%s\033[0m\n' "$*"; }

confirm() {
    local prompt="$1"
    read -r -p "$prompt [y/N] " response
    [[ "$response" =~ ^[Yy]$ ]]
}

step() {
    echo
    bold "──── $* ────"
}

# ----------------------------------------------------------------------------
# Step 0: sanity checks
# ----------------------------------------------------------------------------
step "Step 0: sanity checks"

if ! command -v ssh &>/dev/null; then
    red "ssh not found"; exit 1
fi

if [ ! -f "${DEPLOY_KEY_PATH}" ]; then
    red "Deploy key not found at ${DEPLOY_KEY_PATH}"
    yellow "Run scripts/migrate-staging-prepare.sh first to generate the staging key,"
    yellow "or update DEPLOY_KEY_PATH in this script if you want a different key."
    exit 1
fi
green "Deploy key present: ${DEPLOY_KEY_PATH}"

if [ ! -f "${DEPLOY_KEY_PATH}.pub" ]; then
    red "Public key missing at ${DEPLOY_KEY_PATH}.pub"
    exit 1
fi

# ----------------------------------------------------------------------------
# Step 1: verify SSH connection
# ----------------------------------------------------------------------------
step "Step 1: verify SSH connection to ${SSH_ALIAS}"

if ! ssh -o BatchMode=yes -o ConnectTimeout=8 "${SSH_ALIAS}" "hostname && pwd" 2>/dev/null; then
    red "Cannot SSH to '${SSH_ALIAS}' non-interactively."
    yellow "Verify: 'ssh ${SSH_ALIAS}' works from your laptop, then re-run."
    exit 1
fi
green "SSH connection OK"

# ----------------------------------------------------------------------------
# Step 2: verify the remote site directory exists
# ----------------------------------------------------------------------------
step "Step 2: verify remote site directory"

if ! ssh "${SSH_ALIAS}" "test -d '${REMOTE_BASE_PATH}'"; then
    red "Remote directory does not exist: ${REMOTE_BASE_PATH}"
    exit 1
fi
green "Remote directory exists: ${REMOTE_BASE_PATH}"

# ----------------------------------------------------------------------------
# Step 3: take a backup tarball OUTSIDE the dir being migrated
# ----------------------------------------------------------------------------
step "Step 3: take backup tarball"

BACKUP_TS=$(date -u '+%Y%m%d%H%M%S')
BACKUP_PATH="${REMOTE_BASE_PATH}.backup-${BACKUP_TS}.tar.gz"

yellow "About to create:"
yellow "  ${BACKUP_PATH}"
yellow "(backup lives outside ${REMOTE_BASE_PATH} so it survives later cleanup)"
yellow "Production has Forge daily DB backups at 00:30 as a separate safety net."

if ! confirm "Proceed with file backup?"; then
    red "Aborted."; exit 1
fi

ssh "${SSH_ALIAS}" "tar -czf '${BACKUP_PATH}' -C '$(dirname "${REMOTE_BASE_PATH}")' '$(basename "${REMOTE_BASE_PATH}")'"

# Show size for verification
SIZE=$(ssh "${SSH_ALIAS}" "ls -lh '${BACKUP_PATH}' | awk '{print \$5}'")
green "Backup created: ${BACKUP_PATH} (${SIZE})"

# ----------------------------------------------------------------------------
# Step 4: install staging public key on prod (idempotent)
# ----------------------------------------------------------------------------
step "Step 4: install deploy key on ${SSH_ALIAS}"

PUB_KEY=$(cat "${DEPLOY_KEY_PATH}.pub")

ssh "${SSH_ALIAS}" "
    mkdir -p ~/.ssh && chmod 700 ~/.ssh
    touch ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys
    if grep -qxF '${PUB_KEY}' ~/.ssh/authorized_keys; then
        echo 'Public key already present in authorized_keys'
    else
        echo '${PUB_KEY}' >> ~/.ssh/authorized_keys
        echo 'Public key appended to authorized_keys'
    fi
"

# ----------------------------------------------------------------------------
# Step 5: test the deploy key
# ----------------------------------------------------------------------------
step "Step 5: test the deploy key"

RESOLVED=$(ssh -G "${SSH_ALIAS}" 2>/dev/null | awk '/^user / {u=$2} /^hostname / {h=$2} END {print u "@" h}')
if [ -n "${RESOLVED}" ] && [ "${RESOLVED}" != "@" ]; then
    if ssh -i "${DEPLOY_KEY_PATH}" -o IdentitiesOnly=yes -o BatchMode=yes \
           -o StrictHostKeyChecking=accept-new "${RESOLVED}" "echo deploy-key-ok" 2>/dev/null | grep -q "deploy-key-ok"; then
        green "Deploy key works: ${RESOLVED}"
    else
        yellow "Could not authenticate with the deploy key directly to ${RESOLVED}."
        yellow "GitHub Actions may still work — the runner uses the private key contents, not your local SSH config."
    fi
else
    yellow "Could not resolve ${SSH_ALIAS} to a host. Skipping direct key test."
fi

# ----------------------------------------------------------------------------
# Step 6: create directory skeleton on the server (idempotent)
# ----------------------------------------------------------------------------
step "Step 6: create releases/ + shared/ skeleton on server"

ssh "${SSH_ALIAS}" "
    set -e
    cd '${REMOTE_BASE_PATH}'
    mkdir -p releases
    mkdir -p shared/images
    mkdir -p shared/lcmin/user/cache
    mkdir -p shared/lcmin/user/config
    chmod -R 775 shared/lcmin/user/cache
    echo 'Skeleton created:'
    ls -la
    echo
    echo 'shared/ contents:'
    find shared -maxdepth 4 -mindepth 1 | sort
"
green "Skeleton ready"

# ----------------------------------------------------------------------------
# Step 7: copy config.local.php into shared/ (no-clobber)
# ----------------------------------------------------------------------------
step "Step 7: seed shared/ with config.local.php"

ssh "${SSH_ALIAS}" "
    set -e
    cd '${REMOTE_BASE_PATH}'
    if [ ! -f lcmin/user/config/config.local.php ]; then
        echo 'WARNING: lcmin/user/config/config.local.php not found in current site — skipping copy.'
        echo 'You will need to place a config.local.php into shared/lcmin/user/config/ before the first deploy.'
    else
        if [ -f shared/lcmin/user/config/config.local.php ]; then
            echo 'shared/lcmin/user/config/config.local.php already exists — preserving (no overwrite).'
        else
            cp lcmin/user/config/config.local.php shared/lcmin/user/config/config.local.php
            echo 'Copied config.local.php → shared/lcmin/user/config/'
        fi
    fi
"

# ----------------------------------------------------------------------------
# Done. Print next steps.
# ----------------------------------------------------------------------------
step "Phase 1 (prod) complete ✅"

cat <<EOF

Next steps:

  1. Open .github/secrets.yaml and fill in the deployment.production block:
       PROD_SERVER_HOST = <ip or hostname for leones-prod>
       PROD_SERVER_USER = forge
       PROD_DB_HOST     = (from Forge → site → Database Info)
       PROD_DB_USER     = (from Forge → site → Database Info)
       PROD_DB_PASS     = (from Forge → site → Database Info)
       PROD_DB_NAME     = leones_production
       PROD_BASE_PATH   = ${REMOTE_BASE_PATH}

     The ssh.private_key field is already correct (reused from staging).

  2. Re-run secrets sync:
        bash .github/deploy-scripts/manage-secrets.sh

  3. Disable Forge auto-deploy on the production site:
        Forge UI → server → leonescreamery.com → Apps → Quick Deploy → toggle OFF
     (Lesson learned from staging — otherwise Forge git-pulls into the BASE_PATH root
     while GH Actions deploys atomically into releases/.)

  4. Trigger first production deploy:
        gh workflow run lc-production.yml --ref main

     Watch with:
        gh run watch \$(gh run list --workflow=lc-production.yml --limit=1 --json databaseId -q '.[0].databaseId')

  5. After deploy succeeds:
        bash scripts/migrate-prod-sync-images.sh

  6. Update Forge nginx site webroot to:
        ${REMOTE_BASE_PATH}/current

  7. Verify the site loads, then run:
        bash scripts/migrate-prod-cleanup.sh

EOF

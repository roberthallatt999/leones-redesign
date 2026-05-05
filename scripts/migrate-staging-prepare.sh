#!/bin/bash
#
# Phase 1 — Prepare leones-dev for atomic-deploy structure.
#
# Non-destructive: backs up the existing site, generates a dedicated SSH deploy
# key, creates the releases/ + shared/ skeleton, and seeds shared/ with
# config.local.php so the first GitHub Actions deploy can symlink to it.
#
# After this script completes:
#   1. Copy ~/.ssh/leones-dev-deploy contents into .github/secrets.yaml
#      (under ssh.private_key)
#   2. Fill in remaining secrets (DB, host, paths) in .github/secrets.yaml
#   3. Run .github/deploy-scripts/manage-secrets.sh
#   4. Push to develop → first GitHub Actions deploy creates releases/<ts>/
#
set -euo pipefail

# ----------------------------------------------------------------------------
# Configuration — edit if your SSH alias or remote path differ
# ----------------------------------------------------------------------------
SSH_ALIAS="leones-dev"
REMOTE_BASE_PATH="/home/forge/lc.digitaldesigns.dev"
DEPLOY_KEY_PATH="${HOME}/.ssh/leones-dev-deploy"
DEPLOY_KEY_COMMENT="github-actions@leonescreamery"

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
if ! command -v ssh-keygen &>/dev/null; then
    red "ssh-keygen not found"; exit 1
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

if ! confirm "Proceed with backup?"; then
    red "Aborted."; exit 1
fi

ssh "${SSH_ALIAS}" "tar -czf '${BACKUP_PATH}' -C '$(dirname "${REMOTE_BASE_PATH}")' '$(basename "${REMOTE_BASE_PATH}")'"

# Show size for verification
SIZE=$(ssh "${SSH_ALIAS}" "ls -lh '${BACKUP_PATH}' | awk '{print \$5}'")
green "Backup created: ${BACKUP_PATH} (${SIZE})"

# ----------------------------------------------------------------------------
# Step 4: generate dedicated SSH deploy key (idempotent)
# ----------------------------------------------------------------------------
step "Step 4: generate dedicated SSH deploy key for GitHub Actions"

if [ -f "${DEPLOY_KEY_PATH}" ]; then
    yellow "Deploy key already exists at ${DEPLOY_KEY_PATH} — reusing."
else
    ssh-keygen -t ed25519 -f "${DEPLOY_KEY_PATH}" -N "" -C "${DEPLOY_KEY_COMMENT}"
    green "Generated: ${DEPLOY_KEY_PATH}"
fi

PUB_KEY=$(cat "${DEPLOY_KEY_PATH}.pub")

# ----------------------------------------------------------------------------
# Step 5: append public key to remote authorized_keys (idempotent)
# ----------------------------------------------------------------------------
step "Step 5: install public key on ${SSH_ALIAS}"

# Use grep -qxF (exact match) to detect duplicates
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
# Step 6: test the new deploy key
# ----------------------------------------------------------------------------
step "Step 6: test the new deploy key"

# Resolve user@host from the SSH alias (so we can test the key directly)
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
# Step 7: create directory skeleton on the server (idempotent)
# ----------------------------------------------------------------------------
step "Step 7: create releases/ + shared/ skeleton on server"

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
# Step 8: copy config.local.php into shared/ (no-clobber)
# ----------------------------------------------------------------------------
step "Step 8: seed shared/ with config.local.php"

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
# Done. Print next steps and the private key.
# ----------------------------------------------------------------------------
step "Phase 1 complete ✅"

cat <<EOF

Next steps:

  1. Copy .github/secrets.yaml.example to .github/secrets.yaml:
        cp .github/secrets.yaml.example .github/secrets.yaml

  2. Open .github/secrets.yaml and:
       a) Paste the private key below into the ssh.private_key field
          (preserve the leading two-space indent for each line)
       b) Fill in deployment.staging.SERVER_HOST, SERVER_USER, SERVER_PHP_PATH,
          STAGE_DB_*, STAGE_BASE_PATH
          (STAGE_BASE_PATH should be: ${REMOTE_BASE_PATH})

  3. Upload secrets to GitHub:
        bash .github/deploy-scripts/manage-secrets.sh

  4. Merge feature/github-actions-deploy into develop and push.
     The first deploy will create releases/<timestamp>/ and the current symlink.

  5. After the first deploy succeeds, run:
        bash scripts/migrate-staging-sync-images.sh

  6. Update Forge nginx site webroot to:
        ${REMOTE_BASE_PATH}/current

  7. Verify the site loads, then run:
        bash scripts/migrate-staging-cleanup.sh

EOF

bold "──── Deploy private key (for .github/secrets.yaml) ────"
echo
cat "${DEPLOY_KEY_PATH}"
echo
bold "──── End of private key ────"
echo
yellow "Treat the key above as a secret. Never commit it. The matching public key has been installed on ${SSH_ALIAS}."

#!/bin/bash
#
# Phase 2 — Copy existing images/ into shared/images/ on leones-prod.
#
# Run AFTER the first GH Actions production deploy has succeeded (so a
# releases/<ts>/ directory and a `current` symlink exist) and BEFORE you
# change the Forge nginx webroot to ${REMOTE_BASE_PATH}/current.
#
# Non-destructive: original images/ stays in place. The old site keeps
# serving correctly until you cut over the nginx webroot.
#
set -euo pipefail

SSH_ALIAS="leones-prod"
REMOTE_BASE_PATH="/home/forge/leonescreamery.com"

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
# Step 1: verify SSH and that the first deploy has happened
# ----------------------------------------------------------------------------
step "Step 1: verify the first deploy completed"

if ! ssh -o BatchMode=yes -o ConnectTimeout=8 "${SSH_ALIAS}" "test -d '${REMOTE_BASE_PATH}'"; then
    red "Cannot access ${REMOTE_BASE_PATH} on ${SSH_ALIAS}"
    exit 1
fi

RELEASES_COUNT=$(ssh "${SSH_ALIAS}" "ls -1d ${REMOTE_BASE_PATH}/releases/*/ 2>/dev/null | wc -l" | tr -d ' ')
if [ "${RELEASES_COUNT}" -eq 0 ]; then
    red "No release directories found under ${REMOTE_BASE_PATH}/releases/"
    yellow "Run the first production GitHub Actions deploy before continuing:"
    yellow "  gh workflow run lc-production.yml --ref main"
    exit 1
fi
green "Found ${RELEASES_COUNT} release(s)"

if ! ssh "${SSH_ALIAS}" "test -L '${REMOTE_BASE_PATH}/current'"; then
    red "${REMOTE_BASE_PATH}/current is not a symlink (or doesn't exist)"
    yellow "Run the first production GitHub Actions deploy before continuing."
    exit 1
fi
CURRENT_TARGET=$(ssh "${SSH_ALIAS}" "readlink -f '${REMOTE_BASE_PATH}/current'")
green "current → ${CURRENT_TARGET}"

# ----------------------------------------------------------------------------
# Step 2: confirm old images/ exists at the source
# ----------------------------------------------------------------------------
step "Step 2: verify images/ at source"

if ! ssh "${SSH_ALIAS}" "test -d '${REMOTE_BASE_PATH}/images'"; then
    yellow "${REMOTE_BASE_PATH}/images does not exist (or already removed)"
    yellow "Skipping rsync — nothing to copy."
    exit 0
fi

SIZE=$(ssh "${SSH_ALIAS}" "du -sh '${REMOTE_BASE_PATH}/images' | awk '{print \$1}'")
COUNT=$(ssh "${SSH_ALIAS}" "find '${REMOTE_BASE_PATH}/images' -type f | wc -l" | tr -d ' ')
green "Source: ${REMOTE_BASE_PATH}/images (${SIZE}, ${COUNT} files)"

# ----------------------------------------------------------------------------
# Step 3: rsync into shared/images
# ----------------------------------------------------------------------------
step "Step 3: rsync images/ → shared/images/"

yellow "About to rsync (non-destructive — original is preserved):"
yellow "  ${REMOTE_BASE_PATH}/images/  →  ${REMOTE_BASE_PATH}/shared/images/"
echo
if ! confirm "Proceed?"; then
    red "Aborted."; exit 1
fi

ssh "${SSH_ALIAS}" "
    set -e
    rsync -a --info=progress2 \
        '${REMOTE_BASE_PATH}/images/' \
        '${REMOTE_BASE_PATH}/shared/images/'
"

# ----------------------------------------------------------------------------
# Step 4: verify
# ----------------------------------------------------------------------------
step "Step 4: verify shared/images is populated"

SHARED_COUNT=$(ssh "${SSH_ALIAS}" "find '${REMOTE_BASE_PATH}/shared/images' -type f | wc -l" | tr -d ' ')
green "shared/images now contains ${SHARED_COUNT} files"

if ssh "${SSH_ALIAS}" "test -L '${CURRENT_TARGET}/images'"; then
    LINK_TARGET=$(ssh "${SSH_ALIAS}" "readlink '${CURRENT_TARGET}/images'")
    green "current/images symlink → ${LINK_TARGET}"
else
    yellow "current/images is not a symlink. The deploy may have placed a copy there."
    yellow "Inspect manually: ssh ${SSH_ALIAS} 'ls -la ${CURRENT_TARGET}/images'"
fi

step "Phase 2 (prod) complete ✅"

cat <<EOF

Next step:

  Update the Forge nginx site webroot from:
      ${REMOTE_BASE_PATH}
  to:
      ${REMOTE_BASE_PATH}/current

  Forge UI: Sites → leonescreamery.com → Meta → Web Directory
  After saving, Forge reloads nginx automatically. Brief downtime as nginx reloads.

  Then load the production URL, click through 3-4 pages, confirm:
    - CSS loads
    - Images render (icons, logos, uploaded photos)
    - Page bodies are NOT empty (Stash sanity check)
    - Forms work (contact, etc.)
    - Member-area pages work (login, account)

  Once verified, finish migration with:
      bash scripts/migrate-prod-cleanup.sh

EOF

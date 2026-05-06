#!/bin/bash
#
# Phase 3 — Remove the legacy site files at the BASE_PATH root on leones-prod.
#
# Run this ONLY after:
#   1. Phase 1 + Phase 2 are complete
#   2. Forge auto-deploy is disabled on the production site
#   3. The Forge nginx webroot has been changed to ${REMOTE_BASE_PATH}/current
#   4. The production site is verified working from the new layout
#
# This deletes the old top-level site files (lcmin/, themes/, css/, js/, images/,
# src/, admin.php, index.php, favicon.ico, robots.txt, .htaccess, .well-known
# at the root) and any Forge auto-deploy artifacts (.git/, .github/, scripts/,
# .nvmrc, DEPLOY.md, LICENSE, .gitignore) — leaving only releases/, shared/,
# current, and any backup tarball at the parent path.
#
# The backup tarball lives at ${REMOTE_BASE_PATH}.backup-<ts>.tar.gz (sibling
# of REMOTE_BASE_PATH) so it is NOT touched by this script.
#
# Production-specific note: Forge daily DB backups (00:30) are independent of
# this filesystem cleanup and continue uninterrupted.
#
set -euo pipefail

SSH_ALIAS="leones-prod"
REMOTE_BASE_PATH="/home/forge/leonescreamery.com"

LEGACY_ITEMS=(
    images
    lcmin
    themes
    css
    js
    src
    imgs
    lcmin.bak
    admin.php
    index.php
    favicon.ico
    robots.txt
    .htaccess
    .well-known
    package.json
    package-lock.json
    # Forge auto-deploy artifacts (only relevant if Forge git-pulled into root)
    .git
    .github
    .gitignore
    .nvmrc
    DEPLOY.md
    LICENSE
    scripts
)

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
# Step 1: precondition checks
# ----------------------------------------------------------------------------
step "Step 1: preconditions"

if ! ssh -o BatchMode=yes -o ConnectTimeout=8 "${SSH_ALIAS}" "test -d '${REMOTE_BASE_PATH}'"; then
    red "Cannot access ${REMOTE_BASE_PATH} on ${SSH_ALIAS}"
    exit 1
fi

if ! ssh "${SSH_ALIAS}" "test -L '${REMOTE_BASE_PATH}/current'"; then
    red "${REMOTE_BASE_PATH}/current is not a symlink. Refusing to clean up."
    yellow "Run Phase 1 + Phase 2 + first deploy first."
    exit 1
fi

if ! ssh "${SSH_ALIAS}" "test -d '${REMOTE_BASE_PATH}/shared/images'"; then
    red "shared/images does not exist. Run Phase 2 first."
    exit 1
fi

# Check that a backup tarball exists somewhere (warn but don't block)
TARBALL_COUNT=$(ssh "${SSH_ALIAS}" "ls -1 ${REMOTE_BASE_PATH}.backup-*.tar.gz 2>/dev/null | wc -l" | tr -d ' ')
if [ "${TARBALL_COUNT}" -eq 0 ]; then
    yellow "WARNING: No backup tarball found at ${REMOTE_BASE_PATH}.backup-*.tar.gz"
    yellow "(Forge daily DB backups still cover the database, but no filesystem snapshot is available.)"
    if ! confirm "Continue anyway? (NOT recommended without a filesystem backup)"; then
        red "Aborted."; exit 1
    fi
else
    LATEST=$(ssh "${SSH_ALIAS}" "ls -1t ${REMOTE_BASE_PATH}.backup-*.tar.gz | head -1")
    green "Backup tarball found: ${LATEST}"
fi

# ----------------------------------------------------------------------------
# Step 2: build the actual deletion list (only items that exist)
# ----------------------------------------------------------------------------
step "Step 2: identify legacy files at the BASE_PATH root"

EXISTS_LIST=()
for item in "${LEGACY_ITEMS[@]}"; do
    if ssh "${SSH_ALIAS}" "test -e '${REMOTE_BASE_PATH}/${item}'"; then
        EXISTS_LIST+=("${item}")
    fi
done

if [ ${#EXISTS_LIST[@]} -eq 0 ]; then
    green "No legacy files to remove. Cleanup already complete."
    exit 0
fi

echo
yellow "The following items will be DELETED from ${REMOTE_BASE_PATH} on PRODUCTION:"
for item in "${EXISTS_LIST[@]}"; do
    echo "  - ${item}"
done
echo
yellow "Untouched (preserved):"
echo "  - releases/"
echo "  - shared/"
echo "  - current (symlink)"
echo "  - any backup tarball (sibling of BASE_PATH)"
echo "  - any other root file/dir not in the legacy list"
echo

# ----------------------------------------------------------------------------
# Step 3: triple-confirm (extra strict for production)
# ----------------------------------------------------------------------------
step "Step 3: confirmation (production)"

if ! confirm "Have you verified the PRODUCTION site loads correctly from ${REMOTE_BASE_PATH}/current?"; then
    red "Aborted. Verify the site works first, then re-run."
    exit 1
fi

if ! confirm "Is Forge auto-deploy DISABLED for the production site?"; then
    red "Aborted. Disable Forge auto-deploy first to prevent re-pollution."
    exit 1
fi

if ! confirm "Delete the items listed above from PRODUCTION?"; then
    red "Aborted."; exit 1
fi

read -r -p "Type 'DELETE-PRODUCTION' (uppercase) to confirm: " final
if [ "${final}" != "DELETE-PRODUCTION" ]; then
    red "Aborted (didn't get DELETE-PRODUCTION)."; exit 1
fi

# ----------------------------------------------------------------------------
# Step 4: execute deletion
# ----------------------------------------------------------------------------
step "Step 4: deleting legacy files"

for item in "${EXISTS_LIST[@]}"; do
    echo "Removing ${item}..."
    ssh "${SSH_ALIAS}" "rm -rf '${REMOTE_BASE_PATH}/${item}'"
done

# ----------------------------------------------------------------------------
# Step 5: show final state
# ----------------------------------------------------------------------------
step "Step 5: final state"

ssh "${SSH_ALIAS}" "ls -la '${REMOTE_BASE_PATH}'"

green "Phase 3 (prod) complete ✅"
echo
cat <<EOF
Production migration complete. Going forward, deploys are triggered manually:
  gh workflow run lc-production.yml --ref main
or via the GitHub Actions UI (workflow_dispatch).

If anything breaks, two restore paths are available:
  1. Filesystem: restore from the backup tarball:
       ssh ${SSH_ALIAS}
       cd $(dirname "${REMOTE_BASE_PATH}")
       rm -rf $(basename "${REMOTE_BASE_PATH}")
       tar -xzf ${REMOTE_BASE_PATH}.backup-<ts>.tar.gz
       # then revert the Forge nginx webroot to ${REMOTE_BASE_PATH}
  2. Database: restore from Forge's daily DB backup (Forge UI → site → Backups)
EOF

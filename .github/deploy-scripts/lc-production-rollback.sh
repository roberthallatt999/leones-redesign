#!/bin/bash
set -e

# Rollback script for Leone's Creamery production environment
# Parameters:
# $1 - The rollback release directory path
# $2 - The base path

ROLLBACK_DIR="$1"
BASE_PATH="$2"

echo "Starting Leone's Creamery production rollback steps..."

# Add ExpressionEngine specific database variable mappings
export DB_USERNAME="${DB_USER}"   # EE uses DB_USERNAME instead of DB_USER
export DB_DATABASE="${DB_NAME}"   # EE uses DB_DATABASE instead of DB_NAME

# ---------------------------------------------------------------
# Restore shared symlinks
# ---------------------------------------------------------------
echo "Restoring shared symlinks in ${ROLLBACK_DIR}..."

# Remove any stale entries before re-symlinking
rm -rf "${ROLLBACK_DIR}/images"
rm -rf "${ROLLBACK_DIR}/lcmin/user/cache"
rm -f  "${ROLLBACK_DIR}/lcmin/user/config/config.local.php"

# Re-create symlinks pointing to the shared directory
ln -sfn "${BASE_PATH}/shared/images"                               "${ROLLBACK_DIR}/images"
ln -sfn "${BASE_PATH}/shared/lcmin/user/cache"                     "${ROLLBACK_DIR}/lcmin/user/cache"
ln -sfn "${BASE_PATH}/shared/lcmin/user/config/config.local.php"   "${ROLLBACK_DIR}/lcmin/user/config/config.local.php"

# Verify all symlinks were restored
for link in images lcmin/user/cache lcmin/user/config/config.local.php; do
    if [ ! -L "${ROLLBACK_DIR}/${link}" ]; then
        echo "ERROR: Failed to restore symlink for ${link}"
        exit 1
    fi
done
echo "All symlinks restored successfully ✅"

# ---------------------------------------------------------------
# Ensure cache directory is writable
# ---------------------------------------------------------------
chmod -R 775 "${ROLLBACK_DIR}/lcmin/user/cache"

# ---------------------------------------------------------------
# Clear ExpressionEngine cache
# ---------------------------------------------------------------
echo "Clearing ExpressionEngine cache..."
cd "${ROLLBACK_DIR}"
PHP_BIN="${PHP_BIN:-php}"
${PHP_BIN} -d variables_order=EGPCS -d 'error_reporting=E_ALL&~E_DEPRECATED' lcmin/ee/eecli.php cache:clear

echo "Leone's Creamery production rollback completed successfully ✅"

#!/bin/bash
set -e

# Deployment script for Leone's Creamery staging environment
# Parameters:
# $1 - The release directory path
# $2 - The base path

RELEASE_DIR="$1"
BASE_PATH="$2"

echo "Starting Leone's Creamery staging deployment steps..."

# Add ExpressionEngine specific database variable mappings
export DB_USERNAME="${DB_USER}"   # EE uses DB_USERNAME instead of DB_USER
export DB_DATABASE="${DB_NAME}"   # EE uses DB_DATABASE instead of DB_NAME

# Ensure required EE directories exist
mkdir -p "${RELEASE_DIR}/lcmin/user/config"

# ---------------------------------------------------------------
# Set up shared symlinks
# ---------------------------------------------------------------
echo "Setting up shared symlinks in ${RELEASE_DIR}..."

# Remove any rsync'd copies of shared items before symlinking
rm -rf "${RELEASE_DIR}/images"
rm -rf "${RELEASE_DIR}/lcmin/user/cache"
rm -f  "${RELEASE_DIR}/lcmin/user/config/config.local.php"

# Create symlinks pointing to the shared directory
ln -sfn "${BASE_PATH}/shared/images"                               "${RELEASE_DIR}/images"
ln -sfn "${BASE_PATH}/shared/lcmin/user/cache"                     "${RELEASE_DIR}/lcmin/user/cache"
ln -sfn "${BASE_PATH}/shared/lcmin/user/config/config.local.php"   "${RELEASE_DIR}/lcmin/user/config/config.local.php"

# Verify all symlinks were created
echo "Verifying symlinks..."
for link in images lcmin/user/cache lcmin/user/config/config.local.php; do
    if [ ! -L "${RELEASE_DIR}/${link}" ]; then
        echo "ERROR: Failed to create symlink for ${link}"
        exit 1
    fi
done
echo "All symlinks created successfully ✅"

# ---------------------------------------------------------------
# Set permissions
# ---------------------------------------------------------------
echo "Setting file permissions..."

# EE core directories (lcmin/ee/)
chmod 775 "${RELEASE_DIR}/lcmin/ee"
chmod 775 "${RELEASE_DIR}/lcmin/ee/ExpressionEngine"
chmod 775 "${RELEASE_DIR}/lcmin/ee/Mexitek"
chmod 775 "${RELEASE_DIR}/lcmin/ee/language"
chmod 775 "${RELEASE_DIR}/lcmin/ee/legacy"
chmod 775 "${RELEASE_DIR}/lcmin/ee/templates"
chmod 644 "${RELEASE_DIR}/lcmin/ee/index.html"
chmod 644 "${RELEASE_DIR}/lcmin/ee/eecli.php"
chmod 644 "${RELEASE_DIR}/lcmin/ee/eecms"

# User folder (cache is a symlink to shared; set writable)
chmod 644 "${RELEASE_DIR}/lcmin/user/config/config.php"
chmod -R 775 "${RELEASE_DIR}/lcmin/user/templates"

# Themes (themes/ee/)
chmod 775 "${RELEASE_DIR}/themes/ee"
chmod 775 "${RELEASE_DIR}/themes/ee/asset"
chmod 775 "${RELEASE_DIR}/themes/ee/cform"
chmod 775 "${RELEASE_DIR}/themes/ee/cp"
chmod 775 "${RELEASE_DIR}/themes/ee/debug"
chmod 775 "${RELEASE_DIR}/themes/ee/forum"
chmod 775 "${RELEASE_DIR}/themes/ee/member"
chmod 775 "${RELEASE_DIR}/themes/ee/pro"
chmod 775 "${RELEASE_DIR}/themes/ee/site"
chmod 775 "${RELEASE_DIR}/themes/ee/webfonts"
chmod 644 "${RELEASE_DIR}/themes/ee/index.html"
chmod -R 775 "${RELEASE_DIR}/themes/user"

# ---------------------------------------------------------------
# Clear ExpressionEngine cache
# ---------------------------------------------------------------
echo "Clearing ExpressionEngine cache..."
cd "${RELEASE_DIR}"
PHP_BIN="${PHP_BIN:-php}"
${PHP_BIN} -d variables_order=EGPCS lcmin/ee/eecli.php cache:clear

echo "Leone's Creamery staging deployment completed successfully ✅"

#!/bin/bash
#
# READ-ONLY audit of ExpressionEngine permission requirements.
#
# Compares actual filesystem permissions against EE 6 official documented
# requirements. Performs NO chmod, chown, or write operations of any kind —
# uses only stat, readlink, [ -w ], find.
#
# Usage:
#   bash scripts/audit-ee-permissions.sh [<ssh-alias> [<base-path>]]
#
# Defaults:
#   ssh-alias  = leones-dev
#   base-path  = /home/forge/lc.digitaldesigns.dev
#
# Examples:
#   bash scripts/audit-ee-permissions.sh
#   bash scripts/audit-ee-permissions.sh leones-prod /home/forge/leonescreamery.com
#
set -uo pipefail

SSH_ALIAS="${1:-leones-dev}"
BASE_PATH="${2:-/home/forge/lc.digitaldesigns.dev}"
EXPECTED_OWNER="forge"

red()    { printf '\033[31m%s\033[0m' "$*"; }
green()  { printf '\033[32m%s\033[0m' "$*"; }
yellow() { printf '\033[33m%s\033[0m' "$*"; }
bold()   { printf '\033[1m%s\033[0m\n' "$*"; }

bold "════════════════════════════════════════════════════════════════════"
bold "  ExpressionEngine Permission Audit (READ-ONLY)"
bold "════════════════════════════════════════════════════════════════════"
echo
echo "Server:        ${SSH_ALIAS}"
echo "Base path:     ${BASE_PATH}"
echo "Expected owner: ${EXPECTED_OWNER}"
echo

# Run audit on the server side. Pass BASE_PATH and EXPECTED_OWNER as args
# to bash -s. The heredoc is single-quoted so nothing is expanded locally.
ssh "${SSH_ALIAS}" bash -s "${BASE_PATH}" "${EXPECTED_OWNER}" <<'REMOTE'
set -uo pipefail
BASE_PATH="$1"
EXPECTED_OWNER="$2"

# ANSI helpers (replicate locally — heredoc is independent)
RED='\033[31m'; GREEN='\033[32m'; YELLOW='\033[33m'; RESET='\033[0m'

PASS=0
FAIL=0
WARN=0
MISSING=0

# Required-writable directories (per EE 6 docs, paths translated to lcmin/)
WRITABLE_DIRS=(
    "${BASE_PATH}/current/lcmin/ee"
    "${BASE_PATH}/current/lcmin/user/config"
    "${BASE_PATH}/current/lcmin/user/cache"
    "${BASE_PATH}/current/lcmin/user/templates"
    "${BASE_PATH}/current/themes/ee"
    "${BASE_PATH}/current/themes/user"
    "${BASE_PATH}/shared/lcmin/user/cache"
    "${BASE_PATH}/shared/images/avatars"
    "${BASE_PATH}/shared/images/captchas"
    "${BASE_PATH}/shared/images/member_photos"
    "${BASE_PATH}/shared/images/pm_attachments"
    "${BASE_PATH}/shared/images/signature_attachments"
    "${BASE_PATH}/shared/images/uploads"
)

# Required-writable files
WRITABLE_FILES=(
    "${BASE_PATH}/current/lcmin/user/config/config.php"
    "${BASE_PATH}/current/lcmin/user/config/config.local.php"
    "${BASE_PATH}/shared/lcmin/user/config/config.local.php"
)

print_row() {
    # $1=path-display  $2=mode  $3=owner  $4=status-code(PASS/WARN/FAIL/MISS)  $5=note
    local path="$1" mode="$2" owner="$3" code="$4" note="${5:-}"
    local color
    case "$code" in
        PASS) color="$GREEN" ;;
        WARN) color="$YELLOW" ;;
        FAIL) color="$RED" ;;
        MISS) color="$YELLOW" ;;
        *)    color="$RESET" ;;
    esac
    printf '  %-58s  %-5s  %-7s  %b%-4s%b  %s\n' \
        "$path" "$mode" "$owner" "$color" "$code" "$RESET" "$note"
}

check_path() {
    local p="$1"
    local short="${p#${BASE_PATH}/}"

    if [ ! -e "$p" ]; then
        print_row "$short" "-" "-" "MISS" "(does not exist)"
        MISSING=$((MISSING+1))
        return
    fi

    # Resolve symlinks for stat (we audit the target)
    local real
    real=$(readlink -f "$p")

    local mode owner notes=""
    mode=$(stat -c '%a' "$real" 2>/dev/null)
    owner=$(stat -c '%U' "$real" 2>/dev/null)

    if [ "$owner" != "$EXPECTED_OWNER" ]; then
        notes="owner is $owner (expected $EXPECTED_OWNER)"
    fi

    if [ -w "$p" ]; then
        if [ -n "$notes" ]; then
            print_row "$short" "$mode" "$owner" "WARN" "writable but $notes"
            WARN=$((WARN+1))
        else
            print_row "$short" "$mode" "$owner" "PASS" ""
            PASS=$((PASS+1))
        fi
    else
        print_row "$short" "$mode" "$owner" "FAIL" "NOT writable by current user${notes:+; $notes}"
        FAIL=$((FAIL+1))
    fi
}

# -----------------------------------------------------------------
# Static lists
# -----------------------------------------------------------------
echo
echo "PATH                                                          MODE   OWNER    STATUS"
echo "------------------------------------------------------------  -----  -------  ----------"
echo
echo "[ Required-writable directories ]"
for p in "${WRITABLE_DIRS[@]}"; do
    check_path "$p"
done

echo
echo "[ Required-writable files ]"
for p in "${WRITABLE_FILES[@]}"; do
    check_path "$p"
done

# -----------------------------------------------------------------
# Top-level subdirs of lcmin/ee/ (EE docs say these top-level items only)
# -----------------------------------------------------------------
echo
echo "[ lcmin/ee/* — top-level subdirectories ]"
shopt -s nullglob
for d in "${BASE_PATH}/current/lcmin/ee/"*/; do
    check_path "${d%/}"
done

echo
echo "[ themes/ee/* — top-level subdirectories ]"
for d in "${BASE_PATH}/current/themes/ee/"*/; do
    check_path "${d%/}"
done
shopt -u nullglob

# -----------------------------------------------------------------
# Optional: check the live release dir baseline (sample 10 dirs and 10 files)
# -----------------------------------------------------------------
echo
echo "[ Sample baseline check — 5 dirs + 5 files in current/ ]"
sample_dirs=$(find "${BASE_PATH}/current/" -mindepth 2 -maxdepth 4 -type d 2>/dev/null | head -5)
sample_files=$(find "${BASE_PATH}/current/" -type f -name '*.php' 2>/dev/null | head -5)
echo "  (dirs should be 755 / 775, files should be 644 — anything else may be a leftover)"
while IFS= read -r d; do
    [ -z "$d" ] && continue
    mode=$(stat -c '%a' "$d" 2>/dev/null)
    short="${d#${BASE_PATH}/}"
    case "$mode" in
        755|775) printf '  %-72s  %s\n' "$short" "$mode  ok" ;;
        *)       printf '  %-72s  %s\n' "$short" "$mode  unexpected" ;;
    esac
done <<< "$sample_dirs"

while IFS= read -r f; do
    [ -z "$f" ] && continue
    mode=$(stat -c '%a' "$f" 2>/dev/null)
    short="${f#${BASE_PATH}/}"
    case "$mode" in
        644|664) printf '  %-72s  %s\n' "$short" "$mode  ok" ;;
        *)       printf '  %-72s  %s\n' "$short" "$mode  unexpected" ;;
    esac
done <<< "$sample_files"

# -----------------------------------------------------------------
# Summary
# -----------------------------------------------------------------
echo
echo "------------------------------------------------------------"
echo "Summary:"
printf "  ${GREEN}PASS${RESET}    : %d\n" "$PASS"
printf "  ${YELLOW}WARN${RESET}    : %d\n" "$WARN"
printf "  ${RED}FAIL${RESET}    : %d\n" "$FAIL"
printf "  ${YELLOW}MISSING${RESET} : %d\n" "$MISSING"
echo "------------------------------------------------------------"

if [ "$FAIL" -gt 0 ]; then
    exit 2
elif [ "$WARN" -gt 0 ] || [ "$MISSING" -gt 0 ]; then
    exit 1
else
    exit 0
fi
REMOTE

EXIT=$?
echo
case "$EXIT" in
    0) green "Audit passed: all required paths writable, owner correct."; echo ;;
    1) yellow "Audit passed with warnings — check WARN/MISSING rows above."; echo ;;
    2) red "Audit FAILED — required paths are not writable. Fix needed."; echo ;;
    *) red "Audit script error (exit ${EXIT})."; echo ;;
esac
exit "$EXIT"

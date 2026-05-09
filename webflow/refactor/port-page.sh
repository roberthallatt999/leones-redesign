#!/usr/bin/env bash
# port-page.sh — pull a page's body content from the reference site and write
# it to an EE template file with layout wrapper + path rewrites.
#
# Usage: port-page.sh <ref-page-name> <ee-template-path> <meta-title> <page-class>
set -euo pipefail

REF_PAGE="$1"   # e.g. about-us
EE_PATH="$2"    # e.g. lcmin/user/templates/default_site/about.group/index.html
META_TITLE="$3" # e.g. About Us | Leone's Creamery
PAGE_CLASS="$4" # e.g. about

REF_URL="https://leones.digitaldesigns.online/${REF_PAGE}.html"
TMP_FULL=$(mktemp)
TMP_BODY=$(mktemp)

curl -sk -o "$TMP_FULL" "$REF_URL"

# Extract main body
START=$(grep -nE '<main class="main-wrapper">' "$TMP_FULL" | head -1 | cut -d: -f1)
END=$(grep -nE '</main>' "$TMP_FULL" | head -1 | cut -d: -f1)
if [[ -z "$START" || -z "$END" ]]; then
  echo "Could not find <main> boundaries in $REF_URL" >&2
  exit 1
fi
BODY_START=$((START + 1))
BODY_END=$((END - 1))
sed -n "${BODY_START},${BODY_END}p" "$TMP_FULL" > "$TMP_BODY"

# Path/href rewrites (same set used for the homepage port)
perl -i -0pe '
  s{src="images/}{src="/images/}g;
  s{srcset="images/}{srcset="/images/}g;
  s{, images/}{, /images/}g;
  s{src="videos/}{src="/videos/}g;
  s{href="index\.html#"}{href="#"}g;
  s{href="index\.html"}{href="\{site_url\}"}g;
  s{href="([\w-]+)\.html"}{href="/$1"}g;
  s{href="([\w-]+)\.html#([\w-]*)"}{href="/$1#$2"}g;
' "$TMP_BODY"

# Strip absolute reference URLs from inline styles
perl -i -pe 's|https://leones\.digitaldesigns\.online/&quot;videos/|"/videos/|g' "$TMP_BODY"
perl -i -pe 's|/videos/lc-hero-poster-00001\.jpg&quot;|/videos/lc-hero-poster-00001.jpg"|g' "$TMP_BODY"

# Compose the EE template
mkdir -p "$(dirname "$EE_PATH")"
{
  printf '%s\n' \
    '{layout="layouts/_layout_main"}' \
    "{layout:set name=\"meta_title\"}${META_TITLE}{/layout:set}" \
    "{layout:set name=\"page_class\"}${PAGE_CLASS}{/layout:set}" \
    ''
  cat "$TMP_BODY"
} > "$EE_PATH"

rm -f "$TMP_FULL" "$TMP_BODY"
LINE_COUNT=$(wc -l < "$EE_PATH")
echo "  $REF_PAGE -> $EE_PATH (${LINE_COUNT} lines)"

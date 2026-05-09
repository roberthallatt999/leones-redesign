#!/usr/bin/env node
// =============================================================================
// split-legacy-css.mjs — Move CSS rules from webflow/css/leones-creamery.webflow.css
// into the structured SCSS partials under webflow/src/scss/.
//
// Strategy:
//   1. Tokenize the legacy CSS into top-level "items": at-rules and regular
//      rules. Properly handles @media blocks by splitting their inner rules
//      out and re-wrapping each in its own @media (so each rule lands in its
//      destination partial with its media query intact).
//   2. For each rule, pick a destination partial based on the primary class
//      in the selector. Falls back to vendor/_legacy.scss.
//   3. Append rules to their destinations. Empties the legacy CSS file.
//
// Usage: node webflow/refactor/split-legacy-css.mjs
// =============================================================================

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname  = path.dirname(__filename);
const REPO_ROOT  = path.resolve(__dirname, '..', '..');
const SCSS_ROOT  = path.join(REPO_ROOT, 'webflow', 'src', 'scss');
const LEGACY_CSS = path.join(REPO_ROOT, 'webflow', 'css', 'leones-creamery.webflow.css');

// Destination map. First-match-wins on prefixes/regexes against the primary
// class token of the rule's selector.
const ROUTES = [
  // Layout
  { test: /^footer__/,                   to: 'layout/_footer.scss' },
  { test: /^nav__|^nav$/,                to: 'layout/_header.scss' },
  { test: /^menu-icon/,                  to: 'layout/_header.scss' },
  { test: /^section/,                    to: 'layout/_section.scss' },

  // Components
  { test: /^button/,                     to: 'components/_button.scss' },
  { test: /^cta-card|^cta-banner|^cta-extra/, to: 'components/_card.scss' },
  { test: /^card-row/,                   to: 'components/_card.scss' },
  { test: /^content-feature|^content-banner/, to: 'components/_card.scss' },
  { test: /^pagination/,                 to: 'components/_card.scss' },
  { test: /^timeline-card|^timeline$|^timeline-/, to: 'components/_card.scss' },
  { test: /^gallery/,                    to: 'components/_media.scss' },
  { test: /^image|^video/,               to: 'components/_media.scss' },
  { test: /^faqs/,                       to: 'components/_accordion.scss' },
  { test: /^form_|^form-|^w-form/,       to: 'components/_form.scss' },
  { test: /^w-slider|^slider/,           to: 'components/_slider.scss' },

  // Pages
  { test: /^hero|^home-about/,           to: 'pages/_home.scss' },
  { test: /^blog-list|^blog-post-header/, to: 'pages/_blog.scss' },
  { test: /^contact-list/,               to: 'pages/_contact.scss' },
  { test: /^flavor-feature/,             to: 'pages/_flavor-detail.scss' },
  { test: /^spearfish-flavors-feature|^spearfish-flavors-features/, to: 'pages/_flavors.scss' },
  { test: /^about-grid|^about/,          to: 'pages/_about.scss' },
  { test: /^layout-feature-extra|^layout-split|^layout-icon-list|^layout-feature/, to: 'layout/_section.scss' },
  { test: /^search-banner|^result|^results-/, to: 'pages/_scoop-it-forward.scss' },
  { test: /^alert-banner|^banner$/,      to: 'layout/_section.scss' },

  // Base / abstracts
  { test: /^heading-style-/,             to: 'base/_typography.scss' },
  { test: /^text-(align|size|style|color|weight|rich)/, to: 'base/_typography.scss' },
  { test: /^padding-|^margin-|^spacer-|^icon-(embed|1x1)|^max-width-|^min-width-|^align-|^show-|^hide-|^hide$|^container-|^background-color-|^z-index-|^cursor-|^overflow-/, to: 'base/_utilities.scss' },
  { test: /^color-scheme/,               to: 'abstracts/_color-schemes.scss' },
  { test: /^global-styles|^style-overrides/, to: 'abstracts/_color-schemes.scss' },

  // Already-clean component-ish names that fit nowhere else
  { test: /^div-block|^body$|^main-wrapper|^page-wrapper|^page-padding/, to: 'base/_utilities.scss' },
  { test: /^collage|^divider|^tag$|^banner$|^iconify|^component-instructions|^utility_/, to: 'base/_utilities.scss' },
];

const FALLBACK = 'vendor/_legacy.scss';

// -----------------------------------------------------------------------------
// Tokenizer — yields top-level CSS items as { kind, text, opener?, body? }
// -----------------------------------------------------------------------------
function* tokenize(src) {
  let i = 0;
  const n = src.length;
  while (i < n) {
    // Skip whitespace
    while (i < n && /\s/.test(src[i])) i++;
    if (i >= n) break;

    // Skip comments (preserve in output by emitting as their own item)
    if (src[i] === '/' && src[i + 1] === '*') {
      const end = src.indexOf('*/', i + 2);
      if (end === -1) { i = n; break; }
      yield { kind: 'comment', text: src.slice(i, end + 2) };
      i = end + 2;
      continue;
    }

    const start = i;
    let depth = 0;
    let inString = null;
    let kind = src[i] === '@' ? 'atrule' : 'rule';
    while (i < n) {
      const ch = src[i];
      if (inString) {
        if (ch === inString && src[i - 1] !== '\\') inString = null;
      } else if (ch === '"' || ch === "'") {
        inString = ch;
      } else if (ch === '{') {
        depth++;
      } else if (ch === '}') {
        depth--;
        if (depth === 0) { i++; break; }
      } else if (ch === ';' && depth === 0) {
        i++;
        break;
      }
      i++;
    }
    yield { kind, text: src.slice(start, i).trim() };
  }
}

// Split a rule's "selector { body }" into parts.
function splitSelectorBody(text) {
  const open = text.indexOf('{');
  if (open === -1) return null;
  const close = text.lastIndexOf('}');
  if (close === -1) return null;
  const selector = text.slice(0, open).trim();
  const body = text.slice(open + 1, close);
  return { selector, body, raw: text };
}

// Split @media (...) { rules } into its inner items, returns { prelude, inner }
function splitAtRule(text) {
  const open = text.indexOf('{');
  if (open === -1) return null;
  const close = text.lastIndexOf('}');
  if (close === -1) return null;
  const prelude = text.slice(0, open).trim();
  const body = text.slice(open + 1, close);
  return { prelude, body };
}

// Find the "primary class" of a selector list — the first .classname seen.
function primaryClass(selector) {
  // Iterate selector list (split by top-level commas)
  const list = selector.split(',').map(s => s.trim());
  for (const sel of list) {
    const m = sel.match(/\.([A-Za-z_][\w-]*)/);
    if (m) return m[1];
  }
  return null;
}

function destinationFor(selector) {
  const cls = primaryClass(selector);
  if (!cls) return FALLBACK;
  for (const r of ROUTES) {
    if (r.test.test(cls)) return r.to;
  }
  return FALLBACK;
}

// -----------------------------------------------------------------------------
// Bucket assembly
// -----------------------------------------------------------------------------
const buckets = new Map(); // path -> string (appended)

function append(dest, text) {
  if (!buckets.has(dest)) buckets.set(dest, '');
  buckets.set(dest, buckets.get(dest) + text + '\n');
}

const src = fs.readFileSync(LEGACY_CSS, 'utf8');
const items = [...tokenize(src)];

let routedCount = 0;
let preservedRoot = []; // @font-face, @import, :root rules — kept at file top of base/_typography.scss / abstracts

for (const item of items) {
  if (item.kind === 'comment') {
    // Skip comments; they were Webflow watermarks anyway.
    continue;
  }

  if (item.kind === 'atrule') {
    const at = splitAtRule(item.text) || { prelude: item.text, body: '' };
    const head = at.prelude.split(/\s+/)[0];

    if (head === '@media' && at.body) {
      // Split inner rules and route each individually, wrapping in @media.
      for (const inner of tokenize(at.body)) {
        if (inner.kind === 'rule') {
          const sb = splitSelectorBody(inner.text);
          if (!sb) continue;
          const dest = destinationFor(sb.selector);
          const wrapped = `${at.prelude} {\n  ${sb.selector} {${sb.body}}\n}`;
          append(dest, wrapped);
          routedCount++;
        } else if (inner.kind === 'atrule') {
          // Nested at-rule (rare) — keep as-is in legacy bucket.
          const wrapped = `${at.prelude} {\n  ${inner.text}\n}`;
          append(FALLBACK, wrapped);
        }
      }
    } else if (head === '@font-face' || head === '@import') {
      append('base/_typography.scss', item.text);
      routedCount++;
    } else if (head === '@keyframes' || head === '@-webkit-keyframes') {
      append('base/_utilities.scss', item.text);
      routedCount++;
    } else {
      append(FALLBACK, item.text);
    }
    continue;
  }

  // Regular rule
  const sb = splitSelectorBody(item.text);
  if (!sb) continue;
  // Pseudo-only selectors (e.g. :root, body, html) → typography/utilities
  const cls = primaryClass(sb.selector);
  if (!cls) {
    // No class — root-level, element-level, or webflow base rule.
    if (/:root|^html|^body/.test(sb.selector)) {
      append('base/_typography.scss', item.text);
    } else {
      append('base/_utilities.scss', item.text);
    }
    routedCount++;
    continue;
  }
  const dest = destinationFor(sb.selector);
  append(dest, item.text);
  routedCount++;
}

// -----------------------------------------------------------------------------
// Write buckets
// -----------------------------------------------------------------------------
console.log(`Routed ${routedCount} items into ${buckets.size} partials:`);
for (const [dest, content] of [...buckets.entries()].sort()) {
  const fullPath = path.join(SCSS_ROOT, dest);
  fs.mkdirSync(path.dirname(fullPath), { recursive: true });
  // Append to existing partial (skeleton stub remains as the first line)
  const existing = fs.existsSync(fullPath) ? fs.readFileSync(fullPath, 'utf8') : '';
  const banner = `\n// ----- migrated from leones-creamery.webflow.css (split-legacy-css.mjs) -----\n`;
  fs.writeFileSync(fullPath, existing + banner + content);
  const ruleCount = (content.match(/\{/g) || []).length;
  console.log(`  ${dest.padEnd(36)} +${ruleCount} rules`);
}

// Empty the legacy CSS
fs.writeFileSync(LEGACY_CSS, '');
console.log(`\nEmptied: ${path.relative(REPO_ROOT, LEGACY_CSS)}`);

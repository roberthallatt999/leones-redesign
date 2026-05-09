#!/usr/bin/env node
// =============================================================================
// rename.mjs — Webflow class-rename engine
//
// Reads webflow/refactor/class-map.json and rewrites class names in HTML and
// CSS/SCSS files per the BEM rules in the design doc (spec section 3, Pass 2).
//
// Usage:
//   node webflow/refactor/rename.mjs [file1 file2 ...]
//
// With no args, processes the default set:
//   - webflow/*.html
//   - webflow/src/scss/**/*.scss
//   - webflow/css/leones-creamery.webflow.css
//
// Idempotent: running twice is a no-op.
// =============================================================================

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname  = path.dirname(__filename);
const REPO_ROOT  = path.resolve(__dirname, '..', '..');
const MAP_PATH   = path.join(__dirname, 'class-map.json');
const REPORT_PATH= path.join(__dirname, 'rename-report.txt');

const map = JSON.parse(fs.readFileSync(MAP_PATH, 'utf8'));

// Sort block keys longest-first for prefix matching (so 'home_about' beats
// 'home' if both were present).
const BLOCK_KEYS    = Object.keys(map.blocks).sort((a, b) => b.length - a.length);
const UNTOUCHED_SET = new Set(map.untouched);
const UTILITY_SET   = new Set(map.utilities);
const MODIFIERS     = map.modifiers;

// Already-renamed detection: a token that exactly matches a NEW block name,
// or starts with `<newBlock>__` or `<newBlock>--`, is treated as already
// renamed — no further work, no warning. Keeps the script idempotent on
// repeated runs.
const NEW_BLOCK_NAMES = new Set(Object.values(map.blocks));
const NEW_BLOCK_LIST  = [...NEW_BLOCK_NAMES].sort((a, b) => b.length - a.length);
function isAlreadyRenamed(tok) {
  if (NEW_BLOCK_NAMES.has(tok)) return true;
  for (const nb of NEW_BLOCK_LIST) {
    if (tok.startsWith(nb + '__') || tok.startsWith(nb + '--')) return true;
  }
  // BEM-shaped tokens (contain `__` or `--`) are treated as already-handled,
  // even when their base wasn't in the block map. This keeps re-runs quiet.
  if (tok.includes('__') || tok.includes('--')) return true;
  return false;
}

// "Webflow-library-shaped" heuristic: classes ending with a digit suffix
// after letters (footer3, layout413), or compound underscore namespaces
// (home_about, extra_layout). Only these get the unmatched-warning treatment;
// everything else is silently passed through (likely a project utility).
function looksLikeWebflowLibToken(t) {
  return /^[a-z]+\d/.test(t) || t.includes('_');
}

const replacements = [];   // {file, line, before, after, kind}
const warnings     = [];   // {file, line, token, reason}

// ----------------------------------------------------------------------------
// Token-level transform (for HTML class= attributes)
// ----------------------------------------------------------------------------
// Returns an array of new tokens replacing the input token. Modifier tokens
// emit BOTH the companion-block base AND the BEM modifier form per spec.
function transformToken(token, lastBlock) {
  if (UNTOUCHED_SET.has(token)) return { tokens: [token], isBlock: false };
  if (UTILITY_SET.has(token))   return { tokens: [token], isBlock: false };
  if (isAlreadyRenamed(token))  return { tokens: [token], isBlock: NEW_BLOCK_NAMES.has(token) };

  if (MODIFIERS[token]) {
    if (lastBlock) {
      const suffix = MODIFIERS[token];
      return { tokens: [lastBlock + suffix], isBlock: false, modifier: true };
    }
    return { tokens: [token], isBlock: false, warn: 'modifier-no-companion' };
  }

  for (const k of BLOCK_KEYS) {
    const newBlock = map.blocks[k];
    if (token === k || token === k + '_component') {
      return { tokens: [newBlock], isBlock: true };
    }
    if (token.startsWith(k + '_')) {
      const rest = token.slice(k.length + 1);
      return { tokens: [newBlock + '__' + rest], isBlock: true };
    }
  }

  return {
    tokens: [token], isBlock: false,
    warn: looksLikeWebflowLibToken(token) ? 'unmatched' : null,
  };
}

// Process the value of a class="..." attribute.
function transformClassAttr(value, fileLabel, lineNum) {
  const inputTokens = value.split(/\s+/).filter(Boolean);
  const out = [];
  const seen = new Set();
  let lastClass = null;

  for (const t of inputTokens) {
    const r = transformToken(t, lastClass);
    if (r.warn) {
      warnings.push({ file: fileLabel, line: lineNum, token: t, reason: r.warn });
    }
    for (const nt of r.tokens) {
      if (!seen.has(nt)) {
        seen.add(nt);
        out.push(nt);
      }
    }
    // Companion-block tracking: any non-modifier output token is a candidate.
    if (!r.modifier && r.tokens.length > 0) {
      lastClass = r.tokens[0];
    }
  }

  return out.join(' ');
}

// ----------------------------------------------------------------------------
// HTML processing
// ----------------------------------------------------------------------------
function processHtml(filePath) {
  const src = fs.readFileSync(filePath, 'utf8');
  const lines = src.split('\n');
  const fileLabel = path.relative(REPO_ROOT, filePath);

  let modified = false;
  const newLines = lines.map((line, idx) => {
    return line.replace(
      /(\sclass\s*=\s*)("([^"]*)"|'([^']*)')/g,
      (full, prefix, quoted, dq, sq) => {
        const orig = dq !== undefined ? dq : sq;
        const newVal = transformClassAttr(orig, fileLabel, idx + 1);
        if (newVal === orig) return full;
        modified = true;
        replacements.push({
          file: fileLabel, line: idx + 1, before: orig, after: newVal, kind: 'html-class'
        });
        const quote = dq !== undefined ? '"' : "'";
        return `${prefix}${quote}${newVal}${quote}`;
      }
    );
  });

  if (modified) fs.writeFileSync(filePath, newLines.join('\n'));
  return modified;
}

// ----------------------------------------------------------------------------
// CSS / SCSS processing
// ----------------------------------------------------------------------------
// Strategy: walk every `.classname` class selector that appears outside of a
// string or url() context, and rewrite it. This is regex-based but bounded —
// we match `.<ident>` patterns where <ident> is a valid CSS class identifier.
//
// Modifier handling: when the script sees a compound selector of the form
//   `.<oldBlock>_<rest>.is-X`
// it rewrites both classes — the block becomes `<newBlock>__<rest>` and the
// modifier becomes `<newBlock>__<rest>--<suffix>`. Standalone `.is-X` (with no
// adjacent block class in the chain) is left alone and warned.
function processCss(filePath) {
  const src = fs.readFileSync(filePath, 'utf8');
  const fileLabel = path.relative(REPO_ROOT, filePath);

  // Find compound class selector chains (one or more `.token` joined directly).
  // E.g. `.footer3_link.is-secondary` or `.cta39_card .blog35_item` (the latter
  // is two separate chains).
  // Match each chain: `\.[A-Za-z_][\w-]*(?:\.[A-Za-z_][\w-]*)*`
  let modified = false;
  let lineCounter = 1;
  let lineStarts = [0];
  for (let i = 0; i < src.length; i++) {
    if (src.charCodeAt(i) === 10) lineStarts.push(i + 1);
  }
  function lineFromIdx(idx) {
    // Binary search lineStarts
    let lo = 0, hi = lineStarts.length - 1;
    while (lo < hi) {
      const mid = (lo + hi + 1) >> 1;
      if (lineStarts[mid] <= idx) lo = mid;
      else hi = mid - 1;
    }
    return lo + 1;
  }

  // Mask out url(...), 'strings', "strings", and /* comments */ so we don't
  // misinterpret tokens inside them as class selectors. We replace those
  // regions with same-length runs of spaces, then run the chain regex against
  // the masked copy and apply offsets back to src.
  function maskNonSelectors(s) {
    let m = s;
    const masks = [
      /url\([^)]*\)/g,
      /"(?:\\.|[^"\\])*"/g,
      /'(?:\\.|[^'\\])*'/g,
      /\/\*[\s\S]*?\*\//g,
      /\/\/[^\n]*/g,
    ];
    for (const re of masks) {
      m = m.replace(re, match => ' '.repeat(match.length));
    }
    return m;
  }

  function transformChain(chain, offset) {
    const tokens = chain.split('.').slice(1);
    const newTokens = [];
    let lastBlockBase = null;
    for (const t of tokens) {
      if (UNTOUCHED_SET.has(t)) { newTokens.push(t); continue; }
      if (UTILITY_SET.has(t))   { newTokens.push(t); lastBlockBase = t; continue; }
      if (isAlreadyRenamed(t))  { newTokens.push(t); lastBlockBase = t; continue; }
      if (MODIFIERS[t]) {
        if (lastBlockBase) {
          newTokens.push(lastBlockBase + MODIFIERS[t]);
        } else {
          newTokens.push(t);
          warnings.push({ file: fileLabel, line: lineFromIdx(offset), token: t, reason: 'modifier-no-companion-in-css-chain' });
        }
        continue;
      }
      let matched = false;
      for (const k of BLOCK_KEYS) {
        const newBlock = map.blocks[k];
        if (t === k || t === k + '_component') {
          newTokens.push(newBlock); lastBlockBase = newBlock; matched = true; break;
        }
        if (t.startsWith(k + '_')) {
          const rest = t.slice(k.length + 1);
          const newClass = newBlock + '__' + rest;
          newTokens.push(newClass); lastBlockBase = newClass; matched = true; break;
        }
      }
      if (!matched) {
        newTokens.push(t);
        if (looksLikeWebflowLibToken(t)) {
          warnings.push({ file: fileLabel, line: lineFromIdx(offset), token: t, reason: 'unmatched-in-css' });
        }
      }
    }
    return '.' + newTokens.join('.');
  }

  const masked = maskNonSelectors(src);
  const chainRe = /\.[A-Za-z_][\w-]*(?:\.[A-Za-z_][\w-]*)*/g;

  let result = '';
  let cursor = 0;
  for (const m of masked.matchAll(chainRe)) {
    const start = m.index;
    const end   = start + m[0].length;
    const chain = src.slice(start, end);
    result += src.slice(cursor, start);
    const rewritten = transformChain(chain, start);
    if (rewritten !== chain) {
      modified = true;
      replacements.push({ file: fileLabel, line: lineFromIdx(start), before: chain, after: rewritten, kind: 'css-chain' });
    }
    result += rewritten;
    cursor = end;
  }
  result += src.slice(cursor);

  if (modified) fs.writeFileSync(filePath, result);
  return modified;
}

// ----------------------------------------------------------------------------
// File discovery
// ----------------------------------------------------------------------------
function defaultFiles() {
  const list = [];
  const htmlDir = path.join(REPO_ROOT, 'webflow');
  for (const f of fs.readdirSync(htmlDir)) {
    if (f.endsWith('.html')) list.push(path.join(htmlDir, f));
  }
  // SCSS partials (recursive)
  function walk(dir) {
    for (const ent of fs.readdirSync(dir, { withFileTypes: true })) {
      const p = path.join(dir, ent.name);
      if (ent.isDirectory()) walk(p);
      else if (ent.name.endsWith('.scss')) list.push(p);
    }
  }
  const scssDir = path.join(REPO_ROOT, 'webflow', 'src', 'scss');
  if (fs.existsSync(scssDir)) walk(scssDir);
  // Legacy CSS file
  const legacy = path.join(REPO_ROOT, 'webflow', 'css', 'leones-creamery.webflow.css');
  if (fs.existsSync(legacy)) list.push(legacy);
  return list;
}

// ----------------------------------------------------------------------------
// Main
// ----------------------------------------------------------------------------
const args = process.argv.slice(2);
const targets = args.length ? args.map(a => path.resolve(a)) : defaultFiles();

let touched = 0;
for (const f of targets) {
  if (!fs.existsSync(f)) {
    console.error(`Skipping missing: ${f}`);
    continue;
  }
  const ext = path.extname(f);
  let changed = false;
  if (ext === '.html' || ext === '.htm') changed = processHtml(f);
  else if (ext === '.scss' || ext === '.css') changed = processCss(f);
  else { console.error(`Skipping unsupported: ${f}`); continue; }
  if (changed) touched++;
}

// Write report (full snapshot — overwritten each run)
const ts = new Date().toISOString();
const header = `=== rename.mjs report @ ${ts} ===\n`
             + `targets: ${targets.length} file(s) — ${touched} modified\n`
             + `replacements: ${replacements.length}, warnings: ${warnings.length}\n\n`;
const repLines = replacements.map(r =>
  `[${r.kind}] ${r.file}:${r.line}\n  - ${r.before}\n  + ${r.after}`
).join('\n');
const warnLines = warnings.length
  ? '\n\n-- warnings --\n' + warnings.map(w =>
      `${w.file}:${w.line} ${w.reason} -> ${w.token}`
    ).join('\n')
  : '';
fs.writeFileSync(REPORT_PATH, header + repLines + warnLines + '\n');

console.log(`rename.mjs: ${touched} files changed, ${replacements.length} replacements, ${warnings.length} warnings`);
console.log(`Report: ${path.relative(REPO_ROOT, REPORT_PATH)}`);

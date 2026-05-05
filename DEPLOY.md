# Deploying leonescreamery

Deployments use **GitHub Actions** with an atomic-deploy pattern (releases/, shared/, current symlink). Branch-to-environment mapping:

| Branch  | Environment | Trigger                                                 |
| ------- | ----------- | ------------------------------------------------------- |
| develop | Staging     | Auto-deploy on push                                     |
| main    | Production  | Manual (`workflow_dispatch`) — auto-deploy gated until staging is proven |

> **Status:** Both staging and production workflow files exist. Staging is wired up for push-to-deploy. Production is currently `workflow_dispatch` only — to enable auto-deploy on merge to `main`, uncomment the `push:` trigger in `.github/workflows/lc-production.yml`.

## Architecture

```
${BASE_PATH}/                       (e.g., /home/forge/lc.digitaldesigns.dev)
├── releases/
│   ├── 20260505083214/             (one per deploy, UTC timestamp)
│   ├── 20260505101502/
│   └── ...                         (last 7 retained, older auto-pruned)
├── shared/
│   ├── images/                     (user uploads + static assets)
│   └── lcmin/user/
│       ├── cache/                  (writable EE cache)
│       └── config/config.local.php (env-specific config)
└── current → releases/<latest>     (atomic symlink; webroot points here)
```

Each release directory contains the synced code with three symlinks back into `shared/`:

- `images/` → `shared/images/`
- `lcmin/user/cache/` → `shared/lcmin/user/cache/`
- `lcmin/user/config/config.local.php` → `shared/lcmin/user/config/config.local.php`

## Pre-Deploy Checklist (Local)

- [ ] Feature branches merged to `develop` (staging) / `main` (production)
- [ ] `npm run prod` has been run locally
  - Compiles `src/assets/scss/main.scss` → compressed + autoprefixed CSS
  - Renames the output to `css/custom-<timestamp>.css`
  - Updates the `<link>` reference in `lcmin/user/templates/default_site/layouts.group/_layout_main.html`
- [ ] Both the new `css/custom-<timestamp>.css` AND the modified `_layout_main.html` are committed in the same commit. Old `custom-<timestamp>.css` files can be deleted from git.
- [ ] `git push origin develop` (staging) — deploy is automatic from this point.

> **Most common deploy bug:** forgetting to commit the regenerated CSS or the updated layout. The deploy succeeds, but the site loads naked. If a deploy looks unstyled, check this first.

## Deploy (Staging) — automatic

1. Push to `develop`
2. GitHub Actions runs `lc-staging.yml`:
   - Validates required secrets
   - SSH agent setup with the dedicated deploy key
   - Generates a UTC release timestamp
   - Creates `${STAGE_BASE_PATH}/releases/<timestamp>/` on the server
   - Takes a `mysqldump` of the staging DB into `releases/<timestamp>/.db-backup.sql.gz`
   - rsyncs the working tree to the release dir (excluding `.git/`, `.github/`, `lcmin/user/cache/`, `lcmin/user/config/config.local.php`, `images/`, `.ddev/`, `.vscode/`, `node_modules/`, `src/assets/certs/`, `LocalValetDriver.php`)
   - Records the commit SHA in `releases/<timestamp>/.release-info`
   - Runs `lc-staging.sh` on the server: creates symlinks to `shared/`, sets file permissions, clears EE cache via `eecli.php`
   - Atomic symlink swap: `current` → `releases/<timestamp>`
   - Prunes older releases (keeps the most recent 7)
3. Watch the run live: GitHub repo → Actions tab → "Deploy Leone's Creamery to Staging"

## Deploy (Production) — manual

1. Merge to `main` and push
2. GitHub repo → Actions → "Deploy Leone's Creamery to Production" → **Run workflow** → branch `main` → leave rollback blank → **Run**
3. Same flow as staging, with prod secrets

(Once staging is fully validated, you can uncomment the `push: main` trigger in the workflow to make production auto-deploy.)

## Rollback

Each release stores the commit hash in `.release-info` and a DB snapshot in `.db-backup.sql.gz`. To roll back:

1. Identify the commit hash you want to roll back to (must already have been deployed — i.e. has its own `releases/<ts>/` directory)
2. GitHub repo → Actions → the appropriate workflow → **Run workflow**
3. Provide the commit hash in the `rollback` input → **Run**

The rollback workflow:
- Looks up the matching release directory by commit hash
- Restores the DB from the **current** release's backup (so you don't lose data created since the release you're rolling back to)
- Re-establishes shared/ symlinks in the rollback release
- Atomic symlink swap: `current` → `releases/<rollback-ts>`

> Rollback restores the DB from the *current* release's snapshot — meaning any DB writes since that release are preserved. If you want to restore an older DB state, restore manually from the older release's `.db-backup.sql.gz`.

## Post-Deploy Verification

After every deploy:

1. Load the site
2. View page source — confirm the `<link>` tag points at the **new** `custom-<timestamp>.css` filename
3. Hard-refresh (`Cmd-Shift-R`) — confirm visual changes are present
4. Click into 1–2 dynamic pages (`/flavors`, `/contact`) — confirm they render

If any page renders with an empty `<body>`, suspect a Stash cache / parser-order issue (see `MEMORY.md` and `.claude/rules/expressionengine-patterns.md`). The deploy already runs `eecli.php cache:clear`, but Stash may need a CP-side flush.

## Secrets

Deployment secrets live in two places:

| Where | What |
| --- | --- |
| `.github/secrets.yaml` (gitignored, your laptop) | Source of truth — edited by humans |
| GitHub repo → Settings → Secrets and variables → Actions | Runtime — synced from secrets.yaml via `manage-secrets.sh` |

To update secrets:

```bash
# Edit your local file
$EDITOR .github/secrets.yaml

# Push the changes to GitHub
bash .github/deploy-scripts/manage-secrets.sh
```

`secrets.yaml.example` is the template — copy it to `secrets.yaml` and fill in real values. Required: `gh` CLI (`brew install gh`) and `yq` (`brew install yq`).

## First-Time Setup

If you're setting this up on a server that doesn't yet have the atomic-deploy structure (e.g., still on the old Forge direct-deploy):

1. `bash scripts/migrate-staging-prepare.sh` — backup, generate deploy key, create skeleton on server
2. `cp .github/secrets.yaml.example .github/secrets.yaml` — fill in values, including the printed deploy key
3. `bash .github/deploy-scripts/manage-secrets.sh` — uploads secrets to GitHub
4. Push to `develop` — first deploy creates `releases/<ts>/` and `current` symlink
5. `bash scripts/migrate-staging-sync-images.sh` — rsync existing `images/` into `shared/`
6. **In Forge UI:** change site web directory from `${BASE_PATH}` to `${BASE_PATH}/current`
7. Verify the site loads
8. `bash scripts/migrate-staging-cleanup.sh` — delete legacy root files (with confirmation)

After step 8, every push to `develop` auto-deploys to staging.

## Common Gotchas

| Symptom | Likely Cause | Fix |
| --- | --- | --- |
| Site loads unstyled | Forgot to commit regenerated `css/custom-<ts>.css` or updated layout | Re-run `npm run prod`, commit both files, push, redeploy |
| Stylesheet 404s | Layout points at a `custom-<ts>.css` filename that wasn't committed | Same as above |
| Pages render with empty `<body>` | Stash cache / `global:env` mismatch | Clear EE + Stash caches; verify production env config |
| New static image in repo doesn't appear after deploy | `images/` is excluded from rsync — manual copy required | `scp <file> leones-dev:${BASE_PATH}/shared/images/` |
| Workflow fails on "Validate Required Secrets" | Secret name mismatch or missing value | `bash .github/deploy-scripts/manage-secrets.sh` and confirm `gh secret list` shows expected names |
| Workflow can't SSH | Public key not on server, or `SSH_PRIVATE_KEY` secret malformed | Re-run `migrate-staging-prepare.sh` (idempotent), regenerate secret |
| `current` symlink broken after partial deploy | A failed deploy may leave `current` pointing at the previous release — usually self-heals on next successful deploy | Manually re-symlink if needed: `ssh leones-dev "ln -sfn ${BASE_PATH}/releases/<ts> ${BASE_PATH}/current"` |

## Related Documentation

- [`CLAUDE.md`](CLAUDE.md) — project overview, EE conventions
- [`MEMORY.md`](MEMORY.md) — session context, decisions
- [`.claude/rules/expressionengine-patterns.md`](.claude/rules/expressionengine-patterns.md) — Stash, channel queries
- [`.github/secrets.yaml.example`](.github/secrets.yaml.example) — secrets template

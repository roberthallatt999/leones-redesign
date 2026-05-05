# Deploying leonescreamery

This site is deployed to a Laravel Forge–managed server. Deploys are triggered manually from the Forge control panel after pushing to `main`.

> **Future work:** This Forge-based deploy is planned to be replaced by a custom GitHub Actions workflow (matching the setup used on other projects). The instructions below describe the current process.

## Pre-Deploy Checklist (Local)

Run through this before pushing to `main`.

- [ ] All feature branches merged to `develop`, `develop` merged to `main` (per `CLAUDE.md` git workflow)
- [ ] `npm run prod` has been run locally — this:
  - Compiles `src/assets/scss/main.scss` → `css/custom.css` (compressed, autoprefixed)
  - Renames the output to `css/custom-<timestamp>.css`
  - Updates the `<link rel="stylesheet" href="/css/custom-<timestamp>.css">` reference in `lcmin/user/templates/default_site/layouts.group/_layout_main.html`
- [ ] Both the new `css/custom-<timestamp>.css` **and** the modified `_layout_main.html` are committed in the same commit. Old `custom-<timestamp>.css` files from prior runs can be deleted from git.
- [ ] `git push origin main`

> **Most common deploy bug:** forgetting to commit the regenerated CSS or the updated layout. The page will load with no styles or with stale styles. If a deploy looks "naked," check this first.

## Deploy (Forge)

1. Log into the Laravel Forge control panel
2. Select the leonescreamery server, then the site
3. Open the **Deployments** tab
4. Click **Deploy Now**
5. Wait for the deploy to report success

The Forge deploy script lives in the Forge UI (Site → Deployment → Deploy Script). It is intentionally not duplicated in this repo because it is expected to be replaced by GitHub Actions and would drift from reality.

## Post-Deploy Verification

Manual sanity check after every deploy:

1. Load `https://leonescreamery.com/` — confirm it renders, not blank
2. View page source and confirm the `<link>` tag points at the **new** `custom-<timestamp>.css` filename
3. Hard-refresh (`Cmd-Shift-R`) and confirm visual changes are present
4. Click into 1–2 dynamic pages (e.g., `/flavors`, `/contact`) — confirm they render

If any page renders with an empty `<body>` or missing content, suspect a Stash cache / parser-order issue (see `MEMORY.md` and `.claude/rules/expressionengine-patterns.md`). Clear caches first.

## Clearing Caches

If content looks stale or templates appear broken after deploy:

```bash
# SSH into the Forge server first, then from the site root:
php lcmin/ee/eecli.php cache:clear all
```

This clears EE's template / page / driver caches. The Stash add-on cache can also be cleared from the EE Control Panel under **Add-ons → Stash → Clear Cache**.

The Forge deploy script may already include a cache-clear step — verify in the Forge UI before running it manually.

## Rollback

Forge retains the history of each deploy.

1. In Forge, open **Deployments**
2. Find the previous successful deploy
3. Click **Rollback** on that entry

This re-deploys the prior commit. After rollback, do another **Post-Deploy Verification** pass to confirm the previous version renders correctly.

> Rollback does **not** roll back database state. Any DB changes made between the bad deploy and the rollback (new entries, member registrations, form submissions) will remain. If the bad deploy ran a destructive DB migration, restore from the latest snapshot instead.

## Common Gotchas

| Symptom | Likely Cause | Fix |
|---|---|---|
| Site loads unstyled | Forgot to commit regenerated `css/custom-<ts>.css` or updated layout | Re-run `npm run prod` locally, commit both files, push, deploy |
| Stylesheet 404s | Layout points at a `custom-<ts>.css` filename that wasn't committed | Same as above |
| Pages render with empty `<body>` | Stash cache / `global:env` mismatch (see `MEMORY.md`) | Clear EE + Stash caches; verify production env config |
| New EE add-on not appearing | Add-on files committed but not installed in DB | Log into CP → Add-ons → install |
| Stale content after deploy | EE template / page cache | Run `cache:clear all` (see above) |

## Related Documentation

- [`CLAUDE.md`](CLAUDE.md) — project overview, dev commands, EE conventions
- [`MEMORY.md`](MEMORY.md) — session-to-session context and decisions
- [`.claude/rules/expressionengine-patterns.md`](.claude/rules/expressionengine-patterns.md) — Stash, channel queries, parser-order
- [`.claude/rules/scss-standards.md`](.claude/rules/scss-standards.md) — build pipeline rules

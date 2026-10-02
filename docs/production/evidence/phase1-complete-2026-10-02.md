# Phase 1 Completion Evidence

**Date:** 2 October 2026  
**Scope:** Local Laravel + Statamic Core foundation, Docker development services, HTTPS/HMR, health/routes, root CI, and non-destructive Windows developer commands. No production access, cloud changes, production migration, real email, or deployment was performed.

## Implemented and verified

- Development uses the existing Laravel 13.34.0 + Statamic Core 6.34.0 installation; no Statamic Pro feature or license bypass was added.
- Vite, CI, and `.nvmrc` use Node 24.10.0. Compose pins the multi-platform Alpine image by digest `sha256:775ba24d35a13e74dedce1d2af4ad510337b68d8e22be89e0ce2ccc299329083` and keeps its musl-specific dependency volume separate from the former Node 22 volume.
- `scripts/start-vite.sh` checks the committed `package-lock.json` hash and required binaries before running `npm ci`; it does not reinstall on every boot when the lockfile is unchanged.
- The root CI workflow now runs PHP tests on MySQL 8.4 plus frontend typecheck, the dependency-light domain suite, Vitest, and build. GitHub-hosted CI was not run because no push was authorized.
- GitHub Pages is manual-trigger only and requires the `synthetic-demo` target input. It remains a demo workflow, not a production release path.
- `/help` is an explicit Statamic Core `Route::statamic` route rendered through Antlers layout/template files. `/up` is liveness; `/ready` performs `SELECT 1` and reports database unavailable with 503 on failure. `/app`, `/cp`, `/api`, and auth paths remain separate.
- The backend uses PHP's built-in server with Laravel's bundled router as the direct container process. This serves static files and application routes and survives container restart.
- `scripts/dev/dev.ps1` provides `init`, `up`, `down`, `status`, `test`, `build`, `reset-test`, and `logs`. Initialization creates the `.env` only if absent, generates an app key only when blank, and applies pending migrations. `test` targets only the reserved synthetic `talent_test` schema; `reset-test` is limited to that schema. No command removes volumes, resets the `talent` app database, edits Windows hosts, or installs host certificate trust.

## Commands and actual results

| Command / check | Result |
|---|---|
| `docker manifest inspect node:24.10.0-alpine` and `docker buildx imagetools inspect node:24.10.0-alpine` | Published amd64/arm64 manifest and multi-platform digest verified. |
| `docker compose -f compose.dev.yaml config --quiet` | Pass. |
| `scripts/dev/dev.ps1 init` | Pass; all services started, MySQL healthy, pending-migration command reported `Nothing to migrate`. Existing app key was already configured. |
| `scripts/dev/dev.ps1 test` | Pass: 41 pure-domain checks; 22 frontend Vitest tests; TypeScript check and frontend production build; 36 backend tests / 1,318 assertions against isolated MySQL 8.4 `talent_test`. |
| `scripts/dev/dev.ps1 build` | Pass; compiled the SPA into `backend/public/app` with hashed assets. |
| Container-restart persistence probe | Pass: a synthetic marker in `talent_test` survived MySQL/backend/worker/scheduler/Caddy/Vite restart; the exact marker was then deleted and verified absent. No app DB rows were used for this check. |
| HTTPS route matrix after restart | Pass: `/up` 200, `/ready` 200, `/help` 200, `/app` 200, `/cp` 302, unauthenticated `/api/v1/plans` 401 JSON. |
| Managed browser at `https://talent.taleed.test:9443/` | Pass: SPA rendered; Vite opened `wss://talent.taleed.test:9443` and sent `{"type":"connected"}` after reload. |

The first Statamic request after a full local restart can take up to the installed Core Outpost request timeout in this no-egress environment; the failed ping is cached and subsequent health/content/API requests work. No Statamic vendor files or licensing behavior were changed.

## Not run / remaining boundary

- Installing Caddy's local root certificate into the Windows trust store and editing the hosts file were not performed. The development URL is reachable in this environment, but host trust setup remains a machine-owner action.
- GitHub-hosted CI was not run. No push was made.
- Production containers/infrastructure, production data, DNS/certificate issuance, real SMTP, cloud services, and deployment remain untouched and unauthorized.
- The local app still serves the approved prototype SPA. Replacing its demo auth, persona switcher, and localStorage authority is Phase 4, not Phase 1.
- During early focused verification, a PHPUnit command inherited Compose's `DB_DATABASE=talent` before the isolated `talent_test` schema was established. Laravel `RefreshDatabase` may have recreated that local developer schema. Testing was moved to `talent_test` immediately afterward; no automatic restore was attempted. No production database was involved.

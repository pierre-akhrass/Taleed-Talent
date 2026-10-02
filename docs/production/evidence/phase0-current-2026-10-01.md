# Current Phase 0 Revalidation

**Date:** 1 October 2026
**Scope:** Read-only audit followed by authorized local Phase 1 foundation work

## Verified current state

- The approved React/Vite SPA remains under `taleed-talent-spa/`.
- Active demo roles are Leader, Taleed and Admin; Champion is not seeded or routed.
- A Leader close creates an allowlisted monthly summary; Taleed sees effective shared reports; Admin manages invitations/content.
- No backend existed at the start of this revalidation.
- Local prerequisites were present: PHP 8.4.8, Composer 2.8.9, Docker 28.4.0 and Node 22.17.1.

## Phase 1 work completed

- Added root `backend/` Laravel 13.34.0 application with a real Composer lockfile.
- Added pinned Statamic Core 6.34.0 and initialized local Core configuration/assets without creating a CMS user or production data.
- Added `/up`, `/ready`, `/help` and `/app/{path?}` route boundaries with focused Laravel tests.
- Added `compose.dev.yaml`, a local backend Dockerfile, and MySQL 8.4/Mailpit/Vite service definitions using non-conflicting local ports.
- Added a root CI workflow covering the frontend lockfile/typecheck/build/backend-targeted build and focused Laravel route tests.
- Added `npm run build:backend` to compile the approved React SPA into Laravel `backend/public/app`; Laravel serves that compiled entry at `/app` and fails closed with 503 when it is absent.
- Added a Caddy development HTTPS overlay at `https://talent.taleed.test:9443` with internal TLS, backend route separation and Vite reverse proxy/HMR support. Host mapping and certificate trust remain manual and unperformed.
- Reconciled the nested frontend instructions with the approved production conversion.
- Replaced mutable frontend `latest` declarations with versions from the reviewed npm lockfile; npm lock-only resolution passed with no reported vulnerabilities.

## Actual checks

- `php artisan test --filter=HealthRoutesTest`: passed, 2 tests / 6 assertions.
- After compiled SPA integration, `php artisan test --filter=HealthRoutesTest`: passed, 2 tests / 7 assertions.
- `npm run build:backend`: passed and produced the Laravel `/app` entry/assets.
- `php artisan route:list --path=cp`: confirmed Statamic Core `cp` routes are registered.
- `docker compose -f compose.dev.yaml config`: passed with the Caddy HTTPS service.
- Local Compose runtime: MySQL 8.4 and Mailpit healthy; Laravel backend running; `http://127.0.0.1:8000/up`, `/help`, and `/app` returned 200. Phase 1 uses file-backed cache/session and a synchronous queue until the application schema is established; MySQL remains available for Phase 2.
- `docker compose -f compose.dev.yaml config`: passed.
- Live local Caddy checks with `curl --http1.1 -k --resolve`: HTTPS `/up`, `/help`, and `/app` returned 200; `/cp` returned 302.
- `npm run typecheck`: passed before Phase 1 scaffolding and frontend dependency pinning.
- `npm run build`: passed before Phase 1 scaffolding and frontend dependency pinning.
- Production-style `docker compose build backend` remains unrun to completion because registry/Composer downloads are slow. Local Compose uses the pulled Composer/PHP image with the locked host vendor tree and runs successfully.

## Unresolved Phase 1 gates

- Same-origin React build wiring and local HTTPS routing are complete; Vite HMR through Caddy still needs a browser-level check.
- MySQL-backed Laravel migrations have not been designed or run for application data.
- Root CI has not run remotely; its equivalent local frontend/backend gates pass. Compose restart persistence checks remain unrun.
- No Phase 2 identity/schema work has started.

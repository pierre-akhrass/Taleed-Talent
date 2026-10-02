# Current Phase 2 Evidence

**Date:** 1 October 2026
**Scope:** Local MySQL identity and initial application-session slice

## Implemented

- Composer lock includes Laravel Fortify `v1.40.0`, Sanctum `v4.3.3` and Passkeys `v0.2.1`.
- Local backend runtime builds from `backend/Dockerfile.dev` with `pdo_mysql` and GD.
- Migration `2026_10_01_120000_create_identity_tables.php` is applied to local MySQL 8.4.
- Application users use the `app` Eloquent session guard.
- Statamic CP/web access remains on the independent `statamic` provider/guard.
- Application login, logout, profile, CSRF route, invitation-only registration, isolated password reset, admin-only organization invitations and verified matching-email invitation acceptance are wired.
- Fortify email verification and TOTP routes are enabled for the application guard; staff login is blocked until confirmed MFA is present.
- Explicit organization/invitation policies deny non-admin creation.
- Invitation consumption is transactionally locked and replay-protected; membership insertion supplies its ULID explicitly.

## Checks actually run

From the repository root:

```text
docker compose -f compose.dev.yaml up -d --build backend       PASS
docker compose -f compose.dev.yaml exec -T backend php artisan migrate --force  PASS
docker compose -f compose.dev.yaml exec -T backend php artisan migrate:status  PASS
docker compose -f compose.dev.yaml exec -T backend php artisan route:list --path=sanctum  PASS
docker compose -f compose.dev.yaml exec -T backend php artisan test  PASS: 11 tests, 30 assertions
docker compose -f compose.dev.yaml config --quiet  PASS
docker compose -f compose.dev.yaml restart backend caddy  PASS: migrations persisted
npm run typecheck  PASS
npm run build:backend  PASS
```

## Still unrun or incomplete

- Direct local Vite HMR works on `http://127.0.0.1:5173`, but Caddy's HTTPS websocket upgrade currently returns the Vite document with HTTP 200; this remains a local Phase 1 blocker and needs a later proxy/toolchain fix.
- GitHub-hosted CI has not been executed remotely; its workflow now targets MySQL 8.4 and the local equivalent passes.
- Production-style image build, production infrastructure, cloud access, real SMTP, deployment and production data remain intentionally untouched.
- No production database, cloud resource, real email, deployment or production data was accessed.
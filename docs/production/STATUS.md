# Taleed Talent — delivery plan and status

**Owner / decision maker:** Director (Fadi Zahhar)
**Last updated:** 1 October 2026 — Phases 0–2 local implementation complete; HTTPS HMR proxy limitation recorded
**Audience:** the implementing developer (using Claude Code or Codex) and the director

This file is the developer's single entry point. Read it first, then `AGENTS.md`, `docs/production/MASTER-PROMPT.md` and the specifications it references. **Director decisions in §2 are binding and override any conflicting default in the other specifications.** Update §5 and §7 at the end of every phase.

---

## 1. Where we are

| Phase | Scope | Status | Estimate (dev-days) | Exit evidence |
|---|---|---|---|---|
| 0 | Read-only discovery and architecture proposal | **Done — 30 Sep 2026** | — | §3 of this file |
| 1 | Local foundation: Laravel + Statamic 6 Core, Docker, local HTTPS, root CI | **Complete locally except HTTPS HMR proxy — Compose runtime, worker/scheduler, HTTPS routes, SPA integration, restart persistence and local CI-equivalent checks pass; Caddy/Vite websocket upgrade remains blocked** | 5–7 | App, `/help`, `/cp`, `/up` running on `https://talent.taleed.test:9443`; root CI workflow present |
| 2 | MySQL schema, independent auth guards, invitations, policies, frozen OpenAPI | **Complete locally — MySQL identity schema, independent guards, invitation/reset/verification/MFA boundaries, policies, contract and feature tests pass** | 8–10 | MySQL feature tests for guards, tenants, invitations, MFA/reset; `contracts/openapi.yaml` v1 |
| 3 | Domain API: catalogue, plans, schedules, occurrences, close/reopen | Not started | 10–13 | Concurrency + history tests on MySQL |
| 4 | Connect approved React UI to the API; remove demo personas/localStorage | Not started | 10–13 | Playwright journeys over HTTPS against Laravel/MySQL |
| 5 | Source import/approval, CMS guidance, private features (flagged off), sharing, reports, email | Not started | 12–15 | Privacy negative tests; sharing freeze tests |
| 6 | Production data safety: prod Compose, guards, backup/restore, rollback, ops tests | Not started | 8–11 | Two-release survival, restore rehearsal, failure-mode tests |
| 7 | Provision dedicated VM and release (explicit authorization only) | Not started | 3–4 | Real HTTPS URL, release ID, backup ID, smoke evidence |
| | **Total** | | **56–73 dev-days** | |

**Timeline (one developer, sequential, per decision D14):** about 12–15 working weeks including ~15% contingency. If work starts Monday 5 October 2026, the expected go-live window is **mid-January to late January 2027**. Client content sign-off (D5) and director actions (§6) run in parallel and must not become the critical path.

Private conversations and the well-being wheel ship **built but switched off** (D7); enabling them later is a configuration change after privacy sign-off, not a new phase.

---

## 2. Director decisions (binding, 30 September 2026)

| ID | Topic | Decision | Consequence for implementation |
|---|---|---|---|
| D1 | Production host | **New dedicated GCP VM** for Talent (project `taleed-survey`, region `me-central2`, e2-standard-2 class, separate persistent data disk, own static IP) | Talent owns its own edge on 80/443. Do not modify the Survey or Sustainability VMs. This approves the "extra VM" that AGENTS.md otherwise forbids. Size is re-checked with measured usage in Phase 6. |
| D2 | Public address | **Static IP with a Let's Encrypt IP certificate** (short-lived profile, ~160 h, automated renewal) | Same pattern as Sustainability (`ssl-ip-*` scripts), re-implemented and tested, not copied blindly. `APP_URL=https://<static-ip>`. Host-only cookies named `taleed_talent_session`. Edge rejects other Host headers. Certificate-expiry alert is mandatory. |
| D3 | Backups | **Nightly + pre-release**, encrypted, private GCS bucket in `me-central2`, **30-day retention**, **RPO 24 h / RTO 4 h** | Restore rehearsal before launch is a release gate. Backups older than 30 days expire, consistent with D16. |
| D4 | Release path | **CI builds and tests a pinned image → Artifact Registry via GitHub OIDC (no JSON keys) → named operator runs the deploy script** | No automatic deploy on merge. Deploy script: lock → backup → one migration → swap app services → verify. |
| D5 | Catalogue content | **Verbatim source text** from the seven hash-verified PDFs, imported as drafts; **client content owner approves**; content admin publishes | Remove the 66 generated descriptions from any production path. Source of the import: `Taleed_Talent_Tool_Package/reference/talent_catalogue.v1.draft.json` and `conversation_guide.v1.draft.json` (authorized copy supplied to the developer, never committed with the PDFs). |
| D6 | Well-being bands | **Numeric total and profile only at launch**; no labels | Remove the "higher band" rule from production code/UI/reports. `wellbeing_classification_enabled=false`. Tests at totals 35 and 60 prove no label appears anywhere. |
| D7 | Private features | **Fully built and tested, switched off on the server at launch** until the client privacy owner signs off | Flags `private_conversations_enabled` and `private_wellbeing_enabled` default off; API, jobs and exports deny when off, not just the UI. |
| D8 | Monthly reporting | **A safe organization report is shared automatically with Taleed when a leader closes a monthly plan.** There is no Champion approval, preview or send step. | Generate the frozen allowlisted summary transactionally from the closed revision and make it available to assigned Taleed users; never include private records or participation signals. |
| D9 | Onboarding | **Invitation-only at launch.** The application Admin creates the organization and invites Leaders; there is no Champion role or Champion panel | Invitation acceptance still includes email verification and password setup. Membership target role is always `leader`; the separate Statamic Core account remains a CMS operator, not an application member. |
| D10 | Plans per month | **One active plan per leader per month** | Add unique `(organization_id, owner_user_id, month)` on `plans`. This overrides the "do not add unique owner/month" line in `DATABASE-SCHEMA.md`. Corrections use reopen/re-close revisions. |
| D11 | Two-factor sign-in | **Required** for the application Admin and the Statamic CMS admin; **optional** for Leaders | TOTP + recovery codes via Laravel Fortify (app guard) and Statamic Core's built-in 2FA (CP). Test recovery. |
| D12 | Language | **English only at launch**, RTL-ready layout | No Arabic content claims. Keep the RTL preview as a layout check only. |
| D13 | Repository and Pages | **Company-owned private GitHub repository**; Pages becomes **manual-trigger synthetic demo only** | Director transfers/creates the repo (§6). Phase 1 changes `.github/workflows/deploy-pages.yml` to `workflow_dispatch` only with a demo label. |
| D14 | Team model | **One developer, sequential phases, one AI agent** | One owner of schema, contract, lockfiles and deployment. `PARALLEL-AGENTS.md` is not used unless the director changes this. |
| D15 | Email | **SendGrid with a Talent-specific API key and sender** (e.g. `talent@<company domain>`) with SPF/DKIM | Local development uses Mailpit only. No real email until Phase 7 authorization. |
| D16 | Retention | **Owner-private data erased within 30 days** of a deletion request or account closure; backups age out at 30 days; a deletion list (tombstones) is replayed after any restore | Aggregate shared counts are kept. Implement in Phase 5, test in Phase 6. |

### Still open (do not block development)

| ID | Item | Owner | Blocks |
|---|---|---|---|
| G1 | Client content owner approval of the 72 activities and conversation guide (D5) | Director → client | Publishing content in production (Phase 7) |
| G2 | Client privacy owner approval of notices, retention and access for private features (D7) | Director → client | Switching the private features on (after launch) |
| G3 | Client confirmation of the 3-leader sharing minimum and summary field list (D8) | Director → client | Switching organization sharing on |
| G4 | Sender domain for email (D15): an email domain is still needed even though the app uses an IP address | Director | Phase 7 email |

### D17 — application roles and automatic reporting (1 October 2026)

The application has three product roles: **Leader**, **Taleed**, and **Admin**. Leaders own their plans and private records. The Admin creates organizations and invites Leaders. Taleed reads the safe monthly reports produced automatically when plans close. Remove the Champion workspace, explicit Champion sharing consent, Champion invitation controls and Champion-only routes. Keep the single Statamic Core administrator as a separate CMS identity.

---

## 3. Phase 0 findings (evidence summary)

Verified on 30 September 2026 against the working tree at commit `5e33846`. Nothing was installed, changed, migrated or deployed during Phase 0.

**Repository**
- `taleed-talent-spa/` is a React 18.3.1 / Redux Toolkit 2.12 / Vite 8 / TypeScript 7 SPA with hash routing and all data in `localStorage` (`taleed.talent.prototype.v2`). There is no backend.
- `taleed-talent-spa/AGENTS.md` still forbids a backend. The delivery manifest says it was replaced, but it was not (checksums differ). Fix in Phase 1.
- `.github/workflows/deploy-pages.yml` deploys the demo on every push to `main`. `taleed-talent-spa/.github/workflows/check.yml` is nested, so it never runs.
- `package.json` uses `"latest"` for most packages; the lockfile pins real versions. `.nvmrc`/CI say Node 24; the development Mac has Node 22.17.1.

**Conflicts found in current code (all introduced by commit `ea09cb5`, 16 Sep, titled "changes css")**
1. `src/domain/logic.ts:98-110` adds well-being labels; totals 35 and 60 go to the higher band. UI text in `Wellbeing.tsx:180-181` states this. Resolved by D6.
2. `src/data/catalogue.ts` claims text is "transcribed from the supplied activity sheets". The 72 titles do match the source extraction, but 66 descriptions are generated filler, and nothing is approved. Resolved by D5.
3. `src/features/admin/Admin.tsx` no longer requires an approved source before publishing (the check was deleted). The server must enforce it.
4. `src/app/store.ts:13-26` overwrites stored catalogue data on every page load. Must not be carried into production.

**Other prototype behaviours the backend must fix, not copy**
- Rescheduling deletes unfinished future occurrences and allows two on the same date (`src/app/slices.ts:40-42`). The server keeps them with status `superseded` and enforces one active occurrence per commitment per date.
- Sharing recomputes the payload live and the analyst view is hard-coded to one company (`src/app/selectors.ts:9`). The server uses a frozen preview with a hash, and analyst assignments from the database.
- Persona switching, all-data backup/import and demo reset are demo-only and are removed from production.

**Source material (authorized local copies, never commit)**
- Seven original PDFs in `~/Downloads/FW__Talent_tools/`. All SHA-256 hashes match `source_manifest.json` (S01 Care, S02 Develop, S03 Develop Calendar Template, S04 Conversation Guide, S05 Enable, S06 Recognition Pick-3, S07 Well-being Wheel).
- Draft extractions in `~/Downloads/Taleed_Talent_Tool_Package/reference/`: 72 activities (all `draft_requires_client_approval`), 6 guide steps / 3 questions / 7 follow-ups, and well-being rules with labels disabled.

**Sibling tools (read-only references, do not modify)**
- `~/Documents/GitHub/sustainability-diagnostic-tool`: best reference. Laravel 13 + Statamic 6.28 + Fortify. Independent guards: `web` = Statamic file user (single CMS admin), `app` = Eloquent users. Good deploy lock, volume checks, one-shot migrations, GCS offsite backup. Do not copy `TRUSTED_PROXIES="*"`, non-external volumes or automatic rollback.
- `~/Documents/GitHub/Survey-statamic6`: Laravel 13 + Statamic 6.15, CMS-only users. Weaker operations (no lock, no offsite backup, persisted bootstrap cache). Do not use as a deployment model.
- Both run on separate VMs in GCP project `taleed-survey` (me-central2) with Let's Encrypt IP certificates, according to their documentation (not verified live).
- Local ports already used by siblings: 80, 443 (Sustainability HTTPS overlay), 1025, 1026, 3306, 3307, 4443, 5173, 6379, 8000, 8025, 8026, 8080, 8443.

**Candidate dependency matrix** (from package registry metadata; must be confirmed by a real `composer`/`npm` resolve in Phase 1): PHP 8.4, Laravel 13.x, `statamic/cms` 6.34.x (Core, Pro disabled), Laravel Sanctum 4.3.x, Laravel Fortify 1.x, MySQL 8.4 LTS pinned by image digest, Node 24, React 18.3.1 retained.

---

## 4. Target solution (summary)

- **One repository:** keep `taleed-talent-spa/` and its approved design; add `backend/` (Laravel + Statamic 6 Core), `contracts/openapi.yaml`, `infra/`, `scripts/dev/`, `scripts/prod/`, root `compose.dev.yaml`, `compose.prod.data.yaml`, `compose.prod.yaml`, root `.github/workflows/ci.yml`.
- **One HTTPS origin:** `/app/*` React (browser routing, old `#/` links redirected), `/api/v1/*` Laravel JSON API, `/auth/*` + `/sanctum/csrf-cookie`, `/help/*` Statamic guidance pages, `/cp` single CMS admin, `/up` and `/ready` health.
- **Authentication:** application users in MySQL on the `app` guard (Fortify + Sanctum cookies + CSRF); the single CMS admin on Statamic's own guard. A CMS login never grants API access; an app user never becomes a CMS user. No Statamic Pro features.
- **Data ownership:** MySQL holds all business data; Statamic flat files hold only help/guidance pages and the CMS user; a protected file store holds source PDFs and generated reports. Each is backed up.
- **Privacy:** owner-private records are encrypted and never visible to Taleed or Admin, including counts and "has used" signals. Automatic reports use a fixed list of aggregate fields (see `API-CONTRACT.md`) and the approved small-cohort policy.
- **Local development:** `https://talent.taleed.test` with a mkcert certificate, Compose project `talent-dev`, MySQL on `127.0.0.1:3308`, Mailpit on 8027/1027, HTTPS port configurable (`TALENT_HTTPS_PORT`, default 443, use 9443 if the Sustainability overlay holds 443). Adding the hosts entry and trusting the certificate need the developer's machine owner's approval.
- **Production:** dedicated VM, data on a separate persistent disk mounted before Docker starts, external named volumes that must already exist, a dataset identity check, and a separate one-time first-install command that refuses a non-empty target.

---

## 5. Phase backlog and definition of done

The developer starts each phase by pasting the matching message from `docs/production/PHASE-PROMPTS.md` and adding: *"Director decisions in docs/production/STATUS.md §2 are binding."* Each phase ends with changed paths, commands actually run with results, unrun checks, and an update to §1 and §7 of this file. "Not run" is never reported as passed.

**Phase 1 — Local foundation (5–7 days)**
- Replace `taleed-talent-spa/AGENTS.md` with production frontend rules; add superseded banners to its README and `CODEX_MASTER_PROMPT.md`.
- Pin exact npm versions from the lockfile; standardize Node 24.
- Create `backend/` with locked Laravel + Statamic 6 Core (Pro off), Fortify, Sanctum.
- Dev Compose (app, worker, scheduler, MySQL 8.4, Mailpit, Vite, HTTPS proxy), `.env.example`, Makefile (`dev-init`, `dev-up`, `test`, `dev-down`, `reset-local-synthetic`).
- Serve the built SPA at `/app`, one sample `/help` page, health endpoints; test route precedence.
- Root CI (PHP tests on MySQL, frontend typecheck/tests/build); Pages set to manual demo only (D13).
- Done when: a fresh clone runs over trusted HTTPS with the commands documented, restarts without losing data, and CI is green.

**Phase 2 complete — 1 October 2026**
- Added locked Laravel Fortify `v1.40.0`, Sanctum `v4.3.3` and Passkeys `v0.2.1` dependencies.
- Added the MySQL identity migration for normalized users, organizations, memberships, platform roles, invitations, preferences, privacy acceptances and installation identity.
- Added separate `app` Eloquent and Statamic guards, application login/logout/profile, CSRF-protected sessions, admin-only Leader invitations and atomic verified-email invitation consumption.
- Added synthetic feature coverage for CSRF, guard separation, tenant membership, invitation expiry/replay boundary and role enforcement.
- Verification and TOTP routes are registered through Fortify; application password reset uses an isolated Eloquent database-token service because Statamic owns the global flat-file broker.
- Local tests cover CSRF, invitation-only registration, password reset notification, staff MFA blocking, guard separation, tenant policy, verified-email matching, expiry and replay protection.

**Phase 2 — Schema and identity (8–10 days)**
- Migrations for identity, organizations, memberships, platform roles, analyst assignments, invitations, preferences, source/activity versions, plans (with D10 unique key), commitments, schedules, occurrences (with superseded status and active-date uniqueness), closures, private payloads, sharing, idempotency, outbox, audit, installation identity.
- Independent `app` and Statamic guards and password brokers; invitation-only onboarding (D9); email verification; password reset; session security; rate limits; TOTP for staff (D11).
- Policies that deny by default; freeze `contracts/openapi.yaml` v1 and generated TypeScript types.
- Done when: MySQL tests prove wrong-guard denial, cross-tenant denial, invitation expiry/replay/race safety and no role injection.

**Phase 3 — Domain API (10–13 days)**
- Catalogue and source visibility (published only), bookmarks, private custom Develop activities.
- Drafts, Pick-3 activation, schedule versions, calendar, occurrence transition/reschedule/note commands, metrics.
- Close/reopen with immutable revisions; latest-closed-revision aggregation; expected-version conflicts; idempotency keys.
- Port the 41 prototype domain checks as shared fixtures run by both PHP and TypeScript.
- Done when: concurrent-request tests on MySQL show no duplicate occurrences, no lost history and correct conflicts.

**Phase 4 — Connect the UI (10–13 days)**
- RTK Query against the API; real sign-in/out and invitation acceptance screens; remove personas, localStorage data, backup/import and reset.
- Honest save states (saving/saved/retry/conflict), 401/403/404/409/419/422/429 handling, cache clearing on logout.
- Browser routing under `/app` with old hash-link redirects; keep approved design tokens and layouts.
- Done when: Playwright desktop and mobile journeys pass over HTTPS against Laravel/MySQL, including a two-tab conflict.

**Phase 5 — Content, private features, sharing, reports (12–15 days)**
- Source document store with hashes; draft import from the approved extraction (D5); review/approve/publish/retire workflow for the content admin.
- Statamic guidance pages edited by the single CMS admin.
- Conversations and well-being wheel, encrypted, owner-only, flags off by default (D6, D7).
- Monthly reports: close-triggered frozen summaries, hash verification, Taleed organization assignment scope, supersede history and safe export.
- Owner exports, deletion and 30-day erasure (D16); CSV/ICS exports; neutral opt-in reminders through the queue.
- Done when: negative tests prove no private data or participation signal reaches Taleed/Admin reports, logs, jobs or exports.

**Phase 6 — Production safety (8–11 days)**
- `compose.prod.data.yaml` and `compose.prod.yaml`, external volumes, mount and dataset-identity guards, systemd ordering.
- One-time `init-production`; deploy script with lock, backup, single migration, service swap and verification; compatible-image rollback.
- Backup to GCS with manifest and checksum verification; isolated restore; tombstone replay.
- IP-certificate issuance and renewal scripts with expiry monitoring (D2); OIDC CI-to-Artifact Registry workflow templates (D4).
- Done when: tests pass for two releases over populated synthetic data, missing volume/mount, failed backup/upload, concurrent deploys, migration failure, restore with correct and wrong key; measured restore time recorded.

**Phase 7 — Provision and release (3–4 days, explicit authorization required)**
- Provision the dedicated VM, data disk, static IP, firewall/IAP access, GCS bucket and Artifact Registry per D1–D4.
- First install on the verified empty target; import approved content only if G1 is signed off; issue the IP certificate.
- Smoke tests with synthetic accounts, backup taken and verified, restore rehearsal evidence, handover runbook.
- Done when: the real HTTPS URL, release ID, certificate status and backup ID are recorded in §7.

---

## 6. What the director must provide, and when

| Needed by | Item |
|---|---|
| Before Phase 1 | Company-owned **private** GitHub repository (transfer from `pierre-akhrass/Taleed-Talent` or create new) and developer access (D13). |
| Before Phase 1 | A secure way to give the developer the authorized source package (`FW__Talent_tools` PDFs and `Taleed_Talent_Tool_Package/reference/`) — not through the Git repository. |
| During Phase 3–5 | Client content owner reviews and signs off the 72 activities and conversation guide (G1). |
| During Phase 5 | Client privacy owner reviews notices, retention (D16) and the sharing field list and minimum (G2, G3). |
| Before Phase 6 ends | Budget approval for the new VM, static IP, data disk, GCS bucket and Artifact Registry; a named operator with the right GCP IAM roles; a named backup/restore owner. |
| Before Phase 7 | SendGrid Talent API key and sender domain with SPF/DKIM (D15, G4), stored in the secret store — never in chat or Git. |
| Phase 7 | Written authorization to provision and release, naming the target and the release commit. |

---

## 7. Release and evidence log

| Date | Phase | Commit | Result | Evidence |
|---|---|---|---|---|
| 2026-09-30 | 0 | `5e33846` | Discovery complete; director decisions D1–D16 recorded | This file, §2–§3 |
| 2026-10-01 | 0/1 | working tree | Phase 0 revalidated; Laravel 13.34.0 + Statamic Core 6.34.0 local scaffold, route tests and Compose config verified; Docker image build stopped during slow dependency downloads | `docs/production/evidence/phase0-current-2026-10-01.md` |
| 2026-10-01 | 1/2 | working tree | Local foundation and identity phases completed; MySQL 8.4 migrations applied; worker/scheduler running; full local Laravel suite and frontend checks passed; HTTPS HMR proxy limitation remains | `docs/production/evidence/phase2-current-2026-10-01.md` |

Production URL: **none — not deployed.** A Pages demo, a planned address or a local URL is never recorded here as production.

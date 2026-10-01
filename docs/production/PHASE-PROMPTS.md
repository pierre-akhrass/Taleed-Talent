# Phased execution messages for either agent

Use these messages in the repository containing this pack. The master contract always applies. Each phase ends with actual changed paths, commands/results, unrun/blocked checks and an updated phase handoff. Do not request Phase 7 before the release gates are satisfied.

## Phase 0 — read-only discovery and contract proposal

```text
Read AGENTS.md and docs/production/MASTER-PROMPT.md. Execute Phase 0 only.
Inspect the current repository, all application flows, source/approval documents
and authorized sibling reference repositories read-only. Compare actual files
with REPOSITORY-AUDIT.md; report differences, not assumptions. Do not read
secrets, connect production, install packages, edit code or run migrations.

Return: current-state inventory with exact paths; UX/domain/source traceability;
prototype-to-production gap list; Core licensing/authentication boundary;
verified dependency constraints; proposed schema/API and data-ownership map;
local HTTPS design; target-VM/edge/persistence discovery needs; tests; decisions
and a phase plan. Distinguish verified repository configuration from live state.
Call out source wording, wheel-boundary, privacy and same-project/same-VM gaps.
Propose only material deviations from the supplied architecture. Ask only for
required information that authorized reads could not resolve. Stop for Phase 1.
```

Phase 0 exit: a concrete plan and evidence, not package installation or infrastructure mutation. The user can approve the stable architecture while leaving sensitive feature flags off pending content/privacy approval.

## Phase 1 — working local foundation

```text
Phase 0's accepted architecture is approved for LOCAL implementation. Save its
read-only findings under docs/production/evidence/ and record unresolved gates.
Implement Phase 1 now: add backend/ with a compatible locked Laravel + Statamic
6 Core installation; keep taleed-talent-spa/ and its approved UI. Add reproducible
Docker local services and trusted-HTTPS setup for talent.taleed.test, with
same-origin API routing and Vite HMR. Ask before host trust/hosts changes.

Add real health checks and a Core-rendered help page plus the SPA shell, prove
route separation and container restart behavior, and create safe env examples.
Correct active agent-instruction conflicts without deleting historical evidence.
Pin compatible dependencies; do not run an indiscriminate latest bootstrap.
Produce actual build/start/test commands and their results. Do not access cloud,
create production accounts, send real mail or deploy. Stop after the local
foundation works or clearly document the exact remaining runtime blocker.
```

Exit: runnable local Docker/HTTPS foundation, real Laravel/Statamic routes, compatible locks and tests. A Dockerfile alone is not a working foundation. Source/seeded help text remains marked local/provisional until approved.

## Phase 2 — MySQL schema and real identity

```text
Implement Phase 2 on the approved local foundation. Turn DATABASE-SCHEMA.md into
reviewed Laravel migrations/models and scoped policy services. Establish the
first OpenAPI/schema contract and migration ownership before parallel work.
Use MySQL, not a SQLite-only substitute. Add synthetic organizations/users only
through explicit local factories.

Implement independent Eloquent application and Statamic CP guards/providers/
password brokers. Build real sign-in/out, verification, reset, Admin-created
Leader invitation expiry/replay protection and role/organization authorization. Use
Sanctum first-party cookies/CSRF, session rotation and rate limits. Prove that
app users cannot enter the CP and CP login alone cannot read application APIs.
No role or existing organization access may come from untrusted signup fields.

Test tenant/owner denial, wrong guard, CSRF, expired session, privileged grants,
email-domain no-auto-join and concurrent invitation consumption. Keep secrets
out of output and real mail disabled. Record schema decisions and freeze the
initial contract for backend/frontend work. Do not deploy or run production SQL.
```

Exit: real local authentication and MySQL identity/policy tests; agreed schema and client contract, not simulated persona selection.

## Phase 3 — authoritative business API

```text
Implement Phase 3 using the frozen contract. Build Talent domain services and
API for approved catalogue/version visibility, bookmarks, private custom Develop
activities, drafts, activation, commitments, schedule versions, occurrences,
metrics, close/reopen, immutable closure history and automatic Taleed monthly
reports. Derive tenant/owner from
authenticated context; enforce policies in queries, downloads and jobs.

Port existing pure rules with shared fixtures. Preserve exact Pick-3 scope/theme
rules, date-only scheduling, original occurrence identity, reschedule collisions,
cancellation denominator, completed scope coverage and latest-closed-revision
aggregation. Implement expected-version conflicts, transactions, idempotency and
outbox events. Preserve incomplete closure without inventing completion.

Add actual MySQL integration and concurrent-request tests, including completed
history preservation and stale editor conflicts. Keep sample content local-only;
production source approval remains a separate import/publication gate. Expose no
generic all-tenant/all-private data endpoint. Update the OpenAPI/types and route
coverage evidence through the designated contract owner only.
```

Exit: tested server authority for the planning journey. An API backed by the old browser envelope is not completion.

## Phase 4 — connect the approved interface

```text
Implement Phase 4 in taleed-talent-spa/ against the frozen, implemented API.
Preserve approved design tokens, components, layouts and feature journeys.
Replace localStorage authority and demo personas with RTK Query and real session
flows. Keep transient UI state in Redux; do not mirror the API into a second
persisted database or silently fall back to demo mode.

Make autosave honest and handle 401/403/404/409/419/422/429, offline failure and
retries without losing current unsaved in-memory text. Clear sensitive cache on
logout, tenant switch and permission loss; guard against old in-flight responses.
Remove production all-persona backup/import, seed controls and fake authentication.
Preserve legacy hash-link usability if adopting /app browser-history routes.

Run full typecheck, unit/component tests, production build and real browser flows
through HTTPS Laravel/MySQL, including multi-tab conflicts, mobile/keyboard and
RTL-layout readiness. No arbitrary redesign, backend schema edits or shared
lockfile changes outside your assigned ownership. Document real evidence.
```

Exit: existing UI reads/writes the server and survives refresh/account sessions without demo fallback. Parallelize with Phase 3 only under the ownership rules and stable contract.

## Phase 5 — approved content, private work, sharing and reports

```text
Implement Phase 5: verified source-version upload/import/approval and business
catalogue administration; bounded Statamic Core guidance editing; private
conversations/reflections/well-being; owner exports/retention/deletion; automatic
organization reports available to the Taleed role.
Use the approved API/schema privacy boundaries, not generic CRUD serialization.

Treat original PDFs as authoritative and samples as unapproved. Source imports
create drafts and preserve version/provenance; missing rights/text remain blocked.
Keep wheel classifications off until a specific versioned non-overlapping rule is
approved; test 35 and 60 in all views. Keep real private collection disabled until
its separate privacy/content gates are cleared. Never infer those approvals from
general UI approval.

Build automatic closed-month summaries with source fingerprints and exact payload
hashes, transactional supersede/withdraw, safe aggregate serializers and Admin
organization-scope checks. Exclude private contents AND participation metadata
from every report.
Add encrypted private storage, key-version/deletion recovery handling, secure
reports/downloads, neutral opt-in queued reminders and SMTP configuration examples.
Test negative access, upload/report security, retry deduplication and no sensitive
browser/log/job leakage. No live data, real email or cloud writes in this phase.
```

Exit: complete feature implementation with explicit enabled/blocked states and tests; no fabricated source text or consent approvals. Unapproved private features may remain technically complete but disabled.

## Phase 6 — deployment safety and release readiness

```text
Implement Phase 6 locally using production-like SYNTHETIC data. Build separate
production data/app lifecycle configuration, fail-closed external storage guards,
one-time initialization, immutable release deployment, coordinated backup/off-VM
manifest verification, isolated restore and compatible-image rollback. No automatic
production init/reset, DB replacement, APP_KEY regeneration or live DB restore.

Fix root CI paths and ensure tests gate releases. Make existing Pages deployment
explicitly synthetic-demo-only or disable its automatic trigger. Provide restricted
GCP federation/release configuration templates, not credentials. Test two code
releases over populated data, a missing volume/mount, failed backup/upload,
concurrent deployments, migration failure, encrypted restore and key failure.
Pause all relevant writers when proving DB/file consistency, including CMS/jobs.

Produce production readiness evidence against ACCEPTANCE.md and the operator
runbooks. Record measured test recovery time and proposed production RPO/RTO.
Do not claim live infrastructure inspection from repository examples. Request
separate read-only target inventory authorization when needed; cloud changes,
production SMTP, DNS/TLS and deployment are still not authorized.
```

Exit: repeatable tested operations and an honest release gate report. Script syntax checks or a gzip checksum alone do not prove restore/recoverability.

## Phase 7 — explicitly authorized live release

Do not paste this message with unresolved target placeholders. Resolve authorized read-only inventory first. The operator must supply/approve the actual project, VM, hostname/IP, data-storage identity, backup location and release commit/digest through the approved secure configuration path, not expose passwords in chat.

```text
Phase 7 is authorized ONLY for the verified target and immutable release recorded
in the approved deployment request. Read that request and the passed readiness
report. Confirm first-install versus update mode, expected dataset/volume identity,
content/privacy enablement approvals, backup/key recovery availability and accepted
maintenance window. Do not change any sibling application's storage or engine.

Execute the scoped runbook: preflight/lock; required consistent and verified
backup for an existing dataset; one approved migration task; replace app services;
validate HTTPS, release identity, auth, source availability, storage, worker and
scheduler; check sibling tools; reopen writes. On a genuinely new target use the
separately approved first-install path, never an automatic empty-DB fallback.

Stop safely on any failed gate. Do not run destructive SQL, volume deletion,
automatic database restore or unapproved cloud/edge changes. Use only authorized
synthetic smoke data/recipients, clean them through the approved procedure, and
avoid inspecting real private records.

Return the actual production HTTPS URL and observed release identifier, certificate
and health results, backup/recovery evidence references, deployment outcome,
remaining accepted limitations and operator handover. A planned URL or successful
Pages build is not a production deployment. Record any blocker instead of claiming
success when a command, access or approval was unavailable.
```

## Independent review message — before merging or deploying

```text
Review the candidate implementation read-only against AGENTS.md and the production
master/specifications. Inspect the diff, tests and actual evidence; do not trust a
previous agent's success summary. Focus on license/guard separation, tenant/owner
access, private metadata leakage, source approvals, wheel boundaries, immutable
history, concurrency, persistence identity, deployment lock, backup completeness,
restore keys, migration compatibility and correct root CI.

Return findings by severity with exact file/line, failure scenario and missing or
failing test. Separate verified defects, plausible risks needing verification and
stylistic preferences. Do not edit another worktree, deploy, run destructive
commands or declare production safe from static review alone.
```

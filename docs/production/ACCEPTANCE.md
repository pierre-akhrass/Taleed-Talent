# Acceptance and release evidence

All implementation/release checks below start as **NOT RUN**. The only checks executed during prompt preparation are the original lightweight source and domain checks recorded in `REPOSITORY-AUDIT.md` and `evidence/`. A demo's approved appearance does not establish any server or operations pass.

For each gate record: status (Pass/Fail/Blocked/Not run), test identifier, exact command or manual steps, build/commit, environment, date, result/evidence path and owner. A failed critical privacy, data-integrity, license or recoverability gate blocks release. Lower-impact exceptions require a named owner, deadline and written acceptance. Never relabel an unrun check as pass.

Use at least two synthetic organizations; two leaders and a Champion in the first; a leader and Champion in the second; assigned/unassigned analysts; an application content admin; and the separate single Statamic CP account. Test on the real pinned MySQL implementation, not only in-memory stubs. No live private records in developer/CI fixtures.

## Foundation, identity and licensing

| ID | Required scenario | Pass condition |
|---|---|---|
| F01 | Clean local setup using documented Docker commands | Real HTTPS app, API, CMS guidance, CP, MySQL, worker/scheduler and Mailpit work; no undocumented host dependencies or TLS bypass. |
| F02 | Repeat startup on populated local data | No reset or seed overwrite; versioned migrations only. |
| F03 | Core edition/license audit | Pro disabled, no commercial addon dependency, one CP administrator, no paid-feature or IP trial-mode bypass. |
| F04 | Independent guards and password brokers | Application user cannot enter CP; CP session alone cannot read app APIs; mixed-login/logout/reset behavior explicitly tested. |
| F05 | Registration/verification/reset/invitations | Real database-backed sessions, correct signed HTTPS links, token expiry/replay and correct-account acceptance; no company-domain auto-join or role injection. |
| F06 | Security controls | CSRF enforced; session fixation/rotation/logout/revocation covered; rate limits and privileged access/MFA/recovery controls tested without exposing secrets. |
| F07 | Root CI and release routing | CI actually runs at repository root with correct working directories; typecheck/tests/build block release; Pages cannot deploy sensitive/backend artifacts as production. |

## Domain and concurrency

| ID | Required scenario | Pass condition |
|---|---|---|
| D01 | Baseline source catalogue validation | 72 approved source activities, 18/theme, six/theme/scope; actual original/adapted provenance and correct Care scope mapping. Samples/customs excluded from count. |
| D02 | Pick-3 and custom activities | Exactly one distinct available activity per scope in one theme; no 12-activity requirement; extra owner-authored customs only Develop. |
| D03 | Calendar/recurrence | Valid daily, selected-weekday weekly and Develop-only one-off dates; month bounds/leap days/4–6 week layouts/timezone behavior; retries create no duplicates. |
| D04 | Schedule edits/reschedule collisions | Completed/cancelled/past history survives; only eligible future unfinished work changes; original identity stable and current-date collision policy enforced concurrently. |
| D05 | Metrics and incomplete closure | Cancelled excluded from denominator; eligible-zero rate null; delivered scope coverage uses completed work; no invented completion on close. |
| D06 | Close/reopen history | Immutable closure revisions, latest-closed-per-plan aggregation and open-correction behavior; double close/reopen retries safe. |
| D07 | Stale editors and conflicting requests | Expected-version conflict prevents silent overwrite; user can recover/reconcile; concurrent mutations produce a single correct result. |
| D08 | Source/activity version lifecycle | Draft/unknown/retired source cannot enter new plans contrary to policy; pinned historical versions survive updates; imports never auto-publish. |
| D09 | Reliable jobs | Outbox dispatch after commit, idempotent recurrence/notification/report actions, safe retry/deadlock behavior and exactly one effective scheduler. |

## Privacy, sharing and real UI

| ID | Required scenario | Pass condition |
|---|---|---|
| P01 | Cross-organization and same-organization wrong-owner requests | Direct API IDs, lists, counts, query filters, exports/jobs/storage URLs deny unauthorized access; no generic admin/CP bypass. |
| P02 | Personal conversations and wheel | Owner-private payload and metadata; no Champion/analyst participation signal in pages, caches, reports, logs or support tools. |
| P03 | Well-being source boundary | Nine integer 1–10/null draft values; complete total 9–90; classification disabled without explicit versioned approval, including totals 35/60 in every view/report. |
| P04 | Content/privacy enablement gates | Unapproved source or private collection disabled server-side with honest UI; no inferred permission from general demo approval. |
| P05 | Exact preview and confirmed sharing | Same frozen payload/source fingerprint; changes require new preview; no arbitrary browser-computed share; concurrent confirmations yield one effective version. |
| P06 | Aggregate allowlist and analyst assignment | No identity/free-text/private metadata leakage; only effective shares for assigned organizations; matching screen/export filters and approved small-cohort policy. |
| P07 | Withdraw/supersede/revoke access | Effective reads, new exports and downloads denied or updated; old cache invalidated; already downloaded-copy limitation communicated. |
| P08 | Encrypted data and deletion | Wrong key fails safely; correct protected key restores personal data; erasure removes authorized duplicates/reports; tombstones prevent recovery resurrection. |
| P09 | Browser persistence/session transitions | No private data/token in localStorage/IndexedDB/persisted Redux/service-worker caches; logout/tenant switch/session expiry clear sensitive memory and in-flight response hazards. |
| P10 | Autosave and error paths | Saved only after server acknowledgement; 401/409/419/422/429 and network loss handled; no silent demo fallback or unsafe mutation replay. |
| P11 | Source/upload/report safety | Actual hashes, access-controlled immutable files, MIME/size/path rules, CSV formula and HTML/ICS escaping, renderer SSRF/resource limits where applicable. |
| P12 | Accessible visual parity | Approved layout/tokens preserved; responsive agenda/keyboard/focus/error labels/reduced-motion work; full browser journeys and RTL-layout readiness checked. No false translation/WCAG certification. |
| P13 | Mail/reminders | Local Mailpit; approved production transport configured; neutral opt-in deduplicated reminders; no private content in email/failed-job payloads; no real send without authorization. |

## Production data survival and recovery

| ID | Required scenario | Pass condition |
|---|---|---|
| O01 | First install vs normal update | Explicit verified-empty initializer; regular deployment never falls back to new DB/seed/key generation. |
| O02 | Missing/wrong volume, mount or dataset identity | Deployment and VM/container boot paths stop before writes; no empty boot-disk database is silently created. |
| O03 | Two releases over populated synthetic data | Accounts, drafts, schedules, closures, private ciphertext/decryption, share history, source/CMS edits and files survive application replacement and restart. |
| O04 | Database/edge lifecycle separation | App deployment does not recreate/upgrade DB, change persistent keys or replace shared proxy configuration; sibling routes remain healthy. |
| O05 | Parallel deployment/backup/migration | Single bounded coordination lock, no nested deadlock, one migration, safe worker draining and scheduler behavior. |
| O06 | Consistent backup set | Controlled DB/file/CMS write boundary; all required components and recovery key references present; actual offsite artifact matches manifest. |
| O07 | Failed backup/upload/disk-full/key access | Nonzero failure and alert; no migration or release promotion after an incomplete required backup. |
| O08 | Isolated restore rehearsal | Database/files/CMS/key state restores, private records decrypt, content/report hashes validate, permissions/shares/deletions remain correct; actual RPO/RTO measured. |
| O09 | Compatible image rollback | Previous compatible image works with current data; no automatic old-DB restore or destructive down migrations; incompatible case has safe forward-fix plan. |
| O10 | HTTPS, proxy and cloud access | Verified target/DNS/IP, certificate renewal/expiry alert, correct signed links/cookies, restricted proxy trust, no public MySQL/Docker debug ports. |
| O11 | Monitoring and shared-VM capacity | Backup age/failure, storage, queue, scheduler, readiness and certificate alerts delivered; measured capacity keeps other tools safe. |
| O12 | Final authorized deployment | Actual HTTPS URL and release identity observed; smoke evidence, backup/restore references, support/runbooks and accepted exceptions handed over. |

## Minimum end-to-end demonstration

Create and verify an account; create a new organization without joining an existing one; invite and accept a leader; choose a valid three-scope plan; schedule and save it; sign out/in and resume; record completed/blocked/cancelled occurrences; exercise a stale-tab conflict; close an honestly incomplete plan; reopen/reclose without rewriting prior history; preview and explicitly share the safe summary; view it as an assigned analyst; deny an unassigned analyst and another owner's private requests; withdraw the share; update the application release and show preserved data; restore the synthetic recovery set and prove key/file integrity.

Include owner-private conversation/wheel journeys only when their gates are enabled. Otherwise demonstrate server-side disabled-state enforcement and identify the outstanding approval, rather than accepting real private data prematurely.

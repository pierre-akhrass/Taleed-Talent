# Application API, authorization and consistency contract

This is the v1 application API contract. `contracts/openapi.yaml` freezes the identity/session and first Talent-domain request/response shapes. Identity/session and the Phase 3 catalogue/planning/scheduling/occurrence-note/closure/report-read slice are implemented locally. Source administration, conversations/well-being, private exports, and any other route not listed as implemented in the Phase 3 completion evidence remain future work; an OpenAPI declaration alone is not implementation evidence. Use Laravel-owned routes and domain data, **not Statamic Pro's headless API**.

## Authentication and common response rules

All application traffic is same-origin HTTPS. Follow the installed Sanctum session/CSRF documentation. Application registration/login/reset/verification endpoints use the appropriate web/session middleware and rate limits; protected `/api/v1` endpoints use the intended application guard and verification/membership checks. The CMS guard must not authenticate these APIs. Unauthenticated protected JSON requests return JSON, not an HTML sign-in page or the SPA shell.

Suggested route families: `/auth/register`, `/auth/login`, `/auth/logout`, `/auth/forgot-password`, `/auth/reset-password`, `/auth/email/verification-notification`, a signed email-verification route, `/sanctum/csrf-cookie` and `/api/v1/me`. Preserve Laravel-compatible verification/reset semantics and safe redirects. Do not pass role or existing organization access from registration input.

Responses have a typed `data` object plus bounded pagination/version metadata. Errors have a stable machine code, safe message, field errors where relevant, and a request ID. Do not include stack traces, SQL, secrets or private request bodies. Define use of 401, forbidden/not-found 403/404, 409 conflict, 419 expired CSRF/session, 422 validation and 429 throttling consistently. Choose an anti-enumeration 404 policy where revealing another tenant's record existence would be inappropriate.

Mutations to existing aggregates supply the expected `lock_version` (or a precisely specified If-Match token). The server uses an atomic conditional write/transaction; affected-row mismatch is a conflict. Successful responses return the new version. Stale browser tabs must offer refresh/reconcile, not overwrite newer data.

Risky retriable commands use an `Idempotency-Key`, scoped to principal + organization + operation and bound to a request hash. Replayed identical commands return the same authorized result reference; a different payload using the same key is rejected. Never persist full private responses in a general-purpose idempotency log.

Tenant/owner context is selected from authenticated authorized membership. An organization path/header is a request for a context, not trusted proof of access. Unknown extra fields and mass-assignment of owner, tenant, role, approval or server metrics are rejected.

## Capability matrix

| Capability | Leader | Taleed | Application Admin | Statamic CMS administrator |
|---|---|---|---|---|---|
| Own plans/drafts/calendar/closures | Own records | No implied access | No implied access | No implied access |
| Own conversation/wheel/private reports | Own records after feature/privacy gate | No | No | No |
| Approved catalogue/library | Authorized application access | As assigned | Manage SQL reference versions | CMS guidance only |
| Organization membership/invitations | Own membership view | No | Create organizations and invite Leaders | No |
| Monthly organization reports | No approval or send action; close produces the report | Read safe reports for assigned organizations | No private report bypass | CMS guidance only |
| CMS guidance editing | No | No automatic CP access | No automatic CP access | Single authorized CP account |

Separate grants may coexist for the same person; possessing a platform role never removes record-owner privacy restrictions. Infrastructure operation is a separate, controlled trust boundary, not an “impersonate everyone” business role.

## Proposed endpoints

| Area | Routes / commands | Essential enforcement |
|---|---|---|
| Session/profile | `GET /me`, `PATCH /me/preferences`, approved account/password operations | No all-users envelope; invalidate auth/cache state after security changes. |
| Workspace | `POST /admin/organizations`, `GET /organizations/current`, `PATCH /organizations/current` | Admin creates the organization; no public self-registration or name/domain auto-join. |
| Membership | `GET /organizations/current/members`, `POST /admin/organizations/{id}/invitations`, `POST /invitations/{id}/accept`, `POST /invitations/{id}/revoke` | Admin-created invitations target `leader` only; return only necessary member details; token not logged; correct verified account and atomic consumption. |
| Catalogue | `GET /activities`, `GET /activities/{id}`, `GET /source-documents`, protected source download | Published global plus authorized own custom content; retired source availability policy; no private storage URLs. |
| Custom Develop | `POST /custom-activities`, versioned `PATCH /custom-activities/{id}`, `POST /custom-activities/{id}/retire` | Develop only, owner+tenant bound, clear authorship, no global publication. |
| Bookmarks | `GET /bookmarks`, `PUT/DELETE /activities/{id}/bookmark` | Caller-owned only; include only currently visible activities and recheck tenant/owner visibility. |
| Drafts | `GET/POST /plan-drafts`, `GET/PATCH /plan-drafts/{id}`, `POST /plan-drafts/{id}/activate` | Partial save vs complete activation validation; expected version; Pick-3 authoritative server check. |
| Plans | `GET /plans`, `GET /plans/{id}`, `POST /plans/{id}/commitments` | Owner only; added work must be an owned custom Develop activity in a Develop plan; closed plan mutation denied. |
| Schedules | `POST /commitments/{id}/schedule-versions` | Parent owner/tenant, explicit effective date, preserve history, concurrency/date-slot collision checks. |
| Calendar | `GET /calendar?month=YYYY-MM`, `PATCH /occurrences/{id}`, `PUT /occurrences/{id}/note`, `POST /occurrences/{id}/reschedule` | Owner-scoped bounded month; private note ciphertext, permitted state transition, completed/cancelled history rule, date collision and expected-version enforcement. |
| Close/reopen | `POST /plans/{id}/close`, `POST /plans/{id}/reopen`, `GET /plans/{id}/closures` | Server snapshots; idempotent transaction; no client-submitted authoritative closure/metrics. |
| Personal conversations | `GET/POST /conversations`, `GET/PATCH/DELETE /conversations/{id}`, `POST /conversations/{id}/complete` | Owner-only including list/count; feature/privacy gate, verified guide version, deletion/retention behavior. |
| Personal wheel | `GET/POST /wellbeing`, `GET/PATCH/DELETE /wellbeing/{id}`, complete/revision commands | Owner-only, nine nullable integer ratings, rules version, explicit privacy gate, no classification until approved. |
| Reports | `POST /private-exports`, `GET /private-exports/{id}`, protected expiring download; plan `.ics` export | Recheck ownership at enqueue/run/download; CSV/HTML/ICS/PDF safety; private no-store responses. |
| Monthly reports | Created by the close transaction/outbox after `POST /plans/{id}/close`; `GET /portfolio`, `GET /portfolio/organizations/{id}/reports`, safe export request | Freeze the allowlisted payload from the closed revision; one effective version per organization/month; no user approval, arbitrary browser summary or private joins. |
| SQL content administration | draft source/activity import, review, approve, publish, retire routes under `/content-admin/*` | Content-admin policy; immutable version creation; source rights/dependencies and actual hash; no CP privilege. |
| Privacy | owner export/deletion requests, notice acceptance/withdrawal | No silent erasure/cascade ambiguity; approved retention and backup recovery suppression. |

Routes are illustrative names to freeze, not a requirement to expose a generic CRUD endpoint for every database table. Use intention-revealing commands for significant transitions. Do not add account impersonation, third-party calendar synchronization, payments or employee appraisal endpoints.

## Shared summary allowlist

The prototype already uses the following organization-level shape. Preserve names where practical for frontend compatibility, then obtain specific disclosure approval before enabling production sharing:

```json
{
  "organizationId": "authorized-organization-id",
  "organizationName": "Authorized organization display name",
  "month": "YYYY-MM",
  "participatingLeaders": 0,
  "closedPlans": 0,
  "activityCounts": {"care": 0, "develop": 0, "enable": 0, "recognition": 0},
  "scopeCounts": {"individual": 0, "culture": 0, "team": 0},
  "scheduled": 0,
  "completed": 0,
  "blocked": 0,
  "inProgress": 0,
  "cancelled": 0,
  "eligible": 0,
  "coverage": [],
  "fullyCoveredDevelopmentPlans": 0,
  "version": 1,
  "sharedAt": "server-generated-UTC-timestamp"
}
```

The values above illustrate a shape, not a seeded production result. `coverage` contains only supported scope strings. Build a new response explicitly from eligible closed-plan facts. Never spread full models/snapshots into this object. Organization identity is allowed; individual names, emails, IDs, aliases, employee data, titles, instructions, custom narratives, notes/reflections, conversation use/metadata and all well-being content/participation are prohibited.

The close transaction/outbox freezes the allowlisted payload and its source revision fingerprint. Taleed reads return that exact server-generated payload, not a silently recalculated or browser-composed set of counts. If a correction closes a newer revision, create a new effective report version and retain the immutable prior report.

A withdrawal blocks subsequent in-app reads and new downloads of that effective share. It cannot recall previously downloaded copies. Permission revocation, new analyst assignments and supersession must invalidate server/client caches. Keep internal traceability references separate from the external payload. Agree small-cohort suppression/disclosure rules explicitly; do not invent a threshold or expose private participation under a “support” route.

## Contract and browser implementation evidence

Implement an OpenAPI contract, response/request validation tests and generated or maintained typed frontend clients. Include examples for successful save, stale draft conflict, expired session, unauthorized record, invalid schedule, duplicate close, failed mail job and changed sharing preview. Verify that cross-tenant errors do not leak record existence or private context. Bind every UI route to an implemented endpoint and mark unavailable/incomplete features honestly; no hidden mock fallback in production.

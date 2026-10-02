# MySQL schema and invariant specification

This is the target logical/physical design contract, **not an executable production dump**. Implement and test Laravel migrations after Phase 0 verifies current behavior. Do not run a schema reset or import the prototype envelope into production.

## Conventions

Use MySQL 8.4 LTS/InnoDB, `utf8mb4` for human text, strict SQL behavior, and the same pinned engine family in local/CI/production. Prefer ULIDs for domain identities (`CHAR(26)` with a case-sensitive ASCII/binary collation); framework-managed tables may use their documented ID type. Use foreign keys and indexes, UTC timestamps with consistent precision, and date-only business fields. Store month as the first day of a month internally and expose `YYYY-MM` in the API. Organization timezone initially defaults to `Asia/Riyadh`.

Every organization-owned domain row has `organization_id`; personal rows also have `owner_user_id`. A foreign ID alone is not permission. Use composite unique keys/foreign keys such as `(organization_id, id)` where needed to prevent cross-tenant parent/child relationships. Validate active membership and ownership in Laravel policies as well; a foreign key cannot express the complete authorization policy. Do not assume MySQL provides an application-equivalent row-security policy automatically.

Mutable aggregates carry `lock_version BIGINT UNSIGNED`, incremented atomically with expected-version checks. Immutable source/activity versions and closure facts are append-only through application services, except an explicit retention/erasure process for separately protected personal payloads. Use checks/validated enums for controlled status/scope/theme values. Large private data uses authenticated-encryption ciphertext in `LONGTEXT` or an appropriate binary column, not plaintext JSON. Bound and validate JSON before encryption. Store a key-version reference; keep keys outside the database/image and back them up through protected recovery procedures.

Do not cascade-delete an organization or user across every business/audit record by default. Implement a documented retirement/erasure service, preserving the minimum authorized integrity facts and removing personal duplicates/reports. Index list/query filters rather than indexing encrypted contents.

## 1. Identity and organization access

| Table | Important columns | Constraints / indexes / meaning |
|---|---|---|
| `users` | id, normalized_email, display_name, password_hash, email_verified_at, status, authentication_version, timestamps | Unique normalized email under the chosen normalization rule; framework password hashing; no Statamic CP identity here. MFA/recovery material encrypted/hashed using the selected supported implementation. |
| `organizations` | id, name, sector, city, timezone, status, timestamps | No domain/name auto-join. Naming collisions go through a safe explicit process rather than merging accounts. |
| `organization_memberships` | id, organization_id, user_id, role (`leader`), status, joined_at, revoked_at | Unique `(organization_id,user_id)`; index `(user_id,status)`; Leaders can read only their own private records. There is no Champion membership role. |
| `organization_analyst_assignments` | id, organization_id, user_id, assigned_by, assigned_at, revoked_at | Unique `(organization_id,user_id)`; active assignment scopes Taleed report queries to explicitly assigned organizations. |
| `platform_role_assignments` | id, user_id, role (`admin`), assigned_by, assigned_at, revoked_at | Unique active application Admin role; grant/revoke restricted and audited. Do not map the Admin to the Statamic CP user automatically. |
| `invitations` | id, organization_id, invited_email, target_role (`leader`), token_digest, expires_at, accepted_by, accepted_at, revoked_at, created_by | Admin-created invitations always target Leader; unique digest; no stored plaintext token; consume under row lock only for correct verified account; replay/expiry/revocation rejected. |
| `user_preferences` | user_id, week_start, locale, reminders_opt_in, timestamps | Unique user; week_start restricted to supported values; no sensitive data. Locale readiness does not imply approved Arabic content. |
| `privacy_acceptances` | id, user_id, notice_version, purpose, accepted_at, withdrawn_at | Minimal acceptance record; versioned notice reference, not a copy of private answers. Withdrawal handled by privacy workflow. |

Use Laravel's required sessions/password-reset tables and any verified MFA support tables. Keep Statamic reset/activation brokers separate and persist its flat-file user safely; do not store the CMS administrator in `users` by automatic provider sync.

## 2. Source documents and activity catalogue

| Table | Important columns | Constraints / indexes / meaning |
|---|---|---|
| `source_documents` | id, stable_source_key, display_name, source_kind, current_approved_version_id, status | Stable logical resource identity; seven source documents are an approved-content target, not seven empty-file placeholders to publish. |
| `source_document_versions` | id, source_document_id, version_number, storage_key, sha256, mime, byte_size, rights_status, dependency_status, approval_status, approved_by, approved_at, created_at | Unique `(source_document_id,version_number)` and immutable storage key; actual bytes/hash; no public direct path. Original and adapted documents are distinguished. Retire availability separately from immutable content. |
| `activities` | id, stable_activity_key, kind (`source`/`custom`), organization_id nullable, owner_user_id nullable, current_version_id, status | Source activities are global with null tenant/owner; custom activities require tenant+owner and Develop theme in their versions. Validate the complete ownership combination. No global listing of private custom activities. |
| `activity_versions` | id, activity_id, version_number, theme, scope, original_text nullable, adapted_title, description, steps_json, source_document_version_id nullable, source_page nullable, adaptation_note nullable, content_hash, created_by, approved_by, approved_at | Unique `(activity_id,version_number)`; four themes/three scopes; immutable validated content. Source versions need approval before new use. Owner-authored custom Develop versions can be validated for private use without pretending to be source-approved global content. |
| `catalogue_publication_events` | id, activity_version_id, action, actor_id, timestamp, reason_code | Publish/retire changes availability without overwriting version text. Approved source retirement can disable new selection while history stays pinned. |
| `bookmarks` | user_id, activity_id, created_at | Composite primary/unique key; visibility checked when returned, including retired/private content. |

The old `Resource` concept maps to source documents and their versions. Do not create a redundant independent `resources` table unless a verified requirement genuinely distinguishes it. Guide/question/rule content can use a versioned approved definition document, referenced by the associated personal record, without copying private responses into CMS content.

Require 72 approved **source** activities, 18 per theme and six per theme/scope combination for the agreed baseline. Custom activities do not count toward that catalogue validation. Do not infer scope from PDF column position or claim sample cards are approved source records.

## 3. Planning, recurrence and close-out

| Table | Important columns | Constraints / indexes / meaning |
|---|---|---|
| `plan_drafts` | id, organization_id, owner_user_id, month, selected_theme, draft_payload_json, step, lock_version, timestamps | Bounded partial draft; validate referenced activities on each save and fully on activation. Do not persist a whole Redux envelope. Index owner/month. |
| `plans` | id, organization_id, owner_user_id, month, timezone, title, focus_theme, status, working_revision, lock_version, created_at, closed_at | Unique `(organization_id,owner_user_id,month)` per binding decision D10 in `STATUS.md`; composite owner-membership FK. Corrections use reopen/re-close revisions on the same plan. |
| `plan_commitments` | id, organization_id, plan_id, activity_version_id, pinned_theme, pinned_scope, display_order, status, created_at | Composite tenant/plan FK. Activity version is immutable and access-checked. Three initial commitments satisfy one-per-scope in one theme; optional private custom additions only Develop. |
| `commitment_schedule_versions` | id, organization_id, commitment_id, version_number, cadence, start_date, end_date, weekdays_json, effective_from, created_at, created_by | Unique commitment/version; immutable schedule definition; enforce date boundaries and timezone semantics in service tests. Weekdays unique integers 0–6. |
| `occurrences` | id, organization_id, plan_id, owner_user_id, commitment_id, generation_date, scheduled_date, schedule_version_id, status, active_scheduled_date generated, lock_version, completed_at, updated_at | Composite FK binds tenant/owner to the parent plan. Unique `(commitment_id,generation_date)` for stable identity and unique `(commitment_id,active_scheduled_date)` for one non-cancelled occurrence per commitment/business date. Cancelled rows generate a null slot and do not block reuse. Index `(organization_id,plan_id,scheduled_date,status)`. Never overwrite generation_date during reschedule. |
| `occurrence_events` | id, organization_id, occurrence_id, actor_id, from_status, to_status, old_date, new_date, event_type, created_at | Append minimal transition/date facts; no private note text. Audits preserve cancellation/reschedule history. |
| `occurrence_private_payloads` | occurrence_id, organization_id, owner_user_id, encrypted_payload, key_version, lock_version | Private note separated from shared metrics. Composite FK requires tenant and owner to match the occurrence/plan lineage. No Champion/analyst serialization. |
| `plan_closures` | id, organization_id, owner_user_id, plan_id, revision_number, closed_at, public_integrity_hash, safe_facts_json, pinned_version_ids_json | Composite FK requires closure tenant/owner to match its plan. Unique `(plan_id,revision_number)`; immutable metric facts from a locked, validated source revision. “Safe facts” means candidate organization aggregates, not permission for public access. Include definition/version reference so historical metrics remain explainable. |
| `plan_closure_private_payloads` | closure_id, organization_id, owner_user_id, encrypted_snapshot, key_version, erased_at nullable | Private full plan text/notes/reflection/reason as needed for own recap. Separate from immutable aggregate facts so approved erasure is possible. Never share this payload. |

### Recurrence and date-collision invariants

Stable occurrence generation identity does not prevent two different original instances from being moved to the same current date. Freeze the intended rule explicitly. Recommended first-release policy: at most one non-cancelled occurrence of a given commitment on a business date. Enforce it with a MySQL generated nullable `active_scheduled_date` (null for cancelled records) and a unique `(commitment_id,active_scheduled_date)` index, or an equivalently tested transactional slot table. Verify the chosen DDL on the exact MySQL version. Do not emulate a PostgreSQL partial index syntax on MySQL.

A cancelled date may be reused only as explicitly defined by the schedule correction rules; reactivating a cancelled occurrence must detect current-date collisions. Retrying schedule generation uses the immutable identity key and must not resurrect cancelled or completed rows. Future schedule edits atomically preserve locked history, retire/replace only eligible future unfinished instances and check new date slots. Avoid hard-deleting audit history merely because the demo reducer reconstructs a list. Lock the plan/commitment aggregate consistently to serialize close/reschedule/complete operations.

### Closure and metrics invariants

Lock the plan while taking a closure snapshot. Reject stale versions and duplicate close attempts idempotently. Preserve incomplete completion state and require the applicable private explanatory reason; do not fabricate completion. Closing and reopening do not mutate existing closure content. Reopening increments `working_revision`. A new close appends that revision. Organization aggregation selects the **latest closed revision per plan** even while a reopened correction remains active.

`scheduled` is total relevant occurrences; `eligible` excludes cancelled; `completed` counts completed eligible occurrences; completion rate is null for eligible zero. Delivered scope coverage uses completed commitments. Store the minimum stable counts and version references needed to reproduce shared summaries; do not store personal narratives in safe_facts.

## 4. Owner-private work

| Table | Important columns | Constraints / indexes / meaning |
|---|---|---|
| `conversations` | id, organization_id, owner_user_id, month, status, guide_version_id, encrypted_payload, key_version, lock_version, timestamps | Payload includes alias, conversation/follow-up dates, three answer blocks, goal and next step. Multiple conversations per month allowed. All metadata and payload owner-scoped; no employer participation counts. |
| `wellbeing_entries` | id, organization_id, owner_user_id, month, working_revision, status, rules_version_id, encrypted_payload, key_version, lock_version, timestamps | Unique `(organization_id,owner_user_id,month)` as recommended monthly logical record; revisions preserve the existing history behavior. Payload contains nine scores, focus and three actions. Total/classification not a plaintext analytics column. |
| `wellbeing_revisions` | id, wellbeing_entry_id, organization_id, owner_user_id, revision_number, encrypted_payload, key_version, created_at | Unique entry/revision. Establish which completion/revision actions append history from actual UI. Include in owner deletion/retention and backup-restoration tombstone handling. |
| `private_export_requests` | id, organization_id, owner_user_id, kind, source_reference, status, storage_key, sha256, expires_at, created_at | Protected object keys; owner check both at request and download/execution time. No name/score in filename or public URLs. Expiring deletion includes regenerated/cached artifacts. |
| `deletion_requests` | id, user_id, organization_id nullable, scope, requested_at, completed_at, status | Authorized erasure workflow with explicit retained facts. Store no deleted sensitive content in the request reason/log. |
| `privacy_tombstones` | id, subject_reference, scope, effective_at, retention_until | Minimal recovery-suppression ledger, held for approved backup horizon. Replay after restore before data is re-exposed. Do not use tombstones as a shadow copy of the deleted content. |

Do not expose existence, counts, completion flags, export-request metadata, notification types or “last well-being use” from private records to the Admin. API list/count routes, health dashboards and support tooling must respect that boundary, not just the detail endpoint.

## 5. Explicit organization sharing

| Table | Important columns | Constraints / indexes / meaning |
|---|---|---|
| `sharing_periods` | id, organization_id, month, current_summary_id nullable, next_version, lock_version | Unique `(organization_id,month)`; the close transaction/outbox serializes automatic report creation and one effective version. |
| `shared_summaries` | id, sharing_period_id, version_number, source_revision_fingerprint, approved_payload_json, payload_hash, shared_at, status, withdrawn_at | Created automatically from the closed revision; unique `(sharing_period_id,version_number)`; immutable allowlisted payload. No Champion approval or browser confirmation. |
| `shared_summary_sources` | summary_id, plan_closure_id | Internal traceability only; never returned to analysts/exports if it exposes owner/plan identifiers. Not a route to private payloads. |

Shared payload shape is exactly the allowlist in `API-CONTRACT.md`. All narrative fields are excluded by construction. Organization name/ID are allowed organization-level information; individual identities are not. Automatic publication does not weaken the allowlist or privacy gate. Recheck Admin organization scope and effective-share status during report generation and download.

## 6. Reliability and operations

| Table | Important columns | Constraints / indexes / meaning |
|---|---|---|
| `idempotency_requests` | id, user_id, organization_id nullable, operation, key_digest, request_hash, result_reference, status, expires_at | Unique scoped key; transaction/retry behavior; same key with different payload rejected. Prefer result reference over a duplicate sensitive response body. |
| `outbox_messages` | id, aggregate_type, aggregate_id, event_type, deduplication_key, available_at, processed_at, attempts | Unique deduplication key; insert in business transaction; dispatch only after commit. Minimal IDs rather than private content. Revalidate permission/opt-in at handling time. |
| `audit_events` | id, organization_id nullable, actor_id nullable, action, object_type, object_id, request_id, occurred_at, metadata_json | Append-only application interface, controlled retention. Explicit metadata allowlist; no personal answer diffs, passwords, tokens, raw requests or sensitive filenames. |
| `installation_identity` | id singleton, dataset_uuid, environment_kind, installed_at | Production dataset identity used by deployment guard; do not trust APP_ENV alone. Provision only by explicit first install. Access/read scoped to operator health checks. |

Use documented Laravel tables for database sessions, cache/locks, queues/batches/failed jobs and password resets as required. Failed job storage must not contain private serialized values. Session/cache/queue internals are not a support-visible source of personal analytics.

## Transactions and operational permissions

Use one consistent locking order (organization/period or plan, then child rows) and bounded deadlock retry only around idempotent transactions. Job retry cannot create duplicate occurrences, emails, closures or shares. Test parallel requests against MySQL, not only sequential unit tests.

Separate runtime DML credentials from migration DDL and backup credentials where feasible. Runtime accounts must not drop databases or manage unrelated schemas. Review every forward migration for locks, backfill volume and compatibility with the previous application image. Keep the data engine/version fixed during a code release. One-time seed factories are local-only; approved production catalogue import is a separate versioned, dry-run-first, non-destructive command.

## Required schema outputs from the implementation agent

Deliver actual Laravel migrations/models/policies; a schema diagram or readable relationship map; indexes and query plan notes for common lists; validation rules; seed factories for isolated synthetic companies; concurrency/privacy integration tests; and a migration compatibility/recovery note. Any change to this contract must identify the actual flow evidence and update API/types/tests together. Do not describe this specification file itself as an installed database.

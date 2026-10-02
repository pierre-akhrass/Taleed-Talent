# Phase 3 Completion Evidence

**Date:** 2 October 2026  
**Scope:** Local authoritative catalogue/planning/scheduling/occurrence/closure/report-read APIs against the frozen v1 contract. All records used in verification were synthetic in the isolated local `talent_test` MySQL schema. No production database, cloud resource, live user data, real email, or deployment was accessed or changed.

## Implemented

- Published source catalogue reads with theme/scope filters, bounded cursor pagination, and approved-version visibility. Custom activity reads are scoped to the authenticated owner and organization.
- Owner-private custom Develop activity create/update/retire commands create immutable versions; bookmarks are idempotent and follow current activity visibility.
- Plan drafts support optimistic versions and authoritative Pick-3 activation. Starter activities are pinned to immutable versions. Extra commitments require the Leader's own available custom Develop activity and a Develop plan.
- Schedule-version creation and replacement preserve original occurrence generation dates and completed/cancelled history. Calendar reads are owner-scoped; occurrence status changes, reschedules, and private-note updates lock the plan and use expected versions.
- Occurrence notes use the existing encrypted payload table and Laravel authenticated encryption. Notes appear only in owner-scoped occurrence responses; event rows contain only a fixed event type, and report serializers never read private payloads.
- Plan metrics are centralized for API responses and closure facts. Superseded rows are excluded from scheduled counts; cancelled rows remain scheduled but not eligible; coverage comes from completed commitments; zero eligible returns null; rounding is percent integer.
- Close/reopen appends immutable closure revisions, requires an explanation for incomplete work without storing it in safe facts, and writes outbox/idempotency records. Automatic reports use the explicit contract allowlist and latest closed revision. `TALENT_ORGANIZATION_SHARING_ENABLED` remains false by default.
- A close/reopen/reclose feature test proves Taleed sees the newer effective report revision without double-counting the plan.
- Idempotency hashes include the target plan/commitment/occurrence ID, so a key cannot replay against a different aggregate.
- Root OpenAPI paths/schemas describe custom Develop activities, bookmarks, added commitments, private occurrence notes, and the existing planning lifecycle.
- `contracts/fixtures/planning-rules.json` is consumed by both PHP and TypeScript for Pick-3, recurrence expansion, cancellation denominator, superseded history, completion rate, and delivered-scope coverage.

## Verification

Final command: `scripts/dev/dev.ps1 test`

- Pure TypeScript domain rules: **41 passed, 0 failed**.
- Frontend typecheck: **passed**.
- Frontend Vitest: **22 passed** across 3 files.
- Frontend production build: **passed**.
- Backend PHPUnit on isolated MySQL 8.4 (`talent_test`): **36 passed, 1,318 assertions**.
- MySQL process-race tests: concurrent activation, schedule-version replacement, reschedule date collision, close, and idempotent reopen all pass with persisted-state/history assertions.
- `vendor/bin/pint --dirty --format agent`: passed after final PHP edits.
- OpenAPI YAML parsing/internal `$ref` resolution: covered by backend feature test and passing in the full suite.
- The app's persistent `talent` database was not used for the full suite. `RefreshDatabase` was directed to the separate synthetic `talent_test` schema.
- During early focused verification, a PHPUnit command inherited Compose's `DB_DATABASE=talent` before the isolated test schema was established. Laravel `RefreshDatabase` may have recreated that local developer schema. Testing was moved to `talent_test` immediately afterward; no automatic restore was attempted. No production database was involved.

## Phase boundary and remaining work

- Sharing remains disabled by default; client confirmation of the small-cohort minimum/field list (G3) is still required before enabling real reports.
- Source document ingestion/rights/approval/publication remains Phase 5. Existing sample catalogue content is not approved source data.
- Conversations and well-being APIs remain privacy/content-gated Phase 5 work; they are not exposed by this Phase 3 slice.
- Owner exports/deletion, report downloads, notification outbox dispatch, and production mail remain Phase 5/6 work.
- React is not connected to these APIs and still contains prototype personas/localStorage behavior; that is Phase 4.
- Local synthetic MySQL concurrency is tested, but GitHub-hosted CI, production operations, source/privacy approval, cloud infrastructure, and deployment remain unrun or separately gated.

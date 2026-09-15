# Engineering architecture and handoff

## Boundaries

This is a client-only React SPA for Talent & Team Development. Statamic/Laravel is a **future approved server implementation**, not a runtime dependency of this prototype. No backend, account provider, cloud database, live PDF store, remote analytics, webhook, email or credential has been configured.

The data path is:

```text
Form / user interaction
  → typed Redux action
  → feature reducer (serializable entity update)
  → selector-driven interface
  → debounced listener
  → strict Zod envelope + reference validation
  → LocalRepository revision check / Web Lock
  → browser localStorage
  → accurate saved / failure / conflict status
```

The storage adapter is centralized in `services/repository.ts`. Components do not call `localStorage` directly. The browser-backed port is supplied once by the store. This permits repository tests with a memory port, without pretending those tests establish browser security.

## Slices and ownership

| Slice | Stored data | Persisted? |
|---|---|---|
| planning | Plans, draft selections/schedules, dated occurrences, closed snapshots | Yes |
| privateData | Conversations and well-being revisions keyed by record ID | Yes, fictional demo records only |
| organization | Organizations, fictional users, invitation states, shared snapshots | Yes |
| catalogue | Immutable published activity versions, draft versions, source metadata, owner bookmarks | Yes |
| preferences | Selected month, week start, RTL preview, reminder preference | Yes |
| session | Selected demo persona | No real session; intentionally in memory |
| ui | Save status, error message, last successful save, failure simulation, toast | No |

Do not store native `Date`, `File`, `Blob`, React elements, errors, Promises, functions, Map or Set instances in Redux. Dates are ISO strings, entities have stable IDs, filters are derived, and file reading happens at the boundary.

The application uses plain normalized ID dictionaries rather than `createEntityAdapter` for a small bounded scenario. Switching to an entity adapter is an implementation option, not a prerequisite for correct Redux architecture. Shared entities must not be separately copied into multiple unrelated slices. Activity copies in plans and immutable snapshots are intentional version records, not competing live state.

## Planning model

A plan is a monthly container, not a flat to-do list. It contains activity commitments. A commitment contains a pinned activity version plus recurrence settings. Occurrences are separately dated records. Their deterministic generation key combines plan, commitment and original date.

Starter selection enforces one theme and exactly one initial activity per scope. Extra custom commitments are available only for Develop. A selected card does not count as delivered. Completion and scope coverage come from occurrence states.

Changing a future schedule deletes only eligible unfinished future occurrences and regenerates future dates; completed/cancelled and past records are retained. A retained commitment/date prevents duplicate generation. Single-occurrence rescheduling preserves the record ID, even when its current date changes; its identity represents the original occurrence, not a database uniqueness guarantee. The production API should additionally enforce uniqueness and audit rescheduling explicitly.

Closing a month creates a snapshot with copied plan/version data, occurrence states, private reflection and reason. Reopening increments the plan revision without changing old snapshots. Summaries select the highest closed revision of each plan, not every revision. An open correction does not erase the last closed revision.

## Safe organization sharing

`safeSummary` constructs a new object from an allowlist. It does **not** serialize a plan and then delete selected keys. This is important: future private fields cannot accidentally leak through object spreading.

Allowed fields: organization ID/name, month, participating leader count, closed-plan count, commitment counts by theme and scope, scheduled/completed/blocked/in-progress/cancelled/eligible occurrence counts, delivered scope coverage, fully covered closed Develop plan count, version and sharing timestamp.

Excluded fields: leader and employee names/emails/IDs, aliases, activity titles/instructions, custom content, notes/reflections, conversation data or activity metadata, all well-being scores/actions/focus/participation signals.

The Champion previews the exact object before sharing. Replacement marks the previous organization/month share as superseded. Withdrawal removes its effective in-app visibility; it cannot recall downloads. The analyst selector only returns effective shares for the explicit Cedar Works demo assignment. Filters must feed both portfolio cards and exports. Company names are allowed organization-level information, not employee identities.

## Persistence and failure handling

Storage key: `taleed.talent.prototype.v2`. Maximum envelope: 2 MB encoded JSON. Version 1 migration is supported only for the same entity model with missing `weekStart`; the migration adds Sunday and promotes schema version 2. Arbitrary old formats are rejected, not guessed.

Read failures preserve the original bytes and block subsequent automatic writes. Imports are parsed, size-limited, strictly shaped and checked for selected referential integrity before a preview/explicit replace. Existing data is never removed before a replacement succeeds. The repository revision advances only after successful storage write.

A save failure keeps the changed Redux state in memory. The UI shows an error, offers retry and can warn on unload. A page crash or hard browser termination can still lose unsaved changes; no guarantee is made otherwise.

A storage event hydrates clean tabs or exposes a conflict in a dirty tab. `navigator.locks` serializes supported writers. Without that browser capability, revision checks alone do not make read/modify/write fully atomic. Multi-tab business editing belongs in a server-backed system.

Private data is not encrypted. Client-side encryption with a bundled key would not fix this and is not implemented. Full developer backup is deliberately restricted to the simulated administrator view and strongly warns that it contains all fictional personas; it is not an approved production permission.

## Validation strategy

- React Hook Form + Zod for registration.
- Shared pure domain rules for Pick-3, dates, recurrence, incomplete close-out and well-being completion.
- Field constraints and explicit error summaries for other forms.
- Zod strict shapes for backup/import/persistence, reserved identifier protection, bounded arrays/text, typed enums and selected owner/reference checks.
- Reducer transition checks for closed/foreign occurrence changes and invitation states.
- Route + record-owner guards for the presentation model only.

These are UX/domain controls. None replace server-side authorization or independently verified content approval. Imported historical occurrence fixtures are not regenerated from current recurrence metadata.

## Source management

The demo stores metadata for seven source documents; it does not store their original bytes or invent hashes. Activity imports are append-only drafts, validated against known source IDs. Publishing a revised activity retires its prior version for new selection while plans retain their pinned content. Source dependency clearance and approval are simulated editorial actions.

Production needs a protected immutable source-file store, actual hashes and permission/attribution records, separate source versus adapted versions, reviewer identity and audit trail. Approval should be a server-side state transition. Never auto-publish on PDF upload or application boot.

## Frontend performance and accessibility

Feature routes use dynamic imports. The shell and UI primitives are shared. Catalogue rendering is bounded/paginated; derived selectors use memoization. Numeric profile graphics have visible input/table alternatives. Native `<dialog>` handles modal focus behavior, cancellation and focus restoration in supporting browsers. Calendar interactions do not require drag and drop.

The design includes labels, visible focus, status text in addition to color, reduced-motion CSS, an agenda alternative, logical layout properties, print-specific reports and responsive navigation. This is an implementation intent, not a WCAG certification. Run Playwright/axe and manual keyboard/screen-reader/responsive checks before acceptance.

## Production migration sequence

1. Verify the approved Carbon/Survey repositories, identity conventions, product design system, Statamic version and hosting/database assumptions. Do not infer infrastructure from this prototype.
2. Approve content/adaptation rights, exact activity/conversation JSON, privacy notices, retention/deletion and disclosure allowlist.
3. Implement authenticated tenant-scoped API contracts, authoritative validation, owner permissions and protected source access in the approved Statamic/Laravel stack.
4. Replace the local repository with API-backed commands and RTK Query queries. Keep transient UI state in Redux; do not mirror the entire API cache into another live entity slice unnecessarily.
5. Implement optimistic concurrency, transactional immutable snapshots/sharing, audit logs and measured backup restoration.
6. Remove persona switching, all-persona backup, registration simulation, failure controls and sample catalogue before any production release.
7. Run integration tests against the real approved database, security tests, accessibility testing and a signed acceptance cycle. No automatic migration of fictional local demo records into production.

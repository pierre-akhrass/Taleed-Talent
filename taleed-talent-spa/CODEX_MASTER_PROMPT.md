# Codex master brief — Taleed Talent & Team Development SPA

**Owner:** Fadi Zahhar  
**Prepared:** 15 September 2026  
**Goal:** Build, validate and hand over a complete high-fidelity React/Redux browser prototype. This is not a request for a plan-only answer or a production backend.

## How to use this file

For the supplied source package, open the project root in your coding workspace and send:

```text
Read AGENTS.md, README.md, CODEX_MASTER_PROMPT.md and docs/VERIFICATION.md.
Execute the master brief in EXISTING-PACKAGE mode. Preserve the established
Talent workflow and local data. Install and pin current stable compatible
dependencies, run the full checks and browser journeys, fix failures, and
record actual results. Do not stop at an architecture plan. Do not deploy,
change an external repository, connect production services or invent passes.
```

For an empty project, provide this file plus the available design/content references and send:

```text
Execute CODEX_MASTER_PROMPT.md in FROM-SCRATCH mode. Build the complete
Taleed Talent & Team Development React/Redux/Vite single-page prototype,
including all user-facing and staff interfaces, validation, local saving,
synthetic scenarios, responsive design, tests and documentation. Follow the
source/brand/privacy boundaries and complete the implementation, not just
the scaffolding. Verify current stable versions before installing.
```

The brief below is standalone. In an existing repository, preserve applicable instructions and unrelated user changes. Do not replace an existing AGENTS.md with this package's example without review.

---

## MASTER IMPLEMENTATION CONTRACT

You are the principal frontend engineer, product interaction designer and QA engineer working with Fadi Zahhar on **Taleed Talent & Team Development** for Aramco Taleed.

The project manager approved the proposed approach and requested a complete interactive demo. Deliver an editable React single-page application with Redux Toolkit, localStorage persistence, complete workflows and high-quality desktop/mobile interfaces. The later production implementation belongs in the approved Statamic/Laravel ecosystem, but **do not build or require that backend in this task**.

### 1. Establish the starting point and then execute

Inspect the working directory, git status, applicable instruction files, package manifests and available references. Determine whether this is an existing-package validation/extension task or a from-scratch build. Summarize material assumptions and a short sequence of milestones, then implement. Do not spend the whole response planning. Do not ask for already supplied details.

In EXISTING-PACKAGE mode:

- Read README, architecture, screen coverage, design/content, verification and acceptance files.
- Preserve the behavior already implemented. Audit its correctness rather than assuming source presence proves execution.
- The supplied package's 41 pure domain checks passed, but external dependencies/build/browser tests were not executed in the authoring environment. Run them; resolve failures; do not repeat the limited earlier results as proof of complete application readiness.
- Do not reset users' browser data or change a schema destructively to make tests pass.
- Work in scoped changes. Do not discard unrelated local work, force-push, rewrite history or deploy without explicit authorization.

In FROM-SCRATCH mode:

- Build the complete file structure and all interfaces described below.
- Start with the shell, tokens, types and store, then implement one end-to-end plan flow before expanding the rest.
- Use real components and reusable contracts rather than one giant component or disconnected static mockups.
- Keep deterministic fictional fixtures in one seed module and hydrate once only when storage is genuinely absent.

### 2. Correct product definition

This is a manager-first leadership-practice workspace:

**Choose → Plan → Do → Reflect → Repeat.**

A leader selects practical activities, schedules them, records follow-through, reflects on the month and repeats. A private guided development conversation and an optional personal well-being wheel complement the main journey. An SME Champion may explicitly share a limited organization-level implementation summary with the assigned Taleed team.

This is **not** the Procurement Self-Assessment tool, the Carbon calculation tool, an HRIS, payroll platform, formal performance review, medical service, AI coach, employee surveillance system or maturity/retention scoring system. Do not import business rules from those other Taleed products merely because they share a design system.

### 3. Stable dependency and execution policy

Use a Vite React TypeScript SPA. No Next.js, SSR, server components, Firebase backend or mandatory API. Use `createRoot`, function components and current supported APIs.

Required runtime packages:

- `react` and matching `react-dom`;
- `@reduxjs/toolkit` and `react-redux`;
- `react-router-dom` for client routing;
- `react-hook-form`, `@hookform/resolvers` and `zod` for appropriate form/boundary validation;
- `lucide-react` for consistent interface icons, not a substitute official logo.

Required development tooling:

- TypeScript, Vite, official React Vite plugin and current React/DOM/Node types;
- Vitest, jsdom, Testing Library and user-event;
- Playwright and axe integration for browser/accessibility checks;
- a formatter if needed for maintainable source.

Verify actual stable npm versions and official Node/peer requirements when executing. Never assume this document's release snapshot remains latest. React 19.3 was the verified baseline when the package was prepared; recheck rather than hardcoding an unsupported future claim. Node 24 is the initial project baseline; honor stricter requirements from resolved dependencies.

Use stable releases only, not canary/next/RC. Resolve versions, review compatibility, write exact pins and a real lockfile. Do not bypass errors with `--force` or `--legacy-peer-deps`. If the newest stable combination is incompatible, report the conflict and use a documented supported stable combination rather than pretending all packages can be upgraded blindly.

Provide `dev`, `build`, `preview`, `typecheck`, `test`, `test:e2e`, `check` and version-recording scripts. Keep package-install and test logs truthful. Later clean installations must use `npm ci` against the reviewed lockfile. Do not fabricate a package-lock or an installed-versions report.

Default to hash routing with a relative Vite base for static/subdirectory demo hosting. Do not assume a production rewrite rule exists. Document localhost and static hosting. Do not push or deploy automatically.

### 4. Source and design fidelity

Use the approved Carbon/Survey product kit and actual Taleed design references first, when available. The reference order is:

1. Approved product components, tokens, screenshots and supplied Figma nodes;
2. Approved Taleed library/assets;
3. Official public site as secondary visual context;
4. Clearly labeled provisional design when evidence is unavailable.

User reference: `https://fadizahhar.github.io/taleedtoolsprototypes/`. Official contextual site: `https://www.aramcotaleed.com/en`. Access authorized repository content through the provided connector/workspace where applicable. Never treat an outer bundled-page loading thumbnail as proof of the inner official design system.

Do not redraw a corporate logo, ship unlicensed fonts, invent official color names or claim pixel-perfect brand reuse without the real kit. In this supplied package, navy `#0a1d5c`, blue `#1741c9` and amber `#f0a93b` are explicitly provisional colors echoed from the reference wrapper. Keep them easy to replace in semantic tokens.

Use a calm light workspace, clear task hierarchy, generous spacing, restrained elevation, consistent controls and deliberate accent use. Prioritize readable forms, meaningful next actions and clear states. Avoid decorative dashboards, arbitrary charts, excessive animation and wellness gamification.

Cover desktop 1440, compact desktop/tablet 1024/768 and mobile 390. Mobile needs real navigation/agenda/card adaptations, not a scaled-down desktop canvas. Use logical CSS for RTL readiness. English is the supplied content language. An RTL switch previews direction; do not call it a full Arabic translation.

### 5. Content contract

Four themes: `care`, `develop`, `enable`, `recognition`.

Three scopes: `individual`, `culture`, `team`.

The source structure is 72 activities: 18 per theme, six per scope in each theme. Scope comes from structured source metadata, not a guessed worksheet column order. Use approved source JSON when available; preserve exact original text separately from approved adaptations, stable IDs and source/version provenance.

The source catalogue and exact conversation guide may be unavailable in a from-scratch workspace. Proceed using explicitly synthetic sample copy to demonstrate behavior, but never label it an original transcription or approved Aramco content. Record this limitation prominently in the UI and source manifest. The three known source-equivalent examples are:

- Find mentors to learn from — Develop / Individual;
- Designate development time — Develop / Culture;
- Lunch-n-Learns — Develop / Team.

Do not fabricate missing internal links, dimension definitions, recognition guide, toolkits, resource downloads, source hashes or PDF contents. Do not automatically repurpose the development guide as the missing recognition guide.

Original PDF publication/adaptation rights require approval. Do not embed confidential source documents in a public prototype or send real employee records to external AI services. An upload is not publication approval and not permission to invent digital content.

### 6. Identity, roles and demo disclosure

Provide a persistent “Interactive demo — fictional data only — stored in this browser” disclosure. A clearly labeled persona switch should make all journeys presentable.

Roles:

- **Team Leader:** own plans/occurrences, notes, conversations, optional well-being, history and own exports.
- **SME Champion:** own leader capabilities; own-company member invitations/settings; safe closed-plan aggregate; exact preview and explicit monthly summary sharing. No access to another leader's private content.
- **Taleed Analyst:** assigned-cohort effective shared organization/month snapshots and permitted safe export only. No private drill-through.
- **Content Administrator:** source dependencies, structured content drafts/versions/publication. No participant private-workspace inspection.

Use a fictional second leader in the same company and a second organization to demonstrate owner/organization filtering. Guard routes and record selectors, not merely menu links. These are presentation controls, not secure authorization.

Simulate account registration, verification, recovery and invitations transparently. Never claim an email was sent. Do not persist passwords or use demo persona IDs as real credentials. Domain/name matches must not automatically grant existing-company membership. Invitation acceptance should model exact address, pending/expired/revoked/used states and a single-use transition.

A developer-only full backup console can exist for fictional records but must explicitly warn that it exposes all personas and is not a production content-administrator permission. Browser users can read localStorage regardless of hidden menus. Never claim real tenant security or encrypted privacy from this SPA.

### 7. Required route and screen coverage

Represent all T01–T34 contracts; related states can share templates. Use a route/state coverage matrix in documentation.

Public/setup: introduction; demo entry; registration and field errors; verification/resend simulation; invitation acceptance and exceptions; company setup/existing-company resolution; first-use orientation; help/privacy explanation.

Leader: overview; activity library/filter/bookmarks; full activity/version detail; resource overview; persisted Pick-3 wizard; recurrence editor; real calendar/agenda; occurrence status/private note; custom Develop activity; month close/reflection; correction; personal history; conversation preparation/notes/recap; optional well-being introduction/nine inputs/profile/actions/history; own report centre; account/preferences.

Champion: members/invitations; organization settings; selected-period closed-plan aggregate; exact disclosure preview; explicit share; shared versions; correction/replacement/withdrawal.

Taleed: assigned shared portfolio; period filter; summary detail; exact export preview; JSON/CSV or printable aggregate report with the same filtered set.

Content administration: seven-source inventory, approval/dependency flags, draft activity import, source/adapted comparison, draft save, gated publication and historical version preservation.

Cross-cutting: loading, empty, not found, forbidden, no selected identity, unavailable source, save pending/success/failure/retry, stale tab, corrupted/unsupported backup, confirmation/destructive states, print layout and mobile navigation. Do not fake server errors or asynchronous jobs for services that do not exist.

Every primary action must have a meaningful behavior or an explicit scope explanation. No decorative buttons masquerading as implemented workflow.

### 8. Domain rules that must not be weakened

**Pick-3:** one initial activity per scope, all in one theme. Duplicates, wrong-theme/scope and retired/unavailable content do not satisfy readiness. A second selection in an occupied scope offers Replace. No obligatory four-theme or 12-activity month.

**Scheduling:** monthly container; daily/weekly practices; chosen weekdays; one-off Develop events; custom Develop only unless a separate content decision permits more. Keep valid calendar dates within the selected month, respect leap years and configurable week start, and document organization timezone. Actual calendars support four/five/six display rows.

**Occurrences:** separate commitments from dates. Generate deterministic IDs and prevent duplicates on repeated generation. Support scheduled/in-progress/blocked/completed/cancelled. One completion affects one occurrence. Reschedule one versus future occurrences explicitly; retain completed/cancelled/past history. Cancelled records remain visible.

**Completion:** completed noncancelled occurrences divided by all noncancelled scheduled occurrences in scope. Blocked, in-progress and still-scheduled remain in the denominator. Zero eligible denominator displays “No scheduled activity,” not 0% or 100%.

**Delivered coverage:** distinct scopes represented by completed work. Selecting three cards is not delivering three scopes. Repeated individual work cannot satisfy culture or team coverage. A fully covered Develop plan requires completed Develop commitments across all three scopes.

**Close-out:** allow honest incomplete closure with an explanation. Store an immutable closed snapshot including pinned content version and private reflection. Reopening creates a new revision; earlier snapshots remain. Use one latest closed revision per plan in an organization summary.

**No unsupported outcomes:** self-reported practice is not demonstrated employee retention, happiness, organizational maturity or medical well-being. No rankings or invented impact claims.

### 9. Conversation and well-being details

Conversation structure: six guidance steps, three core questions and seven contextual follow-ups. Use exact source wording only when actually supplied. Otherwise keep copy explicitly illustrative. Optional fictional colleague alias, date, notes, user-authored goal/action and follow-up date are sufficient. No employee directory, salaries, audio recording, automated sentiment or formal ratings.

Well-being is optional and separate. Dimension order clockwise from top: Physical, Emotional, Social, Spiritual, Intellectual, Occupational, Environmental, Financial, Digital.

Use explicit integers 1–10, initial value null, no preselected scores. Eight ratings are incomplete and must not generate a total. All nine produce a numeric total/profile; completion additionally needs a selected focus dimension and exactly three nonempty user-authored actions. Do not automatically select the weakest dimension or prescribe actions.

The example 8+7+8+3+6+3+6+8+5 totals 54. All ones total 9; all tens 90. Reject decimals, booleans, numeric strings in imported score values, unknown dimensions and out-of-range values. Preserve history and correction revisions. Do not apply the overlapping source bands at 35 and 60 or any clinical/“thriving” classification without separately approved rules.

No well-being fields, events, completion counts or participation signals may enter organization/Taleed summaries or exports. Do not make participation a prerequisite for the main tool.

### 10. Redux and persistence design

Use feature slices, typed dispatch/selectors and serializable normalized entities. Keep transient presentation/form state local unless it must survive routing; persist multi-screen drafts through Redux. Do not subscribe entire views to the root state or duplicate derived metrics. Use memoized selectors where projections allocate arrays/objects.

Separate domain logic into pure functions. Activity copies in commitments and snapshots are intentional immutable version records. Prefer a small, inspectable model to overengineering. Do not add Redux Persist blindly; implement or review a validated, versioned repository that meets the actual recovery rules.

Use one debounced listener/repository persistence pipeline with:

- namespaced versioned envelope and bounded size;
- explicit compatibility migrations, not arbitrary guessing;
- safe initial read: seed only if genuinely absent;
- strict schema validation before import/save;
- accurate saving/saved/failure status after actual write outcome;
- preserved in-memory unsaved edits on failure;
- stale-revision checks, storage events and Web Locks where supported;
- controlled reload/resolve, not silent last-writer-wins;
- original-byte export for corrupt data;
- validation/preview/confirmation before replacement;
- atomic replacement without deleting the old snapshot first;
- explicit all-data reset with warning;
- no normal software update reseeding/resetting user data.

LocalStorage is only a prototype data store. Do not call it secure, server-backed, cross-device synchronized or suitable for sensitive production data. Do not invent an encryption key and claim this solves browser access.

### 11. Exact sharing allowlist

Construct a fresh safe object from approved aggregate fields. Never serialize an entire plan/private state and rely on deleting a few fields.

Allowed: organization ID/name, month, participating leader count, closed-plan count, commitment counts by theme and scope, scheduled/completed/blocked/in-progress/cancelled/eligible occurrence counts, delivered scope coverage, fully covered Develop-plan count, version and sharing timestamp.

Excluded: leader/employee IDs/names/emails, aliases, custom or standard activity narratives, titles/descriptions, notes/reflections, conversation content/metadata and all well-being content/participation.

Champion preview must match the exact stored/shared/exported object. Sharing is explicit and independent of a Taleed review gate. Keep one effective organization/month revision; preserve superseded history. Withdrawal removes in-app access but cannot recall downloads. Analyst cards, tables and exports must use the same filters and effective revisions. Not shared is not zero performance.

### 12. Files and reporting

Provide own-report previews with owner checks at the route and selection boundary. Require a warning before personal JSON export or printing. Browser Print / Save PDF is acceptable for this SPA when labeled honestly. Do not fabricate a cloud-generated PDF job, private link expiry service or email attachment system.

CSV exports must quote/escape cells and neutralize formula-leading values. Calendar `.ics` files are exports, not live synchronization; exclude private notes and cancelled occurrences. Clearly state which fields are exported. Revoke object URLs after downloads.

Backup and content imports are separate workflows. A backup replaces a validated whole demo after confirmation. Content import appends validated unpublished new versions, rejects duplicate IDs and does not overwrite existing plans or approved source versions.

### 13. Test fixture and verification contract

Use fictional **Cedar Works** and **Dune Studio**, two leaders in Cedar, a Champion, an assigned Taleed analyst and a content administrator. Seed a current active plan and a previous closed plan.

Required closed fixture: 5 scheduled occurrence records: 2 completed, 1 in progress, 1 blocked, 1 cancelled. Eligible denominator 4, rate 50%. Delivered individual/team only, coverage 2/3. Clearly label historical fixture records rather than silently regenerating them into another count.

Test at least:

- stable catalogue counts/scope mapping and unavailable content;
- balanced Pick-3, duplicate/mixed-scope/theme failures;
- real date/leap/month/year/week-start and recurrence behavior;
- idempotent generation and history-preserving schedule edits;
- completion denominator/zero case and scope delivery;
- incomplete close-out, corrections and effective revision aggregation;
- strict allowlist and no private leakage in shared UI/exports;
- nine-input wheel arithmetic, blank/no default behavior, focus/actions;
- invitation replay/expired/revoked/wrong-email states;
- owner/company route and selector boundaries;
- failed local write, corrupt read, unknown schema, quota, stale tab and atomic replacement;
- source draft/publication gates and unchanged historical content;
- desktop/mobile page flows, overflow, keyboard/dialog focus, reduced motion and automated axe;
- print/export content and refresh survival.

Actually execute full typecheck, unit/integration tests, build and browser journeys after installation. Fix failures without weakening assertions. Capture screenshots of real executed pages at desktop/mobile widths and record the tested build. Do not produce a static mockup screenshot and call it an executed React view.

If the environment prevents a command, state the exact blocker, keep source work complete as far as possible and mark affected results Not run/Blocked. Never fabricate a pass, a working deployment or exact installed versions.

### 14. Delivery requirements

Deliver the full project with README, real lockfile after installation, scripts, reusable source/components/tokens, synthetic fixture manifest, route/screen map, state/persistence architecture, source/brand decision register, test evidence and acceptance checklist.

Include a ready-to-present walkthrough from leader planning through private tools, Champion sharing, analyst view, content revision and failure recovery. Distinguish what was implemented, what was actually tested and what remains a production responsibility.

Use a short AGENTS.md for persistent repository constraints; keep this larger product contract separate. Document future Statamic/API seams without implementing unauthorized backend infrastructure. No credentials, real employee data, font files, restricted PDFs, paid services, automatic deployment or external repository writes.

The final handover should report commands/results, exact installed versions, implemented route groups, demo identities, source/design limitations and remaining blockers. Finish with concrete artifacts and evidence, not a promise to build later.

---

## Suggested milestone sequence

1. Inspect source/instructions/references; verify stable dependency compatibility and lock.
2. Review shell/tokens/types/store/repository and seed safety.
3. Complete one full leader plan → occurrence → close → report path.
4. Complete optional conversation and well-being paths with disclosure tests.
5. Complete Champion → safe shared snapshot → analyst/export path.
6. Complete invitation and source/version administration workflows.
7. Validate failure/recovery, mobile/RTL/keyboard/print and all acceptance tests.
8. Record actual results, screenshots, decisions and a reproducible handover.

Proceed milestone by milestone. Stop only for a genuinely unsafe external/destructive action requiring authorization—not because the implementation is large.

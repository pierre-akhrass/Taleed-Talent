# Taleed Talent & Team Development

**React + Redux Toolkit + TypeScript single-page prototype**  
Prepared for **Fadi Zahhar** · 15 September 2026

A source implementation of the proposed Taleed leadership-practice workspace:

**Choose → Plan → Do → Reflect → Repeat.**

This package is the **Talent & Team Development** tool, not the Procurement Self-Assessment tool. It contains editable application source, local demo persistence, role-specific journeys, validation rules, tests, a presentation walkthrough and a complete Codex build brief.

> **Verification boundary:** 41 pure domain/seed tests, a four-module strict TypeScript check, and syntax/static-import checks across 27 source files were executed successfully when this package was prepared. Package registry downloads were unavailable in the authoring environment. Consequently dependency installation, full application typechecking, Vite build, Redux/Zod integration tests and Playwright/browser/accessibility tests were **not executed**. No compiled `dist/`, fabricated lockfile, deployment or production-readiness claim is included. Run the validation commands below before presenting this build to the client.

## First run

Use Node 24 as the project baseline. The manifest allows Node 22.12+, but the latest dependency releases may impose stricter engine requirements; the bootstrap checks those rather than bypassing them.

```bash
cd taleed-talent-spa
node scripts/bootstrap.mjs
npm run check
npm run dev
```

Open the localhost address printed by Vite, normally `http://localhost:5173`.

The first command resolves stable npm `latest` tags, checks that React and React DOM match, writes **exact versions**, installs with strict peer/engine checks, and creates a real `package-lock.json`. It does not use `--force`, `--legacy-peer-deps` or prereleases. If the registry is unavailable or packages are incompatible, it stops with an error. The previous manifest is saved as `package.bootstrap-backup.json`.

Once the resolved dependency graph has been reviewed and committed, use:

```bash
npm ci
npm run dev
```

Do not rerun a dependency upgrade immediately before a presentation. `node scripts/bootstrap.mjs --refresh` intentionally refreshes the stable versions and must be followed by all checks.

The supplied manifest starts from React/React DOM **19.3.0** and Redux Toolkit **2.12.0**, with unresolved stable tags for the remaining packages. This is a bootstrap manifest, not an already installed/verified dependency graph. `docs/installed-versions.json` is generated only after successful installation; no installed versions have been invented.

### Validate and build

```bash
npm run check:source
npm run test:domain
npm run check
npx playwright install chromium
npm run test:e2e
npm run build
npm run preview
```

`npm run check` runs dependency-aware TypeScript checking, Vitest and the production bundle build. `npm run test:domain` runs only the dependency-light domain suite. `npm run test:e2e` exercises desktop/mobile Chromium and an automated accessibility scan. Browser tests were authored, not run here; any failure must be investigated rather than marked passed.

The application uses hash routes and Vite `base: './'` for straightforward static subdirectory hosting. Serve `dist/` after a successful build. **Do not double-click `index.html`**; source modules need Vite, and browser storage under `file://` is not the intended execution environment. No GitHub repository was changed and no deployment was performed.

## What is implemented

| Workspace | Implemented interfaces and actions |
|---|---|
| Introduction & setup | Purpose, demo entry, registration form validation, explicitly simulated verification/recovery, company setup, membership collision warning, orientation, invitation acceptance with used/expired/revoked/wrong-email states. |
| Leader overview | Current-month plan, next actions, completed/eligible counts, delivered scopes and next useful actions. |
| Activity library | 72 illustrative cards across four themes and three scopes; search, theme/scope filters, bookmarks, details and version/source labels. |
| Monthly planning | Persisted four-step draft; exactly one starter per scope within one theme; replacement confirmation; daily/weekly/one-off Develop scheduling; validation and activation. |
| Follow-through | Calendar and agenda, actual month navigation, configurable week start, occurrence status and private notes, single-occurrence rescheduling, future recurrence changes, custom Develop commitments, `.ics` file export. |
| Close & reflect | Honest incomplete close-out with explanation, private reflection, immutable closed snapshots, correction revisions, history and own reports. |
| Conversations | Six-step illustrative guide, three note sections with seven follow-up prompts, fictional alias, goal, next action and follow-up date, personal recap/export. |
| Optional well-being | Explicit privacy introduction, nine unanswered 1–10 inputs, numeric profile only after all nine are rated, chosen focus, three actions, completion, corrections and private history. |
| Champion | Own leader journey, member list, simulated leader invitations, organization settings, eligible closed-plan summaries, exact allowlist preview, explicit share, replacement revisions and withdrawal. |
| Taleed analyst | Current effective shared summaries for the assigned fictional cohort, period filters, aggregate statistics, detail and permitted safe JSON/CSV exports. |
| Content administrator | Seven source metadata records, dependency review, append-only draft JSON import, prior/adapted content comparison, draft revisions and gated demo publication. |
| Reports | Own monthly/conversation/well-being previews, explicit private JSON export warning, browser Print / Save PDF; shared exports contain only aggregate fields. |
| Settings & recovery | Calendar/RTL/reminder preferences, save-failure simulation and retry, stale-state recovery, personal export/deletion, administrator-only development backup/import/reset console. |
| Cross-cutting | Persistent demo disclosure, role switch, source/brand caveats, lazy routes, accessible native dialogs, visible focus, error/empty/forbidden states, mobile navigation, print styles and reduced motion. |

Related states intentionally share responsive templates. See `docs/SCREEN_COVERAGE.md` for T01–T34 coverage and honest differences from a production system.

## Demonstration identities

All identities and organizations are fictional. There is no real login or password database.

| Persona | Role | Intended demonstration |
|---|---|---|
| Amina Hassan | Team Leader · Cedar Works | Default: active September 2026 plan and a closed August fixture. |
| Omar Nasser | Team Leader · Cedar Works | Empty workspace and another leader’s personal-record boundaries. |
| Noor Saleh | SME Champion · Cedar Works | Invitations, organization setup and explicit aggregate sharing. |
| Rana Ali | Team Leader · Dune Studio | Separate fictional organization; no Cedar plans. |
| Mariam Ahmed | Taleed Analyst | Assigned to Cedar Works only; sees shared summaries, not private data. |
| Hassan Abdullah | Content Administrator | Source inventory and demo-only recovery console. |

Use the **Demo persona** selector under the header. Role switching is a presentation aid, not authentication. Signing out only exits the demo view; it does not delete the browser’s records.

The seeded August record is deliberately incomplete: **5 scheduled, 1 cancelled, 4 eligible, 2 completed = 50%; individual/team delivered, culture not delivered**. It demonstrates honest reporting rather than artificially perfect completion. This is a historical occurrence fixture; its records are authoritative for the snapshot, not regenerated from its last schedule definition.

## Application structure

```text
src/
  app/                 # Store, domain slices, typed hooks, selectors, route composition
  components/          # Reusable UI primitives, responsive shell, dialogs
  data/                # Synthetic scenario, illustrative catalogue and source metadata
  domain/              # Serializable types, pure calendar/scoring/summary rules
  features/
    home/              # Leader overview
    onboarding/        # Simulated account and invitation journeys
    library/           # Activities, bookmarks, resources
    planning/          # Pick-3, scheduling, close-out and corrections
    calendar/          # Calendar/agenda and occurrence editor
    reflection/        # Conversations, well-being, history and personal reports
    organisation/      # Champion sharing, members and analyst portfolio
    admin/             # Content versions and dependencies
    settings/          # Preferences, backup/import, recovery and help
  services/            # Validated local repository and file export adapter
  styles/              # Replaceable semantic tokens and responsive/print styles
scripts/               # Stable-version bootstrap, version evidence and source/domain checks
tests/                 # Authored Vitest domain, reducer and repository tests
e2e/                   # Authored Playwright desktop/mobile journeys
.github/workflows/     # Check-only CI; requires a reviewed lockfile; does not deploy
CODEX_MASTER_PROMPT.md  # Full reconstruction/extension implementation contract
AGENTS.md              # Repository-specific engineering boundaries
```

## State management choices

Redux Toolkit holds serializable domain entities in feature slices: planning, private records, organization, catalogue and preferences. Plans, snapshots and occurrences are normalized by ID; activities are intentionally copied into commitments to preserve historical wording. User/session selection and save/toast states are separate and not persisted as real credentials.

Selectors are typed and derived projections are memoized. Form-only state, modal visibility and unsaved modal fields stay in React; multi-screen plan drafts live in Redux. Pure functions calculate recurrence, eligible completion, scope coverage and summary payloads rather than storing contradictory derived metrics.

A listener middleware debounces domain writes by 350 ms through one repository. JSON is validated before persistence; saved status appears only after `setItem` succeeds. Storage is namespaced and schema-versioned. Corrupt or unknown formats are not silently replaced. Web Locks serialize writes where available; revision checks and storage events detect stale state. Browsers without Web Locks do not gain equivalent concurrent-write guarantees.

There is intentionally no RTK Query server cache in a zero-backend prototype. A later approved API should use a server-backed adapter/RTK Query endpoints, with authoritative authentication, authorization, validation, tenant isolation and concurrency on the server. It must not turn localStorage into a production database.

## Design and content boundary

This is a coherent **provisional Taleed-inspired application direction**, not verified pixel-perfect reuse of the Carbon/Survey kit. Navy, blue and amber echo the supplied prototype’s preview wrapper; other semantic tokens are provisional. No official logo was redrawn and no licensed font files are bundled. Replace tokens and authorized assets after product-design review. See `docs/DESIGN_AND_CONTENT.md`.

The 72 cards preserve the proposed four-theme / three-scope structure, but are **illustrative copy, not the original source catalogue**. Only three known source-equivalent activity titles are identified as such. The original PDFs, exact source JSON and exact conversation questions were not available as usable local assets during this build. They are not fabricated or embedded. The content-admin import is the route for authorized, validated draft data—not automatic PDF interpretation.

The application is English-first. The RTL switch previews layout direction only; it does not claim an approved Arabic translation.

## Privacy and production boundary

Use only fictional information. Every record can ultimately be read or altered by someone who controls the browser. The all-persona backup console is an explicitly labeled development feature, not a production content-administrator entitlement. Do not expose it in a real service.

Sharing allowlists exclude names, emails, aliases, custom titles, free text, private notes and **all conversation/well-being content and participation signals**. Downloaded files cannot be recalled. No clinical labels, employment appraisals, company rankings, AI coaching, tracking scripts, real notifications or external calendar synchronization are implemented.

Client-facing production acceptance must separately establish content rights, approved design assets, real identity and tenant controls, protected server persistence, retention/deletion policy, source versioning, backups/recovery and security testing.

## Documentation

- `docs/DEMO_WALKTHROUGH.md`: ready-to-present journeys and expected results.
- `docs/SCREEN_COVERAGE.md`: all 34 prior screen contracts mapped to interfaces/states.
- `docs/ACCEPTANCE_CHECKLIST.md`: client/demo UAT with unrun results clearly marked.
- `docs/ARCHITECTURE.md`: state, persistence, disclosure and future Statamic handoff.
- `docs/VERIFICATION.md`: actual checks versus authored/unexecuted tests.
- `docs/SOURCES.md`: source/context provenance and current official technical references.
- `CODEX_MASTER_PROMPT.md`: build from scratch or validate/extend this package.

This package is ready for installation, development and structured review. It is not a verified compiled release or a production deployment.

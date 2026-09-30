# Evidence-based repository audit

Prepared 30 September 2026. Source: the user-uploaded `Taleed-Talent-main.zip`. These observations describe that archive, not an unseen newer working tree or a live GCP environment.

## What was inspected and executed

The archive has a repository-level GitHub Pages workflow and a nested `taleed-talent-spa/` project. The application source, domain model/functions, reducers, selectors/persistence boundary, feature flows, manifests, workflows and accompanying technical documents were inspected. There are no original PDF files, Laravel/Statamic backend, Composer project, Docker environment or real authentication implementation in this archive.

Two existing dependency-light scripts were executed in an inspection copy:

| Command, from `taleed-talent-spa/` | Observed result | What it does not prove |
|---|---|---|
| `node scripts/check-source.mjs` | Passed: 27 TS/TSX files checked for syntax/static relative imports. | Full TypeScript semantic checking, dependency resolution, React runtime or production build. |
| `node scripts/test-domain.mjs` | 41 passed, 0 failed. | Redux/Zod integration, browser behavior, server authorization, persistence, Docker or deployment. |

The scripts used Node 22.16.0 and the available TypeScript 5.8.3 fallback, **not the project's installed dependency graph**. Their actual JSON outputs are in `evidence/`. `npm ci`, full typecheck, Vitest, browser tests, production build, PHP integration, Docker and live deployment were not run during this review. Existing test passes do not certify source-content or privacy approval.

The downloadable augmented repository preserves application source bytes. Instruction/documentation additions do not fix the findings below by themselves.

## Findings that determine the implementation

| ID | Evidence in this ZIP | Required treatment |
|---|---|---|
| A01 | `AGENTS.md` inside the frontend prohibits introducing a backend; the historical master prompt is a browser-prototype contract. | Supersede only demo-only constraints through the current user-authorized conversion; retain product and privacy safeguards. Archive the original instructions. |
| A02 | `src/services/repository.ts` persists the complete demo envelope at `taleed.talent.prototype.v2`; `src/data/seed.ts` holds fictional users and records. | Replace browser authority with server APIs. Never import this multi-persona dataset into production or preserve full-state browser backup controls. |
| A03 | `src/domain/types.ts` models company IDs, owner IDs, drafts, commitments, occurrences, closures, conversations, wheel records and explicit shares. | Normalize these actual concepts into relational tables with owner/tenant constraints and immutable versions, rather than using a generic survey schema. |
| A04 | `src/app/App.tsx` uses `HashRouter`; Vite base is relative. | Preserve current navigation initially; deliberately implement `/app/*` routing and legacy hash handling with tested deep links if adopting browser history. |
| A05 | `src/domain/logic.ts` contains reusable Pick-3, date, recurrence, metrics, closure-selection and allowlist-summary functions. | Port rules to authoritative PHP services and shared test fixtures; keep client validation as UX, not security. |
| A06 | `wheelClassification` selects bands with cutoffs 34, 47, 59 and 71; Wellbeing and Reports render classifications. Original instructions require numeric-only pending approval. | Disable classifications until a versioned, non-overlapping rule has explicit approval; test 35 and 60 across UI/API/reports. Do not infer that general demo approval resolved this. |
| A07 | `src/data/catalogue.ts` contains 72 illustrative/source-equivalent cards, seven source metadata records, generic steps and illustrative conversation guidance. | Validate against originals and approved text. Metadata presence is not possession, correct hashing or publication rights. Keep source/adaptation approval separate from UX approval. |
| A08 | README version/bootstrap claims differ from `package.json` and `package-lock.json`; several manifest versions are `latest`. | Record installed/locked/declared versions separately, resolve compatible exact pins, use real lockfiles and correct documentation. Do not rerun an indiscriminate latest-version bootstrap. |
| A09 | `.github/workflows/deploy-pages.yml` is at repository root, builds and deploys on `main`; the quality-check workflow is under `taleed-talent-spa/.github/workflows/check.yml`. | Create actual root quality gates with correct working directories. A nested workflow is not a repository workflow. Make Pages explicitly demo-only or disable its automatic trigger before production conversion is merged. |
| A10 | Closed snapshots intentionally contain private reflection/reason and copies of plan data; summaries are constructed from selected aggregate fields. | Split safe closure facts from protected personal payloads. Preserve immutable integrity without making private fields reachable through exports or generic ORM serialization. |
| A11 | Occurrence IDs encode original generation identity while rescheduling changes the current date. | Model original date separately from current date; enforce collision/uniqueness rules under concurrent transactions. Do not recreate completed history during schedule changes. |
| A12 | `PACKAGE_MANIFEST.json` is dated 15 September and lists a prototype snapshot. | Treat it as historical, not a checksum certificate for subsequent user edits or this augmented delivery. Use the new delivery manifest for this package. |

## Dependency observations — not an installed-version claim

The uploaded lockfile records React/React DOM 18.3.1, Redux Toolkit 2.12.0, Vite 8.3.0, Vitest 5.0.1, TypeScript 7.0.2, React Router DOM 7.18.4, Zod 3.25.76 and jsdom 29.0.1. The declared Node floor is at least 22.12.0; `.nvmrc` names Node 24. Resolve the actual supported matrix and run it before changing versions or asserting compatibility. The full dependency graph was not installed or security-audited in this review.

## Prior documents and the original well-being PDF

Prior Talent blueprint, implementation/design prompt and acceptance/decision documents dated 14 September were retrieved from the user's Library. They describe seven PDFs, ten pages, 72 source activities, owner-private personal work and deliberately limited organization sharing. Their historical SQL Server references are not a restriction on this user's new MySQL decision and do not establish any live database engine.

The original `Well-being%20Wheel%20Activity.pptx.pdf` was separately retrieved and both pages were visually reviewed. Its printed bands overlap at 35 and 60. The nine dimensions and 1–10 radial scale are visible in the source. The remaining six original PDFs were not independently located in this review; the prior audit describes them, but current agents still need authorized originals/approved reference content before publication. No original PDFs are redistributed in this package.

## Sibling deployment evidence actually reviewed

The connected GitHub repository `FadiZahhar/sustainability-diagnostic-tool` was read at commit `2b8ec66c55f8371e69715c55dfc82e18480603dd`. This was a targeted read, not an audit of the whole repository or its server.

- `compose.prod.yaml`, dated 11 September in the connector, describes a dedicated Compute Engine VM, MySQL 8.4, database-backed sessions/cache/queues, app/worker/migration services, private MySQL networking, persistent content/user/upload/TLS volumes and an edge owning ports 80/443. It avoids persisting `bootstrap/cache` across releases. The file names `/opt/taleed-diagnostic` and a dedicated diagnostic VM; it does **not** prove a shared VM with Survey.
- `scripts/prod/backup-remote.sh` describes pre-release/nightly transactional MySQL dumps, file-volume archives, metadata/checksums and an off-VM GCS copy. Reuse the intention, not blindly the script: verify coordinated database/file consistency, required-volume failure behavior, offsite manifest completeness, secret recovery and an actual restore.
- `docs/operations/production-safety.md`, dated 20 August, retains a managed-database direction inconsistent with the newer Compose configuration. Its good immutable-release/backup/rollback principles remain useful; its old hosting choice is not the new Talent architecture.

Reference production Compose currently uses ordinary named volumes and owns an edge port pair. Talent should strengthen missing-volume detection with fixed external storage identities, and must not copy that port binding onto an already occupied shared VM. Wildcard trusted-proxy settings also need re-evaluation under the actual chosen network topology.

The connected repository search did not identify the Survey repository. This says nothing about its availability on the user's computer. The user's local filesystem, local sibling working trees, GCP console, VM, static IP, DNS, SMTP credentials and live backup/restore health were **not** inspected. The local implementation agent must locate authorized reference workspaces and obtain explicit authorization before any live infrastructure access.

## Recommended outcome

Retain the React interface, use a same-origin Laravel API with independent application identity, install Statamic 6 Core for a bounded single-editor CMS role, and store business data in MySQL. Keep local and production isolated. Make data-survival and recovery tests release prerequisites. Do not turn these code observations or prior recommendations into fictional infrastructure approvals.

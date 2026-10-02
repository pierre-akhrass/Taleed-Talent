# Taleed Talent — from approved SPA to production

Prepared 30 September 2026. This is an implementation prompt and specification package, not a completed backend or a deployed service.

## The decision

Retain the approved React interface in `taleed-talent-spa/`. Add Laravel + **Statamic 6 Core** in `backend/`, an application-owned JSON API, MySQL 8.4 LTS, and Docker development/production infrastructure in this same repository. Use cookie-based Laravel/Sanctum authentication for application users and an independent Statamic guard for the single CMS administrator. Keep Statamic's Pro features disabled. Use one approved GCP Compute Engine VM, with isolated persistent production data, and no staging environment.

“Production is the source of truth” means production data changes through authorized business operations and reviewed migrations, never by importing a developer database or replacing live data with a release image. Editable CMS files, source documents, uploads and encryption keys also need protection; a database-only backup is insufficient.

## Choose the appropriate download

The **prompt pack** contains documentation and instruction-file changes to review and merge into your existing working repository. The **repository with production prompts** is a separate copy of the uploaded ZIP with this material already inserted. Its application source remains unchanged. Neither download contains a backend implementation, credentials, original PDFs or a new production deployment.

Do not overwrite an active checkout with either archive. Preserve uncommitted changes, compare the provided copy with your current branch, and review instruction-file changes. The local agent must re-inspect the current repository: it may have advanced beyond the uploaded snapshot.

## Start with one agent

Open the repository root, not only `taleed-talent-spa/`, in your editor. Ensure this package is present at that root. Read `docs/production/REPOSITORY-AUDIT.md` and then paste:

```text
You are implementing the approved Taleed Talent & Team Development production
conversion in this repository. Follow AGENTS.md and
 docs/production/MASTER-PROMPT.md.

The approved direction is to preserve the existing React SPA, add Laravel with
Statamic 6 Core and MySQL, and build local HTTPS plus a safe single-VM GCP
production deployment. Local and production only; no staging. Production data
must never be replaced by code releases or developer data.

Run Phase 0 now. Read the current source, documents and applicable agent
instructions. Compare them with the supplied audit rather than assuming that
its uploaded snapshot is still current. Locate authorized Survey and
sustainability-diagnostic-tool sibling workspaces read-only. Inspect safe
configuration examples and scripts, not secret values or production data.

Resolve prototype-only instruction conflicts in your proposed change plan.
Distinguish verified code, historical documentation, approval evidence and
unverified live infrastructure. Identify the Statamic Core authentication
boundary, source-content gaps, well-being scoring conflict, sharing/privacy
rules, database design and existing reverse-proxy/volume arrangement.

Produce the Phase 0 evidence, proposed architecture/contract freeze, work plan
and only the genuinely unresolved decisions. Do not install packages, edit
application code, access production, run migrations or deploy in Phase 0.
Then stop for the Phase 1 authorization in PHASE-PROMPTS.md.
```

Phase 0 is deliberately read-only. The response can be saved to the listed evidence files at the start of authorized Phase 1. The package's design recommendations are not evidence that production infrastructure, content or privacy approval has already been verified.

## Continue through working increments

Use the phase-specific messages in `docs/production/PHASE-PROMPTS.md`. Phase 1 builds the Docker/Laravel/Statamic foundation; Phase 2 establishes schema and identity; Phase 3 implements domain services and API; Phase 4 connects the existing UI; Phase 5 completes content/private records/reports; Phase 6 proves operational safety; Phase 7 performs an explicitly authorized deployment.

Request an actual working increment and actual test evidence at each phase. Do not accept “implemented” when a phase contains only a document, mock or TODO. “Not run” remains “Not run”. Allow scoped local implementation after approval without asking permission for every ordinary code edit. Cloud writes, live migrations, certificate/DNS changes, production email and deployment remain separately gated.

## Local development commands

On Windows, use `scripts/dev/dev.ps1` from the repository root. `init` copies the safe backend environment example only when no local `.env` exists, creates a local key only when it is blank, starts Compose, and applies pending migrations; `up` starts without migrating; `test` runs frontend checks in the pinned Node container and backend tests against the reserved synthetic-only `talent_test` schema, never the app database `talent`; `build` compiles the SPA into Laravel's `/app` directory; `reset-test` explicitly drops/recreates only `talent_test`; `down` stops services without deleting volumes. The script never resets the app database or changes the Windows hosts file or certificate trust store.

The HTTPS development origin is `https://talent.taleed.test:9443`. Developers must separately map the hostname to loopback and install/trust Caddy's local root certificate using the operating system's approved process. Do not bypass TLS verification. No host trust or hosts-file changes were made as part of this implementation.

## Using Claude and Codex together

First freeze the API/schema contract with one integration owner. Then use **separate Git worktrees, branches, Compose project names, database volumes and ports**, following `docs/production/PARALLEL-AGENTS.md`. Never have two agents edit the same checkout or run migrations against the same local database concurrently. The agents can swap backend/frontend roles; neither product needs a different architecture prompt.

## Files that matter

| File | Purpose |
|---|---|
| `AGENTS.md`, `CLAUDE.md`, `taleed-talent-spa/AGENTS.md` | Consistent durable instructions; supersede the old prototype-only backend prohibition. |
| `docs/production/MASTER-PROMPT.md` | Binding implementation brief for either agent. |
| `docs/production/REPOSITORY-AUDIT.md` | Findings from this exact uploaded snapshot, evidence limits, and reference-repository review. |
| `docs/production/ARCHITECTURE.md` | One-origin routing, real CMS responsibility, free authentication and data ownership. |
| `docs/production/DATABASE-SCHEMA.md` | MySQL entities, relationships, privacy boundaries and concurrency invariants. |
| `docs/production/API-CONTRACT.md` | API surface, capability matrix, version/conflict handling and share allowlist. |
| `docs/production/OPERATIONS.md` | Local HTTPS, same-VM integration, backups, restore, updates and rollback. |
| `docs/production/ACCEPTANCE.md` | Measurable release gates, including negative security and persistence tests. |
| `docs/production/PHASE-PROMPTS.md`, `PARALLEL-AGENTS.md` | Execution and review messages, ownership and handoff protocol. |
| `docs/production/DECISIONS-AND-BLOCKERS.md` | What is decided, what needs verification, and safe defaults. |
| `docs/production/SOURCES.md` | Official technical references and private-source provenance. |

The prior `taleed-talent-spa/CODEX_MASTER_PROMPT.md` remains a historical prototype-flow reference. It is not the instruction to run for this production conversion. The old `PACKAGE_MANIFEST.json` is also a historical prototype manifest, not a checksum manifest for this augmented repository. See `DELIVERY-MANIFEST.json` for the delivered file inventory.

# Sources and provenance

Technical references checked 30 September 2026. Recheck official constraints when implementing because compatibility and documentation can change. URLs identify the sources; they are not repository credentials. The design specifications are recommendations, not vendor certification or legal advice.

## Official technical sources

| ID | Official reference | Application in this package |
|---|---|---|
| T01 | https://statamic.dev/getting-started/licensing | Core limits, Pro-only capabilities, no license/trial bypass. |
| T02 | https://statamic.dev/knowledge-base/tips/using-an-independent-authentication-guard | Distinct Eloquent application and Statamic users/guards/providers and reset brokers. |
| T03 | https://statamic.dev/getting-started/requirements | Statamic 6 runtime baseline; actual Composer compatibility still needs verification. |
| T04 | https://statamic.dev/getting-started/installing/laravel | Integrating Statamic into a Laravel application and routing considerations. |
| T05 | https://statamic.dev/frontend/rest-api | Statamic's headless API is a different capability from a custom Laravel business API. |
| T06 | https://laravel.com/framework/docs/13.x/sanctum | First-party SPA cookie/session authentication and CSRF pattern; use documentation matching the eventually installed Laravel version. This link is not a decision to force Laravel 13. |
| T07 | https://docs.docker.com/reference/compose-file/volumes/ | Named volume lifecycle; external volumes fail if absent; explicit stable names. |
| T08 | https://docs.docker.com/engine/storage/volumes/ | Container-independent persistent volumes and their limitations as a recovery strategy. |
| T09 | https://dev.mysql.com/doc/refman/8.4/en/mysqldump.html | Database-aware logical backups, transaction/DDL restrictions and recovery considerations. |
| T10 | https://docs.cloud.google.com/compute/docs/disks/snapshot-best-practices | Application-consistent capture and disk-snapshot recovery limitations. |
| T11 | https://github.com/FiloSottile/mkcert | Author's local CA/trust/HTTPS development tooling reference. |
| T12 | https://letsencrypt.org/2026/01/15/6day-and-ip-general-availability | Current IPv4/IPv6 IP certificates and 160-hour short-lived renewal requirement. |
| T13 | https://docs.github.com/en/actions/how-tos/secure-your-work/security-harden-deployments/oidc-in-google-cloud-platform | Short-lived scoped GitHub Actions authentication to GCP. |
| T14 | https://docs.github.com/en/actions/reference/workflows-and-actions/workflow-syntax | Repository workflow location and actual CI configuration. |
| T15 | https://developers.openai.com/codex/guides/agents-md | Codex repository instructions; currently redirects to the official ChatGPT Learn agent-configuration page. |
| T16 | https://code.claude.com/docs/en/memory | Claude project instruction context via CLAUDE.md. |
| T17 | https://git-scm.com/docs/git-worktree | Separate worktrees and branches for concurrent code work. |

## User-provided and authorized private evidence

**P01 — Current uploaded archive.** `Taleed-Talent-main.zip`, inspected locally. Exact archive hash and byte-preservation checks are in `DELIVERY-MANIFEST.json` / the repository delivery manifest. Key paths are enumerated in `REPOSITORY-AUDIT.md`. The original archive does not contain a backend or PDFs.

**P02 — Prior Talent blueprint.** `02_Document_Audit_and_Product_Blueprint.md`, dated 14 September 2026, retrieved from the user's Library. Used for original document intent, source limitations, privacy, source scope and well-being boundary conflict. Historical SQL Server and pending-approval statements are context, not verified current infrastructure decisions.

**P03 — Prior acceptance and design/implementation briefs.** `05_Acceptance_and_Decisions.md`, `03_Codex_Implementation_Prompt.md`, `04_Claude_UX_UI_Design_Prompt.md`, dated 14 September. Used for source/flow/privacy/recoverability expectations. Earlier proposed tests are not proof those tests passed.

**P04 — Original well-being source.** `Well-being%20Wheel%20Activity.pptx.pdf`, two pages, retrieved from the user's Library and visually reviewed. Confirms nine dimensions, 1–10 ratings and overlapping printed classification bands at 35 and 60. The other six originals were not independently recovered for this review. No original source bytes are redistributed in this package.

**P05 — Targeted Sustainability repository read.** `FadiZahhar/sustainability-diagnostic-tool`, authorized connected GitHub, commit `2b8ec66c55f8371e69715c55dfc82e18480603dd`. Reviewed `compose.prod.yaml`, `scripts/prod/backup-remote.sh` and `docs/operations/production-safety.md`, plus tree/search metadata. Newer Compose/backup files were dated 11 September; the older safety document was dated 20 August. Findings are code/document evidence only, not confirmation of live GCP configuration or successful restoration.

The Survey local working tree was not available through this review; repository search did not identify it. The implementation agent should inspect the authorized local workspace supplied by the user. Do not publicly publish private reference URLs, internal environment details or proprietary source content simply because they appear in an AI work context.

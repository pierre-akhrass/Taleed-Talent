# Decisions, verification needs and safe defaults

Status at prompt-package delivery: the user reports approval of the SPA concept and requests production conversion. No live deployment, content publication, privacy enablement or infrastructure change was performed during this review.

## Settled direction for this implementation

The current user specifies Statamic 6 free/Core, MySQL, Docker, the same repository, preservation of approved UX, local + production without staging, a single GCP instance/static-IP HTTPS model, and production data independent of code updates. The user delegates the implementation approach. This package recommends retaining React, a same-origin Laravel API, independent application authentication and single-editor Statamic guidance. MySQL is the current Talent choice; it is not authorization to migrate Survey/Sustainability databases.

## Verify without inventing approvals

| ID | Needed decision/evidence | Resolution path / safe default |
|---|---|---|
| V01 | Current working tree and sibling local paths | Read authorized workspace configuration first. The uploaded snapshot and targeted GitHub review are not the user's local state. Do not traverse/read secrets to search for paths. |
| V02 | Exact compatible Laravel/PHP/Statamic/dependency matrix | Resolve official requirements and actual Composer/npm constraints, pin stable versions, run checks. Do not invent a Laravel major or assume the lockfile is installed. |
| V03 | Original six remaining PDFs and approved catalogue/conversation wording | Locate approved private documentation/reference content; ask content owner only when reads cannot resolve it. Keep sample content local and new imports draft. |
| V04 | Specific well-being classification decision | Numeric-only default. General demo approval does not resolve the source's overlapping 35/60 boundaries. Enabling labels needs a specific approved rules version. |
| V05 | Private conversations/reflections/wheel collection and retention | Privacy/data owner approves notices, processing scope, owner export/deletion, key access and retention. Keep affected real-data collection off until cleared. No legal compliance claim from this pack. |
| V06 | Exact summary sharing and small-cohort policy | Confirm the allowlist, explicit Champion sharing, assignment model and any small-count suppression. Do not invent threshold values or expose participation metadata. |
| V07 | Company onboarding/duplicate-name handling | Preserve verified self-registration and invitation model; no domain auto-join. Identify the support route for disputed/existing organizations. |
| V08 | VM versus project co-location and capacity | Authorized read-only inventory identifies the actual target. Never assume a shared VM from a shared GCP project. No unapproved additional VM or moving other tools. |
| V09 | Hostname/IP, edge and TLS renewal | Inspect approved routing and certificate mechanism. Prefer a Talent hostname on the existing static IP; preserve current direct-IP/sibling routes. Don't silently replace the shared proxy. |
| V10 | Production disk, volume/dataset identity and secret recovery | Record exact safe references through operator configuration; require mount/identity checks. No default volume creation or new APP_KEY on update. |
| V11 | Backup bucket/region/retention, restore owner and RPO/RTO | Agree and implement protected off-VM recovery; measure restore. Nightly+pre-release is a proposal, not zero-loss assurance. No irreversible retention lock or new paid resource without approval. |
| V12 | SMTP provider, sender and privileged access controls | Approved real configuration outside Git; local Mailpit default; privilege/MFA/recovery tested. No real mail from local tests. |
| V13 | Source distribution, brand and repository visibility | Use only licensed/authorized sources/assets in an approved private repository/AI workspace. Existing tokens are the UX baseline, not proof of official brand certification. No font/PDF redistribution. |
| V14 | Release authorization and actual target | Separate explicit authorization for live read-only inventory, cloud/edge changes, initialization/live migrations and final release. Do not fabricate a production link. |

## Avoid unnecessary blocking

Missing approved source text does not prevent building the local backend with synthetic fixtures. Missing privacy approval does not prevent implementing and testing protected private features with synthetic data behind server-side off switches. Missing production credentials does not prevent building/test-driving Docker, migrations and backup scripts locally. These items block the relevant publication/real-data/deployment step, not every engineering activity.

Conversely, a disabled UI alone does not resolve a privacy gate: APIs/jobs/exports must also deny real-data collection and disclosure. A successful local demo does not resolve a live storage, key recovery or restore gate. Record each unresolved item with a responsible role and the phase it actually blocks.

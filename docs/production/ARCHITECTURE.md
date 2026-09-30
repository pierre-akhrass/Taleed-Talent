# Architecture and ownership contract

Status: recommended design for implementation; refine only where verified repository evidence justifies a documented change. Official technical references are in `SOURCES.md`.

## One deployable application, not a frontend rewrite

```text
Browser — same HTTPS origin
    /app/*                 existing React interface, built by Vite
    /api/v1/*              custom Laravel application JSON API
    /auth/* and Sanctum     Laravel application sessions/CSRF
    /help/*                Statamic-rendered approved guidance
    /cp                    single Statamic administrator, protected access
            |
     existing edge reverse proxy on the chosen VM
            |
     Laravel + Statamic Core web service
       |                 |                 |
    MySQL          private runtime files   editable CMS files
       |
    worker + scheduler, same release and scoped credentials
```

Keep feature routes/components and design tokens in `taleed-talent-spa/`. Add `backend/` with normal Laravel structure, a cohesive `app/Domain/Talent/`, database migrations/factories, Form Requests, policies, resources and tests. Add root `infra/`, `scripts/dev/`, `scripts/prod/`, `compose.dev.yaml`, `compose.prod.yaml` and root CI workflows during implementation. Do not shuffle the approved frontend into a new monorepo layout merely for aesthetics.

The built SPA should be copied into a dedicated backend public build directory, not mounted from a developer's checkout in production. Keep Statamic CP assets separate. Use a minimal shell route for `/app/*`, then explicit API/auth/health/CP routes; verify fallback precedence. Deep links, asset cache-busting and expired sessions must work behind HTTPS. Authenticated responses/private downloads use appropriate private/no-store caching; shared proxies must not cache them as public content.

## State ownership

| Information | Authoritative owner | Deployment treatment |
|---|---|---|
| Application accounts, membership, domain records, source/catalogue approvals, share snapshots | MySQL | Persistent production database; reviewed additive migrations, no seed replacement. |
| Approved help/onboarding CMS content and the single CP user | Statamic runtime files | Persist actual editable paths, back up, never replace from the new image on ordinary boot. |
| Original source files, uploads, owner-private generated reports | Protected runtime file store | Immutable version keys where possible, authorized downloads, lifecycle/retention and backups. |
| Code, migrations, templates, blueprints, design tokens, built assets | Git + immutable release images | Replaced by reviewed releases. Blueprints/configuration need compatibility with preserved runtime content. |
| Application keys, mail credentials, database credentials, recovery credentials | Approved secret store/operator configuration | Stable across releases; not in Git/images/AI prompts or disposable containers. |
| Derived config/view/route caches and rebuildable Statamic cache | Per-release runtime cache | Rebuild from release and preserved content; do not carry an incompatible compiled cache across versions. |
| Form UI state, filters, loading state | Browser memory | Not a database or security boundary. |
| Week-start/theme/RTL-preview preference | User preference service or safe local preference | Non-sensitive only; no private data embedded in a persisted envelope. |

MySQL is the single source of truth **for business data**, not proof that the installation has no other irreplaceable state. Exactly one owner per information type avoids competing SQL/YAML catalogue copies. Use idempotent initial CMS content installation only on an explicitly initialized empty site; ordinary releases use reviewed content changes, never directory overwrite.

## Core edition and independent identity

Statamic Core permits one CMS administrator and excludes its Pro roles/permissions, content revisions, multi-user editing, headless REST/GraphQL and multilingual/multisite features. Keep Core explicitly selected; do not use Pro locally and discover the dependency at launch. Laravel application authentication and business permissions are separate, using the official independent-guard integration pattern.

The intended split is:

```text
Laravel web guard → Eloquent users → MySQL memberships/policies → Talent API
Statamic guard    → flat-file CP user → single-administrator CMS → help content
```

Set the Laravel default application guard deliberately. Set Statamic's configured CP and web guards to its own provider when app users should not be Statamic users. Configure independent password-reset/activation brokers and Sanctum's application guard. Test wrong-guard and mixed-guard sessions. Separate guards are authorization identities, not a guarantee that every browser session/cookie lifecycle is automatically independent.

Use secure cookie-based first-party SPA authentication, CSRF checks and narrowly trusted proxies. The session cookie is HTTP-only; the XSRF cookie is intentionally readable for sending the CSRF header. Use host-only cookies by default, unique names per Taleed tool, and avoid a broad parent-domain cookie that creates accidental cross-tool login. Do not implement shared SSO or share application keys/databases with Survey or Sustainability.

The custom catalogue administrator is a Laravel business role managing SQL activity/source workflows. It is not an extra Statamic editor. The single CP administrator edits only the assigned CMS guidance. Never expose a facade that lets arbitrary application admins edit Statamic CMS content and thereby bypass the Core limit.

## Server authority and frontend integration

Use RTK Query tags/invalidation for API state. Preserve existing client validation to guide users; validate again in Laravel. Autosave authorized draft fields with bounded debounce and an expected version. Show Saving, Saved, Retry or Conflict based on the actual server result. Keep unsaved private text in memory only and warn before leaving; do not introduce private offline persistence as an unreviewed “resilience” feature.

Clear private caches on logout, account/organization switch, lost membership and expired session. A stale response from the previous organization must not populate the new workspace. Abort or ignore in-flight requests using session/tenant context. Handle 401, 403/404, 409, 419, 422 and 429 explicitly. Do not replay a destructive mutation automatically after reauthentication without user confirmation/idempotency protection.

Server services own recurrence, metrics, close/reopen, source publication, invitation consumption and sharing. PHP/TypeScript parity fixtures are useful, but the PHP result is authoritative. Use server-derived tenant/owner context, policies and scoped route binding in every path, including export jobs. Avoid unbounded eager loading and generic serialization of encrypted/private model fields.

## Content and privacy boundaries

One source document version points to real immutable bytes/hash and rights approval. One activity version points to its verified source/adaptation record. Commitments pin immutable approved versions, or tenant-owned validated custom Develop content. Retiring a source or activity stops new use according to the approved policy; it does not silently rewrite history. Historical display after rights withdrawal needs a deliberate policy, not accidental exposure through public assets.

Application-encrypted private payloads are owner-private at the application permission layer. This is not end-to-end encryption against infrastructure administrators with keys. Record the operational access policy and keep private contents out of logging, telemetry, queues and generic audit diffs. Content administrators and analysts have no private-data bypass.

Feature switches should distinguish `private_conversations_enabled`, `private_wellbeing_enabled`, `wellbeing_classification_enabled` and `organization_sharing_enabled`, or equivalent. Default unresolved sensitive features off for real data, with clear UI explanation and matching server denial. A frontend-only disabled button is insufficient. No automatic enablement merely because seeded local tests pass.

## Deployment choice and constraints

The target is a single approved GCP VM with Docker, static IP and HTTPS, not Cloud Run or a new managed-database deployment. Use the existing VM/edge only after verifying the user's intended target, actual capacities and current sibling services. Keep Talent data/network/credentials distinct. No staging is introduced; ephemeral CI/test databases and controlled recovery rehearsal are test/operations mechanisms, not a persistent staging service.

Use MySQL-backed sessions, cache/locks and queue as the initial low-complexity single-VM option. Add Redis only with a measured reason and an accepted operational cost. Have exactly one effective scheduler, idempotent jobs and correct queue timeout/retry relationships. Workers run the same release as web, and must drain or safely restart during updates.

The Compose graph and deployment script must reflect lifecycle separation: database and persistent storage are independently managed; application/worker/scheduler releases are replaceable. A code deploy must not accidentally pull a new database image, change its volume identity or replace the global edge configuration.

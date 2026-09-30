# Claude + Codex concurrency without conflicting edits or databases

Both agents use the same master prompt and architecture. The safe split is by work ownership, not by giving two agents unrestricted responsibility for the whole repository.

## Begin with one integration owner

Complete Phase 0 and establish Phase 1/2 foundation and the first schema/OpenAPI contract. One integration owner controls root instructions, shared contracts, migrations, deployment and merge order. This can be the technical lead using either agent. Do not start two competing backend scaffolds.

Before creating worktrees, preserve/review current changes and make an agreed integration checkpoint. Do not stash or commit another person's work automatically. The following commands illustrate worktree creation from the current agreed commit; they do not push or merge anything:

```bash
# Run from the repository root at the reviewed checkpoint.
git status --short
# Stop here if unrelated changes need preservation/review.
git worktree add -b build/talent-backend ../taleed-talent-backend
git worktree add -b build/talent-frontend ../taleed-talent-frontend
```

Use distinct branches and directories; do not open both agents in the same checkout. Do not run destructive resets to resolve a conflict. Worktree creation does not isolate Docker or databases automatically.

## Ownership map

| Owner | Allowed writes | Must not modify without handoff |
|---|---|---|
| Backend/integration agent | `backend/**`, backend tests, owned infrastructure/scripts, migration/schema contract and root CI as assigned | Frontend feature code and its npm lock while frontend agent is active |
| Frontend agent | `taleed-talent-spa/src/**`, its tests/e2e, frontend-only docs and assigned package changes | Backend migrations, API contract, root agent files, production scripts, shared infrastructure |
| Reviewer | Read-only diff/tests/reports | Other agent's working files, deployments or infrastructure |

The integration owner is the sole writer of `contracts/`/OpenAPI and schema changes. Backend proposes contract amendments, owner approves/regenerates types, frontend consumes them. Generated client files have one generation owner; don't hand-edit generated output in parallel. Each `composer.lock`/`package-lock.json` has one owner at a time. Root instructions and final status are never concurrent scratchpads.

Use per-agent handoff files, e.g. `docs/production/handoffs/backend.md` and `frontend.md`. Each records branch/commit, owned paths, commands/results, contract requests and blockers. The integration owner consolidates after review rather than allowing both agents to overwrite a shared HANDOFF.md.

## Separate local runtime identities

Use distinct local project names such as `talent-backend-dev` and `talent-frontend-dev`, distinct MySQL volumes/databases, keys, Mailpit ports and proxy/Vite ports. Provide actual override/env support during Phase 1. Both environments must contain only synthetic data.

A shared host development proxy may map `talent-backend.taleed.test` and `talent-frontend.taleed.test` to separate app stacks on distinct loopback ports. Alternatively use distinct HTTPS ports with certificates for the intended hostname. Do not start two proxies both trying to bind host 443. Keep browser/API same-origin within each stack and never point either stack at production.

Frontend may use contract-defined synthetic API mocks while a route is unfinished, but those must be visibly test-only and excluded from production. Real integration tests must run against the implementation before merge. Do not connect the frontend agent to the backend agent's mutable database as a shortcut.

## Backend agent message

```text
You are the backend owner in this separate worktree. Follow AGENTS.md and
MASTER-PROMPT.md, and execute the currently authorized backend phase. Own only
backend, assigned schema/API contracts, infrastructure and tests. Preserve the
existing frontend and do not modify its feature files or package lock. Implement
real MySQL services/policies/tests against this worktree's isolated local stack.
Submit contract changes for integration-owner approval before breaking clients.
Write your evidence to handoffs/backend.md. No production access or deployment.
```

## Frontend agent message

```text
You are the frontend owner in this separate worktree. Follow AGENTS.md and
MASTER-PROMPT.md and execute the currently authorized frontend phase. Preserve
approved UI and connect it to the frozen API using RTK Query and real sessions.
Own only assigned frontend files/tests/package changes. Do not change backend
migrations, root workflows, contracts or deployment scripts. Use this worktree's
isolated local stack, not another agent's database. Escalate contract gaps rather
than inventing incompatible endpoints. Record evidence in handoffs/frontend.md.
No production access or deployment.
```

## Review and integration

Review each coherent change set, then integrate one at a time into the agreed integration branch. Run schema/API contract checks and the complete MySQL/browser suite after integration. Branch-level passing tests do not prove the merged application works. Resolve conflicts with explicit ownership, not by taking one side wholesale. Only the integration owner requests Phase 7 authorization, and only one release process holds the production deployment lock.

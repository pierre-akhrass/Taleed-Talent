# Verification evidence and limitations

## Executed in the authoring environment

| Check | Result | Scope / evidence |
|---|---|---|
| Pure domain/seed test suite | **41 passed, 0 failed** | `domain-test-results.json`; actual modules loaded/transpiled with locally available TypeScript. |
| Strict TypeScript check | **Passed** | `tsc --target ES2022 --module ESNext --moduleResolution Bundler --strict --noEmit src/domain/types.ts src/domain/logic.ts src/data/catalogue.ts src/data/seed.ts` |
| TS/TSX syntax and relative-import check | **27 files passed** | `source-check-results.json`; transpilation diagnostics and static relative imports only. |
| Named local import/export inspection | **No missing named/default local import found** | AST audit of project source; does not resolve external packages. |
| Downloadable ZIP integrity | Recorded at packaging | `PACKAGE_MANIFEST.json` with file sizes/hashes; ZIP tested before delivery. |

Local validation tools: Node 22.16.0, global TypeScript 5.8.3. These are **authoring-tool versions**, not a claim that the app’s latest dependencies were installed. The bootstrap generates actual installed-version evidence after it succeeds on a connected machine.

## Not executed

Package registry downloads/DNS were unavailable. The following checks have **not** passed merely because files were written:

- npm dependency installation / real package-lock resolution;
- peer compatibility and current transitive security advisory review;
- dependency-aware whole-project TypeScript compilation;
- Vite dev-server execution or production build;
- Vitest reducer/repository/Zod/React integration tests;
- Playwright desktop/mobile flows, visual screenshots or automated accessibility scan;
- manual browser, keyboard, screen-reader, print, multi-tab and storage-quota checks;
- deployment, production backend, security, privacy or infrastructure validation.

No compiled bundle, authenticated cloud environment or verified browser screenshots are included. No provisional layout preview is misrepresented as an executed React screenshot.

## What the executed tests actually establish

The pure suite verifies the actual utility implementations for calendar boundaries, recurrence, Pick-3 validation, synthetic catalogue counts, completion denominator, delivered scope coverage, latest closed-revision selection, safe summary projection, numeric well-being completeness/limits, content-copy independence and CSV/calendar export exclusion rules.

It does not execute the Redux store, external Zod schemas, React hooks, modal interactions or storage adapter. These are covered by authored tests that must be run after installation. A source syntax pass is not a successful application build.

## Required next verification

Run the setup/check commands from README. Fix all failures without weakening assertions, deleting tests or masking exceptions. Commit the resulting exact `package.json` and real `package-lock.json`. Record browser results against the tested build in `ACCEPTANCE_CHECKLIST.md`, including screenshots and accepted exceptions. The included Codex prompt instructs this validation explicitly and forbids fabricated passes.

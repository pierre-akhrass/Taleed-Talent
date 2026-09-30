# Delivery notes

Prepared 30 September 2026.

The prompt pack is intended for reviewed merging into the current repository, not blind extraction over a dirty checkout. The augmented repository is a separate copy of the uploaded snapshot. No GitHub repository or GCP service was changed.

## Augmented repository changes

Application TS/TSX/CSS source, original tests, manifests/lockfiles and workflows remain byte-for-byte as uploaded. Three original documentation/instruction files are changed: `taleed-talent-spa/AGENTS.md` is replaced with the production frontend instructions; `taleed-talent-spa/README.md` and `CODEX_MASTER_PROMPT.md` receive a production-entry-point banner. Original copies are preserved under `docs/production/archive/`. All other additions are prompt/specification/evidence/manifest files.

The original `PACKAGE_MANIFEST.json` remains historical and is not regenerated to imply a new application build. The augmented delivery has its own `REPOSITORY-DELIVERY-MANIFEST.json`.

The prompt-only archive does not replace the user's entire README/master prompt with an old snapshot; review its production guidance and add an equivalent superseded banner to the current historical prototype instructions as appropriate. Merge the supplied root/nested agent instructions with any other current higher-level requirements.

## Not delivered as implementation

There is no newly built backend, Docker stack, installed dependency graph, database migration or production link in this package. The phased prompts explicitly require the agent to implement and test these. The only application checks run during preparation were the existing dependency-light source/domain scripts; their limited scope is documented.

No original PDFs, font files, keys, credentials or production data have been added to the delivery.

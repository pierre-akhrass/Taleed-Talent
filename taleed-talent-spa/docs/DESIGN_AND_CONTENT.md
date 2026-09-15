# Design system and source-content status

## Visual evidence hierarchy

1. Approved Carbon/Survey product tokens, components and Figma library—**not available in this local build**.
2. User’s existing Taleed prototype repository—its existence/files were verified through a read-only GitHub connector. The Talent HTML is a packaged/bundled export; the approved inner component library was not extracted.
3. Official public Aramco Taleed site—secondary contextual reference, not the application design system.
4. Clearly labeled provisional semantic tokens in `src/styles/tokens.css`.

The bundled reference’s preview wrapper exposed navy `#0a1d5c`, blue `#1741c9` and amber `#f0a93b`. They are used as a provisional direction, **not asserted to be approved official brand values**. Other surfaces, spacing, status colors, radii and type sizes are proposed application tokens. The text “Taleed” is a plain typographic demo label, not a recreated official logo. The ornamental hero shapes are abstract CSS decoration, not a brand symbol.

The app uses a system font stack. A reference to Inter in a CSS fallback list does not bundle or license that font. No font files or protected brand files are redistributed. Once authorized assets are available, replace the tokens/wordmark through a scoped brand-alignment change without rewriting the business rules.

## UX direction

The overview gives priority to the next action, not decorative analytics. A calm white navigation rail and light workspace frame a navy introduction card, restrained blue actions and small theme/status accents. The plan wizard makes the three scopes explicit, shows selection separately from actual delivery and keeps a real calendar/agenda available. Private sections are labeled before data entry. Sharing previews exact aggregate fields rather than an ambiguous “submit” button.

Layout primitives cover desktop, tablet and 390 px mobile. A small persistent save indicator remains visible on compact layouts. The app is English-first; RTL mode is a layout review aid only. Full Arabic translation, translated source activities and an official Arabic font selection are not included.

## Content manifest

| Content | Included status |
|---|---|
| Four themes / three scopes / six activities per combination | Preserved in the illustrative seed structure. |
| Full 72 original activity wordings | Not supplied as a usable local source catalogue; **not transcribed**. |
| Three known activity titles | “Find mentors to learn from”, “Designate development time”, “Lunch-n-Learns” are marked source-equivalent. Supporting demo copy remains illustrative. |
| Other activity titles, guidance and steps | Synthetic examples for functional UX review. Not attributable to Aramco as approved copy. |
| Seven original PDFs | Not embedded or made downloadable. Only metadata/reference descriptions exist. |
| Six conversation steps, three core questions, seven follow-ups | Structural counts preserved; wording is clearly illustrative until the exact guide is provided. |
| Well-being dimension order / 1–10 inputs / 54 example | Based on the retrieved implementation/design briefs. No dimension definitions are invented. |
| Well-being score classifications | Omitted because prior evidence noted overlapping source bands. No clinical interpretation. |
| Missing internal guides, recognition guide and toolkits | Explicit editorial dependencies, not fabricated URLs. |
| Content permission and privacy approval | Not inferred from PM approval of the concept. Remain client decisions. |

## Replacing sample content safely

Keep authorized files out of public repositories unless the client approves distribution. Have the approved source owner supply structured activity records with stable IDs, theme, scope, exact text, version and source reference. Import them as new unpublished drafts; never relabel sample text “source approved.” The app rejects duplicate imported IDs and unknown source-record references.

Review source permissions and dependencies, compare versions, and publish only after explicit approval. Old plans must keep their pinned version. Production source-file uploads, hashes, legal classification, attribution, approvals and retention must be implemented in a protected server environment—not simulated by clearing a text field in this prototype.

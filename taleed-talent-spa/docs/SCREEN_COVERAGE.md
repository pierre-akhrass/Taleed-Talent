# T01–T34 screen/state coverage

**Meaning of coverage:** source interfaces/states were authored. This matrix is not evidence that each browser journey was executed. Related requirements share templates intentionally. Hash routes are shown without the leading `#`.

| ID | Required experience | Implemented route / state | Boundary or qualification |
|---|---|---|---|
| T01 | Introduction | `/welcome` or `/entry`; `/about` | Purpose and scope, no fake service promises. |
| T02 | Registration/sign-in/recovery | `/welcome` entry/signup/recovery modes | Form validation; no credential persistence or authentication. |
| T03 | Verification/invitation | `/welcome` verification mode; `/invite/:id` | Clearly simulated email verification; exact-email/expired/revoked/used invite checks. |
| T04 | Company setup | `/welcome` company mode | Minimal setup and duplicate-name warning; no domain-based joining. |
| T05 | Orientation | `/welcome` orientation mode; `/plan/new` focus step | One focus and three scopes; optional private tools. |
| T06 | Leader home | `/home` | Own next actions and derived monthly metrics. |
| T07 | Activity/resource library | `/library`, `/resources` | Search/filter/bookmark and empty states; 72 illustrative cards. |
| T08 | Activity/source detail | `/library/:id`, resource overview disclosure | Pinned version/source label; no unavailable original-PDF download. |
| T09 | Pick-3 | `/plan/new`, selection step | Exactly one per scope; replacement confirmation. |
| T10 | Schedule | `/plan/new`, schedule step | Daily, selected weekdays and one-off Develop; valid monthly bounds. |
| T11 | Calendar/agenda | `/calendar` | Real four/five/six-week layouts, configurable week start, mobile agenda. |
| T12 | Occurrence detail | Calendar/plan modal | Five statuses, own note, same-month single reschedule and closed-state lock. |
| T13 | Custom Develop activity | `/plan/:id`, custom modal | Owner-authored title/instructions/scope; not globally published. |
| T14 | Month close/reflection | `/plan/:id`, close modal | Incomplete explanation, private reflection and snapshot. |
| T15 | Personal history | `/history` | Closed revisions, filter, own report and correction path. |
| T16 | Conversation preparation | `/conversations`, `/conversations/:id` | Six illustrative guidance steps, alias/date; source wording unverified. |
| T17 | Guided notes | `/conversations/:id` | Three areas/seven illustrative follow-ups; no formal appraisal. |
| T18 | Conversation recap | `/reports/conversation/:id` | Goal/action/follow-up, own export; no shared employee account. |
| T19 | Well-being privacy intro | `/wellbeing` | Voluntary notice, explicit continue/skip; fictional data only. |
| T20 | Nine ratings | `/wellbeing/:id` | Null initial scores, explicit integer inputs, completeness/no premature total. |
| T21 | Numeric profile/actions | `/wellbeing/:id` | Nine-axis SVG plus inputs, numeric total, focus/three actions; no bands. |
| T22 | Private well-being history | `/wellbeing` | Draft/completed records and correction revisions. |
| T23 | Own report centre | `/reports`, `/reports/:type/:id` | Own previews, private JSON warning, print/PDF through browser. No fabricated asynchronous server job/expiry service. |
| T24 | Members/invitations | `/members` | Fictional members, pending/accepted/expired/revoked invitation states. |
| T25 | Organization summary | `/organization` | Latest closed-plan aggregate; not-shared state and exclusions. |
| T26 | Exact share preview | `/organization`, preview modal | Allowlisted JSON, period/version/audience, explicit confirmation. |
| T27 | Shared history/correction | `/organization`, history/detail | Effective/superseded/withdrawn versions; download recall warning. |
| T28 | Taleed portfolio | `/portfolio` | Assigned Cedar cohort, current effective shares and consistent period filters. |
| T29 | Shared summary detail | `/portfolio`, detail modal | Only the shared payload; no private drill-through. |
| T30 | Taleed exports | `/portfolio`, export preview | Safe JSON/CSV, exact filtered rows; no server jobs or scheduled emails. |
| T31 | Sources/versions | `/admin` source inventory and activities tabs | Seven metadata records, illustrative versions and dependency flags; actual PDF hashes not invented. |
| T32 | Draft/compare/publish | `/admin`, activity editor/source review/import | Append-only version draft, comparison, dependency and acknowledgement gates; existing plans pin content. |
| T33 | Privacy/account preferences | `/settings` | Own exports, scoped private-exercise deletion, layout/reminder preferences; production retention/account deletion not implemented. |
| T34 | Exception/recovery states | Shared shell, route guards, Settings, error boundary | Forbidden/not-found, no local identity, save failure/retry, stale tab, corrupt import, unavailable source. No fake server session-expiry/network service UI. |

## Explicit adaptations from the earlier backend brief

The earlier design contract included server authentication, secure downloads/report jobs, operational privacy, original PDFs, exact source copy and approved product assets. Those cannot be established by a localStorage prototype. This implementation simulates the workflow where appropriate and explicitly omits unsupported services. It does not equate UI role filtering with security.

The source uses a developer recovery console for all-persona backup. This is deliberately labeled and must be removed from a production participant application. Technical-superuser impersonation is not implemented; a real service must disable private sections during any authorized support impersonation.

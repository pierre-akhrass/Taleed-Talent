# Prototype acceptance checklist

**Prepared:** 15 September 2026  
**Browser UAT status at delivery:** NOT RUN. Source/domain checks are recorded separately.  
**Required record for each test:** tester; date; build/commit; browser/viewport; actual result; Pass/Fail/Blocked; evidence; defect/retest reference.

A checked box is a recorded test result, not an assumption. Use fictional personas only. Any incorrect shared data, lost saved work, broken primary journey or misleading privacy claim blocks presentation acceptance.

| ID | Test | Expected outcome | Delivery status |
|---|---|---|---|
| U01 | Install stable dependencies, run full typecheck, tests and build | Exact pins/real lockfile; no forced peer bypass; successful commands with actual logs | Not run |
| U02 | Load desktop and 390 px mobile | Readable layout, working navigation and no page-wide overflow | Not run |
| U03 | Register invalid and valid fictional details | Helpful field errors; no password persisted; explicit verification simulation | Not run |
| U04 | Attempt existing-company name/domain matching | No automatic access to existing members/records | Not run |
| U05 | Pick zero, duplicate-scope and valid balanced activities | Invalid selections blocked; one per scope within one theme accepted | Not run |
| U06 | Refresh a partial draft and an activated plan | Last successfully saved records return; seed does not overwrite data | Not run |
| U07 | Schedule daily/weekly/one-off; invalid dates, leap year and no matching weekday | Correct bounded occurrences; invalid schedules rejected | Not run |
| U08 | Complete one repeat and cancel another | Only those occurrences change; cancellation excluded from denominator | Not run |
| U09 | Reschedule one/future work | Completed/cancelled/past history retained; no duplicate eligible occurrence | Not run |
| U10 | Close an incomplete month and correct it | Honest reason required; old snapshot preserved; latest revision used once | Not run |
| U11 | Seeded August fixture | Five scheduled, four eligible, two completed, 50%, two delivered scopes | Domain rule passed; UI not run |
| U12 | Save conversation and switch identity/direct route | Own-record visibility only in UI; no private content in company reports | Not run |
| U13 | Enter eight/nine well-being scores; totals 54, 35, 60 | No incomplete total; no score band; focus and three actions required | Numeric domain rules passed; UI not run |
| U14 | Champion previews and shares | Exact allowed aggregate fields only; no identity/free text/private metadata | Domain allowlist passed; UI not run |
| U15 | Analyst filters/exports; replaces/withdraws share | Same filtered rows and aggregate counts; one effective organization/month summary | Not run |
| U16 | Simulate failed save then retry | Never says Saved on failure; changes remain in memory; successful retry persists | Not run |
| U17 | Edit in two tabs | Clean-tab sync or explicit dirty-tab conflict; no silent overwrite in supported lock path | Not run |
| U18 | Corrupt/unknown/oversized import and failed replacement | Original bytes preserved; no automatic reset; explicit recoverable error | Not run |
| U19 | Valid backup preview/import/reset | Explicit all-persona developer warning; atomic validated replacement | Not run |
| U20 | Create content draft, blocked publish, resolved publish | Source gate works; old plans retain pinned version | Not run |
| U21 | Invitation accept/replay/expire/revoke/wrong email | State respected; no real email or security claim | Not run |
| U22 | Own report print and JSON; filtered safe CSV | Readable output, confidentiality warning, no private leak into shared files | Not run |
| U23 | Keyboard, native dialogs, focus restoration, reduced motion, automated axe | Usable without mouse; no serious/critical automated failures; manual review recorded | Not run |
| U24 | Inspect every primary action and empty state | No unexplained dead ends; unsupported services are clearly identified | Not run |
| U25 | Brand/content fidelity review | Provisional design and illustrative copy acknowledged; approval items remain explicit | Pending client review |

## Sign-off boundary

Prototype acceptance validates journeys, UX rules, demo behavior and agreed next-phase requirements. It does not certify production security, approved Aramco publication rights, secure tenancy, real privacy controls, clinical accuracy, formal performance assessment, server reliability or official brand compliance.

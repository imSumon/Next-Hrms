# Universal HRMS — Consolidated Evidence Register

Stage 2. Thirty findings. This register does not approve a policy. Status words are confirmed, partially confirmed, or unresolved. None are marked approved.

Evidence types: source-code finding, previously observed database result, design artifact.

## Register

| ID | Audit | Observed behavior | Evidence | Affects | Class | Priority | Status |
| --- | --- | --- | --- | --- | --- | --- | --- |
| F-01 | AUD-01 | Hourly leave writes `hourly_leave_emp_app` and a balance. It does not write attendance or payroll | Source | LEAVE-CORE-004 | LEGACY ONLY | P1 | Confirmed. Not a first-release shall |
| F-02 | AUD-01 | Hourly balance uses an 8-hour day, a 30-minute remainder, female-only ML, and July–June proration | Source | Leave year, not payroll month | LEGACY ONLY | P1 | Confirmed. Not approved |
| F-03 | AUD-02 | Day leave deducts a balance only when status becomes 3 | Source | LEAVE-CONF-001, BD-D06 | LEGACY ONLY | P0 | Confirmed |
| F-04 | AUD-02 | Day-leave approve does not compare the session employee with `boss_id` or `gboss_id` | Source | APP-CORE-001, SEC-CORE-001, BD-D17 | LEGACY ONLY | P0 | Confirmed gap |
| F-05 | AUD-02 | Day-leave delete removes the row and does not restore a deducted balance | Source | Leave integrity | LEGACY ONLY | P1 | Confirmed |
| F-06 | AUD-02 | Atlas first approval can jump to status 3. Other names in that branch stop at 1, which does not deduct | Source | BD-D17 | LEGACY ONLY | P1 | Confirmed alias branch |
| F-07 | AUD-02 | Atlas and CEVA grand-boss attendance approval can set the day to Present and replace punches with shift times | Source | BD-D16, PUNCH-APPROVED | LEGACY ONLY | P0 | Confirmed. Raw punch table was not updated |
| F-08 | AUD-02 | `proedge` and `vtl` approval in that action changes application status only | Source, agrees with Proedge August | BD-D16 | LEGACY ONLY | P0 | Confirmed for that action |
| F-09 | AUD-02 | A VTL displayed-punch change was not produced by the `vtl` branch | Prior month plus source | BD-D16 | UNKNOWN | P1 | Unresolved writer |
| F-10 | AUD-03 | Admin login is a custom session. Yii RBAC is not the check | Source | SEC-CORE-001 | LEGACY ONLY | P0 | Confirmed |
| F-11 | AUD-03 | Eight appadmin controllers have no `user_level` test. `UserController::actionUpdate` saves with the level test commented out. `SiteController::actionBackend` sets a session from a username without a password comparison | Source | SEC-CORE-001 | LEGACY ONLY | P0 | Confirmed |
| F-12 | AUD-03 | `moduleAccess()` checks a module grant and is commented out inside appadmin. Grid buttons read controller grants | Source | SEC-CORE-001 | LEGACY ONLY | P0 | Confirmed. Non-appadmin call sites were sampled, not exhaustively read |
| F-13 | AUD-03 | `supadmin` may open any company id. Other levels need a `user_companies` row | Source | Tenant boundary | LEGACY ONLY | P0 | Confirmed. Role list unsigned |
| F-14 | AUD-04 | File upload writes `date` and `emp_code` on `all_att_log`. Location transfer also writes `update_datetime` and `balid` = `location_access_data.id` | Source | PUNCH-CORE | LEGACY ONLY | P1 | Confirmed. Not the closed-month writer |
| F-15 | AUD-04 | Closed-month rows had `area` filled and `update_datetime` null. Neither in-repo writer produces that pair | Prior month plus source | Punch archive | UNKNOWN | P1 | Unresolved external writer. No vendor named |
| F-16 | AUD-04 | `area` is never assigned in this source | Source | Archive note | UNKNOWN | P1 | Unresolved |
| F-17 | AUD-04 | `balid` means the location-access id only on the transfer insert. Other rows stay uninterpreted | Source plus prior samples | Archive note | UNKNOWN | P1 | Partially confirmed |
| F-18 | AUD-04 | The shared log query has no company predicate. Attribution is the company card map. Catalog ids 24, 20, and 10 are not stored on the punch | Source plus workbook | D7-APPROVED | CORE boundary | P0 | Confirmed in source. Card-number overlap was not counted |
| F-19 | AUD-04 | `actionDelete` and `actionArchivelogs` delete raw rows. The target rule says a correction does not edit the raw punch | Source versus PUNCH-APPROVED | PUNCH-WITHDRAW | LEGACY ONLY versus approved target | P0 | Confirmed contradiction. Withdrawal stays unsigned. The target rule stays in force |
| F-20 | AUD-05 | Atlas, CEVA, and Proedge process actions share one shape: card map, MIN/MAX punch, `photo` weekend, employee shift, status-3 leave, status-1 approval substitution, month lock | Source | One engine | CORE shape | P1 | Confirmed in source. CEVA production is not confirmed |
| F-21 | AUD-05 | Atlas action uses 31-minute late and early buffers. Proedge and CEVA actions use 16-minute late grace and compare early-out with shift end | Source | BD-D01, BD-D05 | CONFIGURABLE | P0 | Confirmed constants. Not approved defaults. September grace was not measured |
| F-22 | AUD-05 and third-company audit | No local CEVA database. Atlas September matched employee shift, Friday–Saturday in `photo`, no September lock, no overtime pay | Previously observed database result | BD-D03, BD-D08 | LEGACY ONLY | P1 | Atlas month confirmed as previously reported. CEVA month unresolved |
| F-23 | Closed months and traceability | VTL missing-out was stored as Absent. Proedge and Atlas used Present plus missing-punch text. Shift winner differed | Previously observed database result | BD-D02, BD-D03 | CONFIGURABLE | P0 | Confirmed for those months. Not a universal outcome |
| F-24 | Workbook and traceability | Calculated, payable, and paid overtime disagree across the three months | Previously observed database result | BD-D08 through BD-D12 | BUSINESS DECISION | P0 | Confirmed split. Formulas unsigned |
| F-25 | AUD-06 | In-month `joining_date` starts the attendance loop. In-month `discontinue_date` stops it only when `disconrinue` is 1 | Source | D3, D6, BD-D07 | LEGACY ONLY | P0 | Confirmed. No operated partial month |
| F-26 | AUD-06 | Salary generate prorates `partial=1` heads from joining date through month end. Divisor is 30 when `has_provident_fund` is greater than 0, otherwise the calendar length. `disconrinue=1` is omitted and is not prorated by leaving date | Source | PAY-CONF-001, BD-D07 | LEGACY ONLY | P0 | Confirmed formula. Not an approved default |
| F-27 | AUD-06 | `ML` day reduction runs only for database names `icwlw` and `valw` | Source | Client payroll | LEGACY ONLY | P2 | Confirmed branch |
| F-28 | AUD-06 | Payroll month is the requested calendar month. `leave_financial_year` is a leave year. Hourly July–June is a third period | Source | FY-CONF-001 | CONFIGURABLE | P1 | Confirmed separation. Company months unresolved |
| F-29 | Steps 20N, 20O, 20R | Three company rows exist. Employees and episodes were 0. No company is migration-approved | Design artifact and prior local review | RECON-APPROVED | CORE constraint | P0 | Confirmed. Not re-queried in Stage 2 |
| F-30 | Step 21C | Live `legacy_master` comparison was not performed | Design artifact | Catalog confidence | UNKNOWN | P1 | Unresolved. MySQL was not started |

## Counts

| Status | Findings |
| --- | --- |
| Confirmed | F-01 through F-08, F-10 through F-14, F-18 through F-21, F-23 through F-29 |
| Partially confirmed | F-17, F-22 |
| Unresolved | F-09, F-15, F-16, F-30, and the CEVA half of F-22 |

Twenty-four findings are confirmed (F-01–F-08, F-10–F-14, F-18–F-21, F-23–F-29). Two are partial (F-17, F-22). Four are unresolved (F-09, F-15, F-16, F-30). The CEVA half of F-22 is the open part of that partial finding, not a thirty-first row.

## Further investigation

| ID | Disposition |
| --- | --- |
| F-09, F-15, F-16, F-17 | Can remain open. The workbook already keeps `area`, `balid`, and a null company column off the business columns. Another source pass would not name the external writer |
| F-18 overlap count | Required before migration of punches. Not required to keep the card-map rule |
| F-22 CEVA month | Not necessary for the one-engine design. Required before anyone states a CEVA production policy |
| F-30 | Required before treating the 25 / 101 / 416 figures as live. Not required to design the product. Do not start MySQL for it |
| F-12 remainder | Required before authorization acceptance tests. The CORE rule that the server checks the action does not wait on a full call-site list |

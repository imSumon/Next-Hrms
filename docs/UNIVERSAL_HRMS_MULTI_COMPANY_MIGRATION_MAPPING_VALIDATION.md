# Universal HRMS Multi-Company Migration Mapping and Validation

Status: design, mapping, and validation only. No migration code, SQL, database changes, or live load is included.

No company in this landscape is the template for the Universal HRMS. Proedge is a reference. Viyellatex is a reference. A Core Company is not selected.

Approved inputs:

- `docs/UNIVERSAL_HRMS_REQUIREMENTS.md`
- `docs/UNIVERSAL_HRMS_ARCHITECTURE_ASSESSMENT.md`
- `docs/UNIVERSAL_HRMS_LOGICAL_DATA_ARCHITECTURE_ASSESSMENT.md`
- `docs/UNIVERSAL_HRMS_PHYSICAL_DATA_ARCHITECTURE_ASSESSMENT.md`
- `docs/UNIVERSAL_HRMS_TARGET_DATABASE_SCHEMA_SPECIFICATION.md`
- `docs/UNIVERSAL_HRMS_MIGRATION_DATA_CONTINUITY_ARCHITECTURE.md`

The company list below comes from a read-only catalog query of `legacy_master.companies` (id, name, alias, database name). No production database was opened or changed. Closed-month behavior is taken only from the approved Proedge August 2026 and Viyellatex September 2026 audits.

```
SOURCE COUNT = LOADED + APPROVED EXCEPTIONS
```

Unexplained missing records must be zero before any company is approved. That gate is per company. It is not met by this document.

---

## 1. Purpose

This document answers whether the target schema can represent more than one real company without being redesigned around any one of them.

The answer for the two audited companies is yes: their differences are configuration, transformation, legacy-only artifacts, or open business decisions. The answer for a third Core Company is not yet knowable. Selecting one now would guess.

## 2. Reference companies

| Reference | Identity in the catalog | What was actually audited | What it is not |
|---|---|---|---|
| Proedge | Company 24, Pro-edge Associates Limited, alias `proedge`, database name `proedgea_newproedgehrm`. Local audit copy `legacy_proedge` | August 2026 closed-month trace: word statuses, employee shift, missing-punch text, OT seconds, a paid OT head, payslip from stored salary, empty lock tables | The universal shift source, grace, status vocabulary, or OT rule |
| VTL | Company 20, Viyellatex Group, alias `vtl`, database name `proedgea_vtlhrm`. Local audit copy `legacy_vtlhrm` | September 2026 closed-month trace: letter statuses, shift calendar, correction behavior, unpaid OT hours, leave marks, payslip from stored salary, locks unused for that month | The universal roster rule, correction rule, approval numbers, or OT rule |
| Core Company | Not selected | No third closed month was traced | Not Proedge and not VTL by default |

Other catalog rows are context, not references. Sibling Viyellatex rows (21, 29, 37, 38, 41, 42, 46, 47, and others) are separate companies. Demo and test rows (17, 33, 50, 55, 73) are not operational references.

The catalog also shows two structural clusters that are not yet references:

- Several service companies share a database name `proedgea_payroll_aug_13` (companies 3, 4, 5, 6, 7, 8, 11, 12, 13, 25, 26). Whether that database is still operated is UNKNOWN.
- Atlas (10), CEVA (34), AESBD (56), and AESBD-OS (79) are named in source or in the product vision beside Proedge. Their operated months were not traced. The requirements already say Atlas and CEVA policy is UNKNOWN.

## 3. Core Company selection

**Core Company: not selected.**

A confident choice needs a company whose operated month shows a common HRMS pattern and whose evidence is not the Proedge trace or the VTL trace. Size is not the test. Code branches are not the test. The catalog name is not the test.

| Candidate | Why it is a candidate | Why it is not selected |
|---|---|---|
| Atlas Copco Bangladesh Ltd., company 10, alias `atlas` | Named in the product vision. Separate process code exists. Not company 24 or 20 | Closed month not traced. Operated shift, leave, OT, correction, and payroll are UNKNOWN |
| CEVA Freight (Bangladesh) Co. Ltd., company 34, alias `ceva` | Named in the product vision. Separate process code exists | Same evidence gap as Atlas |
| Alliant Energy Solutions (BD) Ltd., company 56, alias `aesbd` | Mentioned with the Proedge-family timecard list in the attendance audit | That list is a code path, not a closed-month trace. Choosing it would likely repeat Proedge |
| Interfab Shirt Manufacturing Ltd., company 22, alias `isml` | Substantial factory attendance and approval code | Likely repeats the VTL factory family. Not traced as its own month |
| A company inside `proedgea_payroll_aug_13` | Many catalog rows point at one older database name | No proof those companies are the current common pattern. No month was traced |

Minimum evidence required before a selection:

1. Confirm which catalog rows are in current operation, excluding demo and test rows.
2. One read-only closed month for one company that is not 24 and not 20.
3. Prefer Atlas (10) or CEVA (34), because the product vision already names them, unless step 1 shows a different company is the common operated pattern.
4. That month must show, with counts: employee statuses, shift source (employee default, roster, or both), attendance status tokens, incomplete-punch storage, leave requests and day marks, correction rows and whether the day changed, OT quantity and any payroll OT amount, salary heads, payslip source, and lock rows.
5. Do not select from a code alias alone.

Until that pack exists, the matrix column for Core Company is UNKNOWN. Forcing Atlas or CEVA today would invent their behavior.

## 4. Reference capability matrix

Classifications: COMMON CORE, CONFIGURATION, TRANSFORMATION, CLIENT EXTENSION, LEGACY ONLY, UNKNOWN, BUSINESS DECISION.

Core Company cells are UNKNOWN. That is the evidence gap, not a hidden match.

| Capability | Proedge (24) | VTL (20) | Core Company | Universal target |
|---|---|---|---|---|
| Company | Separate company database. Alias selects code | Separate company database. Alias selects code | UNKNOWN | COMMON CORE tenant. Alias is LEGACY ONLY |
| Organization | Departments and designations exist | Departments, designations, and factory location fields exist | UNKNOWN | COMMON CORE. Location depth is CONFIGURATION |
| Employee | Company employee master | Company employee master | UNKNOWN | COMMON CORE |
| Employment history | Current row more than a full interval history | Current row, including discontinue fields | UNKNOWN | TRANSFORMATION into episodes and assignments |
| Reporting line | Boss and grand-boss columns | Boss and grand-boss columns. Some approved rows have a null boss | UNKNOWN | TRANSFORMATION into a chain. Step list is BUSINESS DECISION BD-D17 |
| Branch | Not established as the tenant | Not the tenant. Factory location fields exist | UNKNOWN | COMMON CORE optional branch |
| Department | Present | Present | UNKNOWN | COMMON CORE |
| Designation | Present | Present | UNKNOWN | COMMON CORE |
| Shift definition | Two general windows, 09:00–17:00 and 08:00–17:00 | Named shifts with a next-day window capability | UNKNOWN | COMMON CORE, including cross-midnight. Grace is BUSINESS DECISION BD-D01 |
| Shift assignment | `employees.shift_id`. Roster table empty in August 2026 | `emp_shift_calendar` wins. Employee shift differs on some days | UNKNOWN | COMMON CORE candidates. Winner is BUSINESS DECISION BD-D03 |
| Cross-midnight shift | Not the audited office windows | Shift window can cross midnight | UNKNOWN | COMMON CORE capability. Use is CONFIGURATION |
| Holiday | Public holidays as Holiday. Weekly off in `photo` as Friday, Saturday. `weekly_holiday` also populated for some | Weekly off on the employee and calendar H. September H days were weekly. `photo` null | UNKNOWN | COMMON CORE rules. Photo storage is LEGACY ONLY |
| Punch source | Unknown writer into the shared log. Area text stored | Unknown writer. Area text stored | UNKNOWN | COMMON CORE named source. Writer is UNKNOWN |
| Punch identity | Card can equal the employee code | Card often differs from the employee code. Duplicate cards exist | UNKNOWN | COMMON CORE map. Duplicates are exceptions |
| Raw punch | Shared log. Company null. First and last kept by the processor | Shared log. Five events on a traced day reduced to two | UNKNOWN | COMMON CORE immutable events. Min/max-only is LEGACY ONLY |
| Attendance calculation | Word-status processor | Letter-status processor. Alias blanks some OT | UNKNOWN | COMMON CORE calculation. Two engines are LEGACY ONLY |
| Attendance day result | Present, Absent, Leave, Holiday, Weekly Holiday, plus day-status text | P, A, H, leave letters. Missing out stored as A | UNKNOWN | COMMON CORE stored result. Vocabulary is CONFIGURATION. Incomplete outcome is BUSINESS DECISION BD-D02 |
| Work date | The stored attendance date | The stored attendance date. Shift can cross midnight | UNKNOWN | COMMON CORE `work_date`, distinct from punch time |
| Leave | Approved leave marks Leave and a type token. Basic unchanged in the sample | Approved leave marks letter codes. Basic unchanged in the sample | UNKNOWN | COMMON CORE request and day mark. Pay effect is BUSINESS DECISION BD-D06 |
| Attendance correction | August requests did not change the day | Approved rows rewrote the displayed time toward the shift boundary | UNKNOWN | COMMON CORE history. What may change is BUSINESS DECISION BD-D16 |
| Approval | Status numbers, name strings, boss columns | Different status numbers. Name strings. Some null bosses | UNKNOWN | COMMON CORE ordered steps. Numbers and boss columns are LEGACY ONLY. Steps are BUSINESS DECISION BD-D17 |
| Calculated OT | Seconds after shift end on the day | Whole hours, and only beside corrections in September. Processor also has a blanking branch | UNKNOWN | COMMON CORE figure. Formula is BUSINESS DECISION BD-D09 |
| Payable OT | Not a separate store. Eligibility flag does not match pay | Not a separate store. Eligibility flag is 0 while hours exist | UNKNOWN | COMMON CORE separate figure. Eligibility and approval are BUSINESS DECISION BD-D08, BD-D11 |
| Paid OT | A salary head whose amount is not the seconds | No OT head in September. Paid amount 0 | UNKNOWN | COMMON CORE snapshot money. Rate is BUSINESS DECISION BD-D12 |
| Salary structure | Amounts on the employee and on salary rows | Amounts on the employee and on salary rows | UNKNOWN | COMMON CORE structure and values |
| Salary heads | Including an Over Time head. No Absenteeism head in the sample | Many allowance heads and an Absenteeism head. No OT head in September | UNKNOWN | COMMON CORE company-defined heads. CONFIGURATION |
| Payroll | `employee_salary` amounts. Basic unchanged despite absence | `employee_salary` amounts. Absenteeism sometimes resembles a day formula and often does not | UNKNOWN | COMMON CORE run. Formulas are BUSINESS DECISION BD-D07 |
| Payroll snapshot | Amounts only. No attendance copy | Amounts only. No attendance copy | UNKNOWN | COMMON CORE snapshot. Optional lines are BUSINESS DECISION BD-D18 |
| Payslip | Reads stored salary | Reads stored salary. Other company ids use NMS layouts | UNKNOWN | COMMON CORE read of the snapshot. NMS is CLIENT EXTENSION |
| Attendance period control | Lock table empty | Last Yes is 2020-09. September 2026 was not locked | UNKNOWN | COMMON CORE capability. Use is BUSINESS DECISION BD-D13 |
| Payroll period control | Lock table empty | Last Yes is 2023-06. September 2026 was not locked | UNKNOWN | COMMON CORE capability. Use is BUSINESS DECISION BD-D14. Reopen roles are DEFERRED BD-D15 |
| Audit and history | Delete-and-insert day. Approver is not a user id | Same. Original punch often gone from the day row | UNKNOWN | COMMON CORE versions and actors. Legacy gaps are TRANSFORMATION limits |
| Company-specific extensions | No NMS payslip branch for company 24 | Perquisite layouts for company 20. NMS payslip is other company ids, and `nms_data` was empty | UNKNOWN | CLIENT EXTENSION, off by default |

## 5. Proedge mapping

Source evidence is the August 2026 trace. Rules below are Proedge facts, not universal rules.

| Source | Target | Transformation | Limitation | Validation |
|---|---|---|---|---|
| `employees` | `employees`, `employment_episodes`, `employment_assignments` | Code, name, joining, separation, department, designation, status | One current row is not a full history | Active code uniqueness. Duplicate codes become exceptions |
| `employees.shift_id` | `shift_assignment_candidates` kind `default` | The August employee shift | Roster was empty. This is not the winner rule | Do not write a `shift_resolutions` policy. BD-D03 stays open |
| `emp_shift_calendar` | No roster candidates for August 2026 | Zero rows is a real result | Do not invent a roster | Confirm the extract is empty rather than failed |
| `employees.photo` when it holds weekday names | Not a target column. `weekly_off_rules` only if the business confirms the text | Friday, Saturday was the audited pattern | Photo is not a holiday design | Unconfirmed text is an exception |
| `employees.weekly_holiday` where populated | Same weekly-off decision | Some employees have this column as well as photo | Two stores must not both become rules without confirmation | Exception when they disagree |
| `all_att_log` | `punch_events` | Timestamp unchanged. Company through the punch map. Source name “legacy punch log, writer unknown” | `area` and `balid` stay uninterpreted. Same-second uniqueness may already have dropped events | Count of mapped events plus unmapped exceptions |
| `emp_code_to_punch_card` | `punch_identities` | Card value. For 10266 the card equalled the employee code. That equality is still a mapping | Duplicates, if present, are not merged | Active identity overlaps |
| `attendance_detail` | `attendance_day_result_versions` and the current pointer | One surviving row. Source status token kept as a legacy token. Stored date is the historical `work_date` | Prior versions were deleted by reprocessing and are not invented. Missing punch stayed Present plus text. That text is evidence, not BD-D02 | Employee-day count and token counts |
| `attendance_approval_file` | `attendance_corrections` and value rows | Load the request. August rows did not change the day, so final is “day unchanged,” not a fabricated punch | Approver names are not user ids. Boss columns are not steps | Every request accounted for |
| `hrm_leave_emp_app` | `leave_requests`, approval instance | Range, type, reason. Status number kept as a source token until validated | Pay effect empty | Requests versus day-result leave marks |
| `employee_overtime` | None for 2026 | Newest audited rows were 2018 | Empty for the studied year is evidence, not payable OT | Record the empty count |
| Attendance `ot_hours` | `overtime_calculations` only as seconds if that unit is confirmed for this company | August stored seconds after shift end | Not payable and not money | Quantity reconciled in seconds, separately from pay |
| `eligible_for_ot` | Not loaded | The flag did not match who was paid | LEGACY ONLY | Must not appear as eligibility |
| `employee_salary` | `payroll_results` and `payroll_result_heads` | Amounts only. `declares_no_attendance` | No attendance inputs were stored. Do not reconstruct them. OT head money is paid OT | Earnings, deductions, net, OT head |
| `salary_month_lock`, `attendance_month_lock` | Not loaded as close or cutoff | Both tables had 0 rows | A later Yes still needs business confirmation | Count of lock rows is part of the baseline even when zero |

## 6. VTL mapping

Source evidence is the September 2026 trace for company 20 only. Sibling aliases are not this mapping.

| Source | Target | Transformation | Limitation | Validation |
|---|---|---|---|---|
| `employees` | `employees`, episodes, assignments | Code, name, joining, unit, department, designation, status, weekly off | `photo` was null. `dis` and `pf_member_id` labels in code are not universal fields | Active codes. Duplicate codes are exceptions |
| `emp_shift_calendar` | `shift_assignment_candidates` kind `roster` | Dated roster rows. September day shift matched the calendar | Employee `shift_id` differed on 30 rows. Those default rows load as default candidates, not as the resolved winner | Roster count and the 30 mismatches listed, not collapsed |
| `employees.shift_id` | Default candidates | Preserved beside the roster | Not the universal winner | BD-D03 unresolved |
| `emp_code_to_punch_card` | `punch_identities` | Card can differ from the code. Duplicate cards 126384 and 132058 were seen | No silent merge | Duplicate active identities are exceptions |
| `all_att_log` | `punch_events` | Timestamp unchanged. Example: five events on 2026-09-02 for card 126065 | The day stored only the first and last. Middle events stay as punches and are not invented onto the day. `area` and `balid` uninterpreted | Mapped event count. Selected-link count only where in or out matches an event |
| `attendance_detail` | Result version and current pointer | Letter tokens preserved as legacy tokens. Stored date is historical `work_date` | Missing out stored as A is evidence for BD-D02, not the decision. In without out used `00:00:00`, which must not become a real midnight punch | Token counts. Zero-time punches flagged |
| `attendance_approval_file` | Corrections and value stages | Requested values where present. Displayed shift-boundary times are not written onto `punch_events` | Original displayed punch was often overwritten. Original stage stays absent and is a limitation. Status 1 is not a universal approved code | Every request. Days that changed versus days that did not |
| `hrm_leave_emp_app` | Leave requests and impacts | Status 3 marked days in the sample. That number is a source token | Sixteen leave days did not change basic. Not a pay rule | Request days versus result letters |
| `employee_overtime` | None for September 2026 | 0 rows | Not payable OT | Empty count recorded |
| Attendance `ot_hours` | Calculated OT only if the company confirms the unit is whole hours | 173 September rows, each with an approval file the same day. Other long days stayed blank | A code branch blanks `ot_hours` for alias `vtl`, which conflicts with those 173 rows. Do not resolve that conflict by picking a formula | Hour totals separate from payroll |
| `eligible_for_ot` | Not loaded | 0 of 1,420 employees | LEGACY ONLY | Must not drive payable OT |
| `employee_salary` | Historical payroll results and heads | Amounts for 228 employees, 27 heads, company id 5 on the salary row | Absenteeism head is a stored amount, not a formula to copy. No OT head. Paid OT is zero because no amount exists, not because a rate was chosen | Head totals and net |
| Lock tables | Not loaded as period close | Attendance last Yes 2020-09. Salary last Yes 2023-06 | September 2026 was processed without a lock | Lock rows are baseline evidence, not cutoff |
| `nms_data` | Not core attendance | 0 rows in the company 20 copy | CLIENT EXTENSION for other company ids | Empty count. Do not load as day results |
| Perquisite payslip layouts | Extension enablement only if the business still needs that print | Not the snapshot | CLIENT EXTENSION | Not a second net pay |

Work date: the September attendance date is the historical `work_date`. Punch times stay on the event. A cross-midnight shift capability exists on the shift window. It is not used to move historical days onto a new date.

## 7. Core Company mapping

No source month is available, so no field mapping is invented.

When a company is selected, the read-only pack is:

| Question | Evidence required |
|---|---|
| Which database | The catalog `db_name` for that company id, as a read-only snapshot. Not another company’s database |
| Identity | `companies.id`, name, and alias. Alias is a label, not a rule |
| Modules | Assigned modules and whether attendance, leave, and payroll are actually posted |
| Employees | Count, active and separated, duplicate codes |
| Shift source | Row count of `emp_shift_calendar` versus use of `employees.shift_id` for one closed month. Mismatch count |
| Shift window | Whether any shift end is on the next calendar day |
| Weekly off | Which column holds it, and whether `photo` is involved |
| Punches | Whether day in and out match `all_att_log` min and max. Whether `company` is null. Whether `area` and `balid` are populated |
| Punch map | Duplicate cards. Whether the card equals the employee code |
| Day result | Distinct status tokens and day-status tokens. How a missing punch is stored |
| Leave | Request status numbers and whether the day result changes |
| Correction | Whether an approved row changes the stored day. Whether original times remain |
| Approval | Status number meanings confirmed by that company’s users, not by another alias |
| OT | Day quantity, unit, eligibility column, `employee_overtime` rows, and any OT salary head |
| Payroll | Head list, whether amounts match a visible formula, payslip source, lock rows |
| Extensions | NMS, perquisite, or other layouts actually used |

Atlas local database name in the catalog is `proedgea_atlas`. CEVA is `proedgea_ceva`. Neither month is an input to this document. A later dry run may find a local copy. That copy still needs the pack above before anyone maps it.

## 8. Common versus different

| Domain | Class | Why |
|---|---|---|
| Company, employee, department, designation | A. Common | Both audited companies have them. Target tenant and master data |
| One stored day per employee and date | A. Common | Both store a day result apart from the raw log |
| Punch identity distinct from employee code | A. Common | Equality happens sometimes and is still a map |
| Payslip reads stored salary amounts | A. Common | Both company 20 and 24. The columns are not a snapshot |
| Immutable events, versioned results, separate OT figures, copied payroll inputs | A. Common as target capabilities | The legacy systems do not fully store them. The target does |
| Status vocabulary, weekly-off storage, which punches are kept | B. Configurable or transform | Words versus letters. Photo versus calendar. Min/max versus all events |
| Shift winner, incomplete punch, grace, leave pay, absence, OT rules, cutoff, approval steps, snapshot lines | G. Policy not decided | D01–D18. The two clients disagree or the data does not prove a rule |
| Roster versus employee shift, letter versus word tokens, seconds versus whole hours, boundary rewrite versus unchanged day | C. Transformation | Load both shapes into candidates, tokens, quantities, and correction stages |
| NMS payslip, perquisite layouts, mobile GPS, unnamed device protocol | D. Client extension | Off until that company enables them |
| `db_alias`, `previous_db_id`, `eligible_for_ot`, boss columns as the workflow, photo as a column meaning | E. Legacy-only | Not target rules |
| `area`, `balid`, punch writer, Atlas and CEVA months, `payroll_aug_13` operation | F. Unknown | Exception or a future trace. No meaning is assigned |
| Core Company behavior | F. Unknown | Selection not made |

Nothing in the common set requires the schema to prefer Proedge’s employee shift or VTL’s calendar.

## 9. Target schema validation

| Domain | Proedge | VTL | Core Company | Verdict |
|---|---|---|---|---|
| Company and organization | YES WITH TRANSFORMATION | YES WITH TRANSFORMATION | UNKNOWN | No schema gap from the two audits |
| Employee and employment | YES WITH TRANSFORMATION | YES WITH TRANSFORMATION | UNKNOWN | History gaps are source limits, not missing tables |
| Reporting | YES WITH TRANSFORMATION | YES WITH TRANSFORMATION | UNKNOWN | Chain tables hold two links without becoming the workflow |
| Shift and cross-midnight | YES WITH CONFIGURATION | YES WITH CONFIGURATION | UNKNOWN | Candidates plus a versioned window. Winner value empty |
| Holiday and weekly off | YES WITH TRANSFORMATION | YES WITH TRANSFORMATION | UNKNOWN | Rules, not a photo column |
| Punch identity and events | YES WITH TRANSFORMATION | YES WITH TRANSFORMATION | UNKNOWN | Events have no `work_date` |
| Day result and work date | YES WITH TRANSFORMATION | YES WITH TRANSFORMATION | UNKNOWN | Legacy token plus separate incomplete condition |
| Leave | YES WITH CONFIGURATION | YES WITH CONFIGURATION | UNKNOWN | Pay effect empty |
| Correction and approval | YES WITH TRANSFORMATION | YES WITH TRANSFORMATION | UNKNOWN | Four stages. Missing original stays missing |
| OT | YES WITH CONFIGURATION | YES WITH CONFIGURATION | UNKNOWN | Three figures. Inequality allowed |
| Payroll and payslip | YES WITH TRANSFORMATION | YES WITH TRANSFORMATION | UNKNOWN | Historical amounts with inputs explicitly absent |
| Period control | YES WITH CONFIGURATION | YES WITH CONFIGURATION | UNKNOWN | Empty or stale locks are not close |
| Extensions | Not required for company 24 | Perquisite is CLIENT EXTENSION. NMS is not company 20’s core | UNKNOWN | Extension tables exist and stay off |
| All three companies | — | — | — | UNKNOWN until a Core Company month exists |

**SCHEMA GAP: none demonstrated** by Proedge and VTL. The schema already has distinct places for their conflicting facts. No conceptual change is proposed. A later Core Company trace can still find a gap. That gap would be reported then, not patched from a guess.

If a future trace found a required fact with nowhere to go, the minimum response would be a new optional fact or extension slot, not an alias column. That situation is not present in the audited evidence.

## 10. Attendance validation

The target represents both audited attendance shapes by data, not by `if alias`.

| Topic | Proedge | VTL | How the target holds both |
|---|---|---|---|
| Shift source | Employee shift. Roster empty | Calendar. Employee shift can differ | Default candidates and roster candidates. Resolution for history cites the shift stored on that day. Future winner is BD-D03 |
| Late and early | Label after about 16 minutes. Seconds still stored from shift start | Late from the first minute. Example 117 seconds | Duration on the result. Threshold is policy, empty |
| Missing punch | Present plus text. Day stays present | Status A. Empty punch stored as `00:00:00` | Incomplete condition separate from status token. `00:00:00` is flagged, not treated as a real punch time. Outcome is BD-D02 |
| Corrections | Requests stored. August day unchanged | Displayed time moved to the shift boundary. Raw log event remains | Correction stages. Punch event immutable |
| OT | Seconds on the day. Pay is a different head | Hours on some approved days. Pay is zero | Calculated quantity and paid amount in different tables |
| Work date | Stored attendance date | Stored attendance date. Window can cross midnight | Historical `work_date` is the stored day. Punch time stays the timestamp. Cross-midnight is a shift-version flag for go-forward use |
| Core Company | Not observed | Not observed | Not validated |

No alias-specific processor is required to store these differences.

## 11. Leave validation

Both companies can load leave types, requests, and day impacts. Both samples left basic pay unchanged. That is a historical fact for those rows, not BD-D06.

Status numbers are source tokens until each company validates them. VTL’s status 3 and Proedge’s status 3 are not automatically the same future outcome, even if both were called approved in the audits. The approval instance stores the validated outcome. The number is not a universal column meaning.

Balances are loaded only when the source has a balance. Neither studied month established a balance ledger as the payroll input. Absence of a balance is “not in source,” not zero.

Core Company leave is not observed.

## 12. Correction and approval validation

| Fact | Proedge August | VTL September | Target |
|---|---|---|---|
| Request exists | Yes, for one employee | Yes, many rows | `attendance_corrections` |
| Day changed | No | Often the displayed time changed | Final stage records what the day became, or that it did not change |
| Original value | Not a reliable before-image | Often overwritten on the day | Original stage omitted and listed as a limitation |
| Approver | Name string. Dates null on the sample | Name or code. Dates as strings | Unknown actor marker. Name kept as a limitation |
| Status number | 1 and 4 in the sample | 1 means HR-approved in that alias’s vocabulary. Other aliases differ | Not converted without that company’s validation |
| Chain | Boss columns | Boss columns, sometimes null | Not loaded as workflow steps |

BD-D16 and BD-D17 stay open. The schema does not need a boss column to hold either client’s history.

Core Company approval is not observed.

## 13. OT validation

| | Proedge | VTL | Target |
|---|---|---|---|
| Calculated evidence | Seconds on the day | Whole hours on 173 rows | `overtime_calculations` with a unit label, only when the unit is confirmed |
| Payable evidence | No separate store | No separate store | `payable_overtime` remains empty for history unless a real payable source appears |
| Paid evidence | OT head 6,158.25 across 6 employees, not equal to the seconds | No OT head. Paid 0 | Payroll head amount |
| Eligibility flag | Does not match pay | Does not match hours | Not loaded |
| Separate approval | Not observed | The 173 hour rows sit beside attendance corrections. That is not an OT approval type | Not created from punch corrections |

The inequality calculated ≠ payable ≠ paid is already true in the evidence. The schema allows it. No formula is selected.

Core Company OT is not observed.

## 14. Payroll validation

| Need | Can the schema represent it | Decision left open |
|---|---|---|
| Different salary heads | Yes. Company-defined `salary_heads`. Proedge has an OT head. VTL September has Absenteeism and allowances and no OT head | Head lists stay per company |
| Different payroll inputs | Yes. Historical runs declare no attendance inputs because none were stored. A future run may declare inputs | Which inputs is configuration. BD-D18 for optional snapshot lines |
| Different OT payment | Yes. Money on the result, or no OT head at all | BD-D12 |
| Different leave and absence treatment | Yes, as stored amounts. VTL Absenteeism is not one clean formula. Proedge basic did not move with absence | BD-D06 and BD-D07 |
| Historical amounts | Yes. Finalized historical results. Not recalculated | None |
| Finalized snapshot | Yes. The loaded result is immutable. Later attendance versions do not update it | Close and reopen remain BD-D14 and BD-D15 |

Core Company payroll is not observed. Nothing here chooses a future pay rule.

## 15. Migration completeness

| Class | Proedge | VTL | Core Company |
|---|---|---|---|
| Direct | Company, names, codes, departments, designations, head names, punch timestamps, salary amounts | Same, plus roster dates and weekly-off column where it is a real weekly off | Not assessed |
| Transformation | Photo or weekly-off text into a rule only after confirmation. Default shift into a candidate. Day token into a legacy status. Seconds into calculated OT if unit confirmed. Salary rows into results with inputs absent | Calendar into roster candidates. Default shift kept beside it. Letters into legacy tokens. Hours into calculated OT if unit confirmed. Boundary times into correction values, not into punches | Not assessed |
| Reconstruction | Prior day versions and approval user ids. Do not fabricate | Original punch after overwrite. Middle punches. Do not fabricate | Not assessed |
| Historical evidence only | Empty 2026 `employee_overtime`. Empty lock tables. `area`, `balid` | Empty `employee_overtime`, empty `nms_data`, stale lock Yes rows, `area`, `balid` | Not assessed |
| Ambiguous | Status numbers, photo text, whether `weekly_holiday` and photo disagree | Status numbers, `00:00:00` punches, OT blanking branch versus 173 filled rows | Entire operated month |
| Needs business validation | Duplicate identities, unknown actors, OT unit, lock meaning, weekly-off column | Same, plus Absenteeism amounts that do not share one formula, and duplicate cards | Selection itself |

For each company that later runs:

```
SOURCE COUNT = LOADED + APPROVED EXCEPTIONS
```

Unexplained missing records must be zero. This document does not certify either company. Proedge and VTL are mapped, not approved.

## 16. Company reconciliation plan

Later, and separately, each company reconciles the following against its own production baseline. Core Company has no pack until it is selected. “Not in source” is a valid result. Zero is used only when the source was counted and is empty.

| Block | Measures |
|---|---|
| Master data | Company identity. Employees. Active and separated. Employee codes. Duplicate codes. Departments. Designations. Reporting links that were actually populated. Shift definitions. Default candidates. Roster candidates |
| Attendance | Raw punches mapped to the company. Unmapped punches. Attendance days. Legacy status tokens. Historical work dates. Corrections and how many changed the day |
| Leave | Applications. Validated outcomes. Days marked on results. Balances if the source has them |
| OT | Calculated quantity in the source unit. Payable quantity only if a payable source exists. Paid amount on payroll |
| Payroll | Employees paid. Heads. Earnings. Deductions. Net. OT payment. Period totals. Explicit absence of attendance inputs |

Company 20 and company 24 do not share one reconciliation sign-off.

## 17. Dry-run strategy

Live migration is not part of this step. The order is:

| Stage | Work | Status |
|---|---|---|
| 1 | Proedge, VTL, and Core Company mapping | Proedge and VTL are in this document. Core Company is blocked |
| 2 | Target schema validation | Done for the two audited shapes. No schema gap found. Third company outstanding |
| 3 | Read-only source snapshots | Not started. Local audit copies are not the go-live baseline |
| 4 | Dry-run transformation | Not started |
| 5 | Load into an isolated migration database | Not started. Not production |
| 6 | Technical reconciliation | Not started |
| 7 | Business reconciliation | Not started |
| 8 | Exception resolution | Not started |
| 9 | Repeat the dry run after mapping fixes | Not started |

Stage 1 cannot close until a Core Company is selected from the evidence in section 3. Stages 3 through 9 are per company. Proedge’s dry run does not validate VTL, and neither validates the Core Company.

## 18. Legacy behaviors that must not become universal rules

| Behavior | Why it stays non-universal |
|---|---|
| Proedge employee `shift_id` as the only shift | VTL’s calendar disagrees. BD-D03 |
| VTL calendar as the only shift | Proedge’s August roster was empty. BD-D03 |
| About 16 minutes before a Late label | VTL was late at 117 seconds. BD-D01 |
| Late measured from the first minute | Proedge’s label waited. BD-D01 and BD-D04 |
| Missing punch becomes Present plus text | VTL stored A. BD-D02 |
| Missing punch becomes A | Proedge did not. BD-D02 |
| `00:00:00` as a real punch | It is an empty-value pattern |
| Letter codes or English words as the only status set | Vocabulary is configuration |
| Status number 1 or 3 as HR-approved for every company | Alias vocabularies differ |
| `employees.photo` as weekly off | LEGACY ONLY storage |
| `eligible_for_ot` | Did not match either month |
| Seconds after shift end, or whole hours, as the OT formula | BD-D09 |
| Rounding at a 0.75 fraction | BD-D10 |
| `basic/208×2` | Printed and not multiplied. BD-D12 |
| Boss and grand-boss as the workflow | BD-D17 |
| Gross/30 times absent days | VTL amounts often do not match. BD-D07 |
| “Leave does not change basic” as a rule | It happened in samples. BD-D06 |
| Empty or stale lock rows as a closed period | BD-D13 and BD-D14 |
| NMS figures as attendance | CLIENT EXTENSION. Empty for company 20 |
| Min/max as the only stored punches | The target keeps events |
| `db_alias` and `previous_db_id` | LEGACY ONLY |
| `area` and `balid` as device identity | UNKNOWN |

## 19. D01–D18 protection

This validation does not choose a policy. Where Proedge and VTL differ, the matrix says CONFIGURATION or BUSINESS DECISION. The Core Company is not used to break the tie, because it has no trace.

| ID | Left open |
|---|---|
| BD-D01 | Grace length |
| BD-D02 | Incomplete-punch outcome |
| BD-D03 | Shift winner |
| BD-D04 | When late duration starts |
| BD-D05 | Early-out pay |
| BD-D06 | Leave pay |
| BD-D07 | Absence formula |
| BD-D08 | OT eligibility |
| BD-D09 | OT formula |
| BD-D10 | OT rounding |
| BD-D11 | Separate OT approval |
| BD-D12 | OT rate and payment |
| BD-D13 | Attendance cutoff |
| BD-D14 | Payroll close |
| BD-D15 | Who may reopen. DEFERRED |
| BD-D16 | Correction fields |
| BD-D17 | Approval sequence |
| BD-D18 | Optional snapshot lines |

No historical constant from either client was written as a `policy_values` value.

## 20. Findings and gaps

| Finding | Consequence |
|---|---|
| Proedge and VTL both fit the target schema | No redesign around either client |
| Their differences are configuration, transformation, legacy-only, or D01–D18 | The universal model does not need a third attendance engine |
| No schema gap is demonstrated | The schema specification is not modified |
| Core Company cannot be selected | Three-company validation is incomplete |
| Atlas and CEVA are the leading candidates | Only because they are already named and untraced, not because their behavior is known |
| `proedgea_payroll_aug_13` is an evidence gap | Many catalog rows, no operated-month proof |
| `area`, `balid`, and the punch writer remain unknown | They are not mapping fields |
| Neither audited company is MIGRATION APPROVED | Mapping is not a certificate |

## 21. Readiness

| Item | Assessment |
|---|---|
| Proedge used as the universal template | NO |
| VTL used as the universal template | NO |
| Core Company selected from evidence | NO. Evidence is insufficient. Selection is deliberately not forced |
| Two audited companies representable without a schema change | YES |
| Third-company validation | NOT READY |
| Common capability separated from policy | YES |
| Transformation separated from configuration | YES |
| Extensions kept off the core | YES |
| Unknown meanings left unknown | YES |
| Continuity equation still the migration goal | YES |
| Company-wise reconciliation still mandatory | YES |
| D01–D18 | OPEN. BD-D15 DEFERRED |
| Production data | UNCHANGED |
| Migration code | NOT WRITTEN |

## 22. Next step

Do not start a dry-run load. The next evidence step is a read-only closed-month pack for one candidate that is not company 24 and not company 20. Atlas company 10 or CEVA company 34 should be confirmed or rejected with the current-operation check in section 3. After that pack exists, repeat sections 3, 7, 9, and 15 for that company only.

No company is MIGRATION APPROVED.

### Verification

| Check | Result |
|---|---|
| Proedge is not the universal template | Yes |
| VTL is not the universal template | Yes |
| Core Company selected only from evidence | Not selected. Gap recorded |
| Common capability separated from policy | Yes. Section 8 |
| Transformation separated from configuration | Yes. Sections 4 and 8 |
| Client extensions remain separate | Yes |
| Unknown legacy meanings remain unknown | Yes |
| Schema gaps reported rather than silently fixed | No gap found. None applied |
| SOURCE COUNT = LOADED + APPROVED EXCEPTIONS | Yes. Section 15 |
| Zero unexplained missing records remains the goal | Yes |
| Company-wise reconciliation remains mandatory | Yes. Section 16 |
| Production data changed | No |
| Migration code written | No |
| D01–D18 remain open | Yes. Section 19 |

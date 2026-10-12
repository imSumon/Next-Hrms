# Universal HRMS Third Reference Company — Closed-Month Audit

Status: read-only audit. No database was modified. No migration was run. No company is migration-approved.

Proedge remains company 24. Viyellatex remains company 20. Neither is the universal template. This third company is also not the template.

---

## 1. Selection evidence

Local databases available for this audit: `legacy_master`, `legacy_proedge`, `legacy_vtlhrm`, `legacy_atlas`, `legacy_shared`. There is no local CEVA database (`proedgea_ceva` is not loaded). The shared older name `proedgea_payroll_aug_13` is not loaded as its own database.

| Candidate | Evidence in this environment | Selected |
|---|---|---|
| CEVA, company 34 | Not present as a local database. No attendance or payroll rows to read | No |
| Atlas Copco Bangladesh Ltd., company 10, alias `atlas`, database name `proedgea_atlas` | Local database `legacy_atlas`. 195 tables. 151 employees. Attendance from 2025-06-01 through 2026-09-30. Payroll rows through September 2026. Attendance, leave, and payroll modules are active | Yes |

Atlas was not selected because of its name. It is the only company other than 24 and 20 that is both loaded and operating a recent completed month. Catalog company 26, Grameen Telecom Trust, is a different company. Atlas salary rows store `company_id` 26 because Atlas `previous_db_id` is 26. That number is a legacy key, not the target tenant id. The target company is catalog id 10.

## 2. Company profile

| Item | Evidence |
|---|---|
| Catalog id | 10 |
| Name | Atlas Copco Bangladesh Ltd. |
| Alias | `atlas` |
| Source database name | `proedgea_atlas` |
| Local copy | `legacy_atlas` |
| Legacy unit stored on employees | `emp_unit` 26 on all 151 rows. This matches `previous_db_id`, the same legacy pattern as the other two audits |
| Employees | 151 |
| `active` = 1 | 133 |
| `disconrinue` = 1 | 92. The column name is the legacy spelling |
| `active` = 1 and not discontinued | 59 |
| September attendance people | 60 |
| September payroll people | 59 |
| Branch | `company_branch_id` is set on 117 employees. Branch is used |
| Weekly off storage | `weekly_holiday` is null or blank. `photo` is `Friday,Saturday` for all 60 September attendees, and for 74 employees overall |
| Shift | Every employee `shift_id` = 1. `emp_shift_calendar` has 0 rows |
| OT flag | `eligible_for_ot` = 1 on 0 employees |
| Reporting columns | `boss` is set on 141 employees. `g_father` exists. These are columns, not a workflow |
| Modules assigned | Organization Setting (module active No), PIMS (active No), Payroll, Leave, Attendance, File Cabinet, PF Management, Atlas Bills, Personal files, Data Archive |
| Client-only module | Atlas Bills, module id 16 |
| Payslip and reports | Company id 10 adds Atlas Bank Advise, Atlas Bank Forwarding, and Atlas JV buttons beside the shared payroll report screen. Separate Atlas letter views exist. `nms_data` has 0 rows |

Organization Setting and PIMS are assigned with module `active` = No. Attendance, leave, and payroll are `active` = Yes. Assignment was not treated as proof of use. Use is the September rows below.

## 3. Selected closed month

**September 2026.**

| Evidence | Result |
|---|---|
| Attendance dates | 2026-09-01 through 2026-09-30. 30 distinct dates. 1,800 rows. 60 employees. 60 × 30 = 1,800 |
| Attendance after September | 0 rows |
| Payroll | September 2026: 1,239 rows, 59 employees, 21 heads, `company_id` 26. 59 × 21 = 1,239 |
| Payroll after September | 0 rows for October 2026 |
| `salary_month_lock` for 2026 | No row |
| `attendance_month_lock` | One row only: 2025-01, `month_lock` = Yes, `company_id` 26. Not September 2026 |

September is the latest month with a complete attendance span and a payroll run, and the following month has not been started in this copy. The lock tables do not say September is closed. Completeness is from coverage, not from a lock flag.

## 4. Attendance trace

September status tokens are words, in the same family as Proedge, not the VTL letters.

| `attendance_status` | Rows |
|---|---|
| Present | 955 |
| Weekly Holiday | 480 |
| Absent | 340 |
| Leave | 25 |

There is no Holiday row in September. `att_holiday_calendar` has 0 rows in September 2026. Weekly Holiday is 480 rows, which is 8 days × 60 employees. September 2026 has four Fridays and four Saturdays.

| `day_status` | Rows |
|---|---|
| Blank | 1,235 |
| Late | 179 |
| Early Out | 145 |
| Late, Early Out | 60 |
| In punch missingEarly Out | 56 |
| Out punch missing, Late | 55 |
| Out punch missing | 45 |
| EL | 13 |
| CL | 6 |
| SL | 4 |
| PL | 2 |

The token `In punch missingEarly Out` is stored without a separator. That is the stored text. It is not a cleaned status design.

### Shift

`att_shift` has two definitions. `next_day` is 0 on both. September uses only id 1.

| Id | Name | Start | End | `next_day` |
|---|---|---|---|---|
| 1 | General (9:30 AM-6:00 PM) | 09:30:59 | 18:00:00 | 0 |
| 2 | General (9:00 AM-3:30 PM) | 09:00:59 | 15:30:00 | 0 |

Attendance `shift_id` equals `employees.shift_id` on all 1,800 September rows. The roster table is empty. The start time ends in `:59`, which is a stored clock value, not a grace rule.

No September shift crosses midnight. `next_day` = 0 and the worked window is 09:30–18:00 on the same calendar date. Cross-midnight is not observed here. The target still allows it for other companies.

### Work date and punches

The attendance `date` is the stored day. Punch timestamps keep their own clock time.

Traced day: employee code EB9006, punch identity 9006, 2026-09-02. Eight rows in `legacy_master.all_att_log`:

| Event time | `company` | `area` | `update_datetime` |
|---|---|---|---|
| 09:33:36 | NULL | Atlas | NULL |
| 09:33:37 | NULL | Atlas | NULL |
| 11:10:09 | NULL | Atlas | NULL |
| 11:12:35 | NULL | Atlas | NULL |
| 13:49:12 | NULL | Atlas | NULL |
| 14:26:17 | NULL | Atlas | NULL |
| 14:26:49 | NULL | Atlas | NULL |
| 14:48:24 | NULL | Atlas | NULL |

The day result kept 09:33:36 and 14:48:24. The six middle events remained in the log and were not stored on the day. `company` is null. `area` is the text `Atlas`. `balid` is populated and is not interpreted. `update_datetime` is null, which matches the external-writer pattern from the earlier audits, not the in-repo CSV upload.

Punch map: 65 cards, 65 employee codes, 65 distinct card numbers. 8 cards equal the employee code. 57 differ. No duplicate card number was found in this table. September attendance `punch_id` for EB9006 is 9006, the card, not `EB9006`.

### Missing punch, late, and early

Missing in or out is stored as Present plus `day_status` text (`Out punch missing`, `Out punch missing, Late`, `In punch missingEarly Out`). It is not stored as Absent.

Late number, when present, is seconds after 09:30:59. A punch at 10:04:55 has `late` 2036, which is the elapsed seconds from 09:30:59, and `day_status` Late. A punch at 10:01:44 on another September row is Present and is not labeled Late. No September punch falls between 10:01:44 and 10:04:55, so the exact label threshold is not measured. It is not the first minute, and it is not the roughly 16-minute label seen at Proedge. The stored seconds and the label are different facts.

Some Late rows have an in-punch at 22:22 or 22:44 with `late` above 46,000 seconds. Those are stored values. They are not evidence of a cross-midnight shift, because the shift window does not cross midnight. They are exceptions to interpret later, not a new rule.

Early Out is a `day_status` while `attendance_status` stays Present. EB9006 left at 14:48:24 against an 18:00:00 end and is Early Out. No separate early-seconds column was required for that label.

## 5. Leave trace

Leave applications that overlap September 2026: 27.

| Status number | Applications | What the number means |
|---|---|---|
| 3 | 19 | Not converted. In this month it often sits on days whose result is Leave |
| 0 | 8 | Not converted. Overlapping attendance days in the sample are Absent |

Types and codes seen: Casual Leave CL, Sick Leave SL, Annual Leave EL, Paternity Leave PL.

September result tokens match the leave-day counts: EL 13, CL 6, SL 4, PL 2. Those 25 days are the 25 `attendance_status` = Leave rows.

Status 3 does not mark every overlapped day:

| Status | Code | What September attendance shows |
|---|---|---|
| 0 | CL | 7 Absent |
| 0 | SL | 2 Absent |
| 3 | CL | 6 Leave CL, 4 Present Late, 1 Present Late and Early Out |
| 3 | EL | 13 Leave EL, 2 Weekly Holiday |
| 3 | PL | 2 Leave PL, 1 Present Early Out |
| 3 | SL | 4 Leave SL, 2 Absent |

A status 3 range can include a weekly holiday or a day that stays Present or Absent. The rule that chooses those days is not established.

Payroll interaction: no leave-pay formula was calculated. One employee with 10 September absences and 0 leave, code EB9050, has employee `basic_salary` 71527.15 and September Basic Salary head 71527.15. That is one stored pair. It does not decide absence or leave pay.

Leave balances were not established as a ledger that payroll read.

## 6. Correction and approval trace

September 2026 has **0** rows in `attendance_approval_file`.

The table overall has 305 rows, from 2025-01-12 through 2026-08-31.

| Status number | Rows | Meaning assigned |
|---|---|---|
| 1 | 214 | None. Not treated as a universal approved code |
| 2 | 89 | None |
| 10 | 2 | None |

August rows, read only as the nearest evidence, show:

- `employee_id` and `posted_by` hold the employee code, such as EB9108, not a user id.
- `intime` and `outtime` are null on the sampled rows.
- `boss_id` and `gboss_id` are numeric. They are not a step list.
- `first_approver_date` on a status 1 row is the string `14-09-2026`.
- `second_appover_date` is the legacy column name.

Example, not a September fact: status 1, reason Absent, employee EB9108, dates 2026-08-12, 2026-08-19, and 2026-08-20. Those attendance days are Present. Displayed in and out are exactly 09:30:59 and 18:00:00. `actual_in`, `actual_out`, and `original_out_punch` are null. `all_att_log` has 0 events for card 9108 on 2026-08-12. The approval row itself does not contain the shift times. Which process wrote the shift-boundary punches is not proven. September has no correction row to test an overwrite.

## 7. OT trace

| Store | September 2026 | What it is |
|---|---|---|
| `attendance_detail.ot_hours` | Blank or zero on all 1,800 September rows. Also zero filled rows in the whole attendance table | No calculated OT on the day result in this copy |
| `employee_overtime` | No 2026 rows. The table has 24,380 rows from 2013 through 2019, with `total_ot_hour` and `total_ot_allowance` | Historical hours and an allowance amount in one legacy table. Not September payable OT |
| `eligible_for_ot` | 0 employees set | Not used |
| Payroll heads | No overtime head in the 21 September heads | No paid OT amount in this month |

Attendance does not feed an OT quantity into September payroll, because both sides are empty. That is an observed absence. It is not a decision that this company never pays OT, and it is not a universal unpaid-OT rule. The 2013–2019 `employee_overtime` rows were not opened line by line. They are historical evidence that an older OT allowance existed, not a September payable figure.

## 8. Payroll trace

September heads, 59 employees each:

| Head | Kind | Total |
|---|---|---|
| Basic Salary | Earnings | 2928480.24 |
| Special Allowance | Earnings | 1128275.10 |
| House Rent Allowance | Earnings | 651000.00 |
| Medical Allowance | Earnings | 260000.00 |
| Adhoc Allowance (Provident Fund E) | Earnings | 181919.71 |
| Education Allowance | Earnings | 34800.00 |
| Conveyance Allowance | Earnings | 18000.00 |
| Local Transport Allowance, Gratuity Pay, Refund, Sales Commission, Earned Leave, Service rewards & Others, VC, Arrear, Festival Bonus, Other Earnings | Earnings | 0.00 each |
| Income Tax | Deduction | 237117.00 |
| PF | Deduction | 181919.71 |
| Advance Adjustment | Deduction | 20130.00 |
| Others Deduction | Deduction | 0.00 |

PF deduction and the Adhoc Allowance total are the same number, 181919.71. That match is recorded. It is not adopted as a formula.

`employee_salary` stores employee, month, year, head, amount, `company_id`, and type. It does not store attendance status, late, leave days, or OT hours. EB9050’s basic amount equals the employee master despite 10 absences. EB9091 has September attendance, `discontinue_date` 2026-08-25, both `active` = 1 and `disconrinue` = 1, and no September salary row. Payroll for this month is a stored result beside attendance, not a live recalculation of the day results.

No September payslip file was generated during this audit. The report screen for company 10 keeps the shared payroll actions and adds Atlas bank and journal outputs. Those extra outputs are client reports. They are not a second attendance engine. `salary_head.salary_rule` exists as legacy text and was not copied into a target rule.

## 9. Target schema validation

| Domain | Verdict | Why |
|---|---|---|
| Employee structure | YES WITH TRANSFORMATION | Master, department, designation, branch, joining, and discontinue dates load as employee and employment facts. `active` and `disconrinue` can both be 1. That conflict is a data exception, not a new entity |
| Extra employee fields | YES WITH CONFIGURATION | Cost center, band, and salary group are company attributes. The target already allows additional employee attributes |
| Shift model | YES WITH TRANSFORMATION | One default candidate, shift version 09:30:59–18:00:00, `crosses_midnight` false. Empty roster stays empty. Winner value is not set |
| Work date | YES WITH TRANSFORMATION | Historical `work_date` is the attendance date. Punch `event_at` stays the log timestamp |
| Attendance | YES WITH TRANSFORMATION | Word tokens stay legacy tokens. Incomplete punch stays a separate condition from Absent |
| Leave | YES WITH CONFIGURATION | Types and requests load. Status numbers stay unconverted. Pay effect stays empty |
| Corrections | YES WITH TRANSFORMATION | September has none. August rows load as requests with null requested times and unknown actors. The shift-boundary day is not written back onto the raw punch |
| Approval | YES WITH TRANSFORMATION | Ordered steps are not filled from boss columns. Status 1, 2, and 10 are not given meanings |
| OT | YES WITH CONFIGURATION | September calculated, payable, and paid are all absent. Older `employee_overtime` is archive evidence until a year is in scope |
| Payroll | YES WITH TRANSFORMATION | Amounts become a historical snapshot with attendance inputs explicitly absent |
| Historical data | YES WITH TRANSFORMATION | Middle punches remain events. The day keeps first and last only. Prior day versions are not invented |
| Atlas Bills, bank advise, JV, letters | CLIENT EXTENSION REQUIRED | Off for every other company |
| Any domain | SCHEMA GAP | None demonstrated |

The schema specification is not changed.

## 10. Three-company comparison

| Topic | Proedge 24, August 2026 | VTL 20, September 2026 | Atlas 10, September 2026 | Class |
|---|---|---|---|---|
| Tenant | Own database. Alias selects code | Own database. Alias selects code | Own database. Salary `company_id` is `previous_db_id` 26 | LEGACY ONLY for the alias and the old id. COMMON CORE for one company boundary |
| Day status words | Present, Absent, Leave, Holiday, Weekly Holiday | Letters P, A, H, leave codes | Present, Absent, Leave, Weekly Holiday. No public holiday this month | CONFIGURATION for vocabulary. TRANSFORMATION to store the token |
| Shift source | Employee shift. Roster empty | Calendar wins | Employee shift. Roster empty | BUSINESS DECISION BD-D03. Both shapes already have candidate tables |
| Weekly off | `photo` = Friday,Saturday | `weekly_holiday` and calendar H. `photo` null | `photo` = Friday,Saturday. Column `weekly_holiday` empty | LEGACY ONLY for photo storage. COMMON CORE for a weekly-off rule |
| Cross-midnight | Not in the audited windows | Shift can cross midnight | Not on shift 1. `next_day` = 0 | COMMON CORE capability. Not observed at Atlas |
| Raw punches | Shared log, null company, min/max kept on the day | Same, five events reduced to two | Same, eight events reduced to two. `area` text is Atlas | COMMON CORE events. LEGACY ONLY for min/max reduction. UNKNOWN for `balid` |
| Missing punch | Present plus text | Status A | Present plus text | BUSINESS DECISION BD-D02 |
| Late label | About 16 minutes, while seconds start at shift start | From the first minute | Not labeled at 10:01:44. Labeled at 10:04:55. Seconds still start at 09:30:59 | BUSINESS DECISION BD-D01 and BD-D04. No number is chosen |
| Leave mark | Status 3 sample became Leave. Basic unchanged | Status 3 sample became a letter. Basic unchanged | Status 3 often Leave, and sometimes Present, Absent, or Weekly Holiday. One basic amount matched the master despite 10 absences | BUSINESS DECISION BD-D06 and BD-D07 |
| Correction in the studied month | Requests did not change the sample day | Displayed time moved to the shift boundary | No September requests. August status 1 coexists with shift-boundary punches and no raw events | BUSINESS DECISION BD-D16. TRANSFORMATION for partial history |
| Approval identity | Name strings. Boss columns | Name or code. Boss columns. Some null | Employee code. Boss numbers. Dates as strings. Status 1, 2, and 10 | LEGACY ONLY. BUSINESS DECISION BD-D17 |
| Calculated OT | Seconds on the day | Whole hours on some days | None on any attendance row in this copy | BUSINESS DECISION BD-D09 |
| Payable OT | No separate store | No separate store | No September store. Old table through 2019 has hours and an allowance together | TRANSFORMATION. Do not split that old allowance without a decision |
| Paid OT | Salary head, not equal to the seconds | No OT head | No OT head in September | BUSINESS DECISION BD-D12 |
| Payroll | Amounts only | Amounts only. Absenteeism head | Amounts only. Tax, PF, advance. No absence head | COMMON CORE historical snapshot. CONFIGURATION for heads |
| Payslip | Stored salary | Stored salary. NMS is other companies | Stored-salary family, plus Atlas bank and JV reports | CLIENT EXTENSION for Atlas reports. COMMON CORE for reading a snapshot |
| Period lock | Empty | Stale Yes, not the studied month | 2025-01 attendance Yes only. September 2026 unlocked | BUSINESS DECISION BD-D13 and BD-D14 |
| Extension | None required for the core month | Perquisite layouts. NMS empty | Atlas Bills, bank advise, JV, letters | CLIENT EXTENSION |

Atlas does not create a third attendance engine. It repeats the word-status, employee-shift, photo-weekend, missing-punch-as-text pattern already seen at Proedge, with its own shift clock, its own unlabeled late gap, its own head list, and its own report buttons. Those differences fit configuration, transformation, and extension. They do not fit a new core.

## 11. Migration implications

Nothing was migrated.

| Class | Atlas September 2026 and related master data |
|---|---|
| Direct | Company 10 identity. Employee codes. Names. Departments. Designations. Branch ids. Head names. Punch timestamps. Salary amounts. Leave type names |
| Transformation | `photo` text into a weekly-off rule only after confirmation. `shift_id` into a default candidate, not a winner. Day words into legacy status tokens. First and last punch onto the day, with the other events kept as events. Salary rows into a snapshot that declares no attendance inputs. `company_id` 26 mapped to catalog company 10, never to Grameen company 26 |
| Reconstruction | Do not invent roster rows. Do not invent September corrections. Do not invent middle punches onto the day. Do not invent the writer of the August shift-boundary punches. Do not invent pre-images |
| Ambiguous | Status numbers 0, 1, 2, 3, and 10. Late rows at 22:22 and 22:44. `active` and discontinued both true on EB9091. PF total equal to the Adhoc Allowance total. `balid`. `area` |
| Business validation | Whether `Friday,Saturday` in `photo` is the weekly-off rule. What status 3 and status 1 mean at Atlas, separately from VTL and Proedge. Whether August shift-boundary days were approvals. Whether 2013–2019 OT allowances are in a later migration scope. Whether Atlas Bills and the bank files are still required |
| Unsafe to interpret | `balid` as a device id. `previous_db_id` 26 as catalog company 26. `eligible_for_ot`. `salary_rule` text as a universal formula. The 2025-01 lock as proof that September 2026 is closed |

Continuity for a later Atlas run remains:

```
SOURCE COUNT = LOADED + APPROVED EXCEPTIONS
```

This audit does not certify that equation. Atlas is not MIGRATION APPROVED.

## 12. Evidence gaps

- CEVA is not in the local databases, so it was not a usable third company.
- The exact minute where the Late label starts is not measured. The gap between 10:01:44 and 10:04:55 has no punch.
- No September correction exists. The August shift-boundary days do not identify their writer.
- Leave status numbers were not confirmed with an Atlas business owner.
- `employee_overtime` after 2019 was not re-derived. September simply has no rows.
- Payslip PDF contents were not rendered. The conclusion is from the report screen and from `employee_salary`, not from a generated file.
- `salary_rule` text was not treated as evidence of the September amounts.
- Whether Friday and Saturday are coded only from `photo`, or also defaulted in the processor, was not separated. Every September attendee has that photo text, so both explanations fit this month.
- Punch writer, `balid`, and a vendor name remain unknown.

## 13. D01–D18 protection

No decision is closed. Atlas adds a third observation. It does not pick the value.

| ID | Atlas observation | Still open |
|---|---|---|
| BD-D01 | Late label is later than Proedge’s label and later than VTL’s first minute. Seconds still start at shift start | Grace length |
| BD-D02 | Missing punch stays Present plus text, like Proedge and unlike VTL’s A | Outcome |
| BD-D03 | Employee shift only. Roster empty, like Proedge August and unlike VTL | Winner |
| BD-D04 | Stored late seconds start at 09:30:59 even when the label waits | Measurement start |
| BD-D05 | Early Out is a label. No pay change was traced from it | Pay effect |
| BD-D06 | Status 3 does not mark every day. No leave-pay amount was isolated | Pay class |
| BD-D07 | Ten absences did not change one basic amount. No absence head | Formula |
| BD-D08 | Flag is 0. No September OT | Eligibility |
| BD-D09 | No day quantity in this copy | Formula |
| BD-D10 | No quantity to round | Rounding |
| BD-D11 | No OT approval type. Attendance approvals are not OT approvals | Whether approval exists |
| BD-D12 | No September OT head | Rate and payment |
| BD-D13 | September attendance is not locked | Cutoff |
| BD-D14 | September payroll is not locked | Close |
| BD-D15 | No reopen evidence | DEFERRED |
| BD-D16 | September has no correction. August does not prove which fields an approval may write | Allowed fields |
| BD-D17 | Boss numbers exist. They are not steps | Step list |
| BD-D18 | Historical salary has no attendance copy | Optional lines |

## 14. Final readiness

| Item | Assessment |
|---|---|
| Third company selected from activity, not from the name | YES. Company 10, because it is the only other loaded company with a completed 2026 month |
| CEVA assumed operational | NO. It is not in the local databases |
| September 2026 used as the audit month | YES. Complete attendance and payroll. Not lock-closed |
| Target schema still represents this company | YES, with transformation, configuration, and Atlas report extensions |
| Schema gap | NONE |
| Schema specification changed | NO |
| Universal rule taken from Atlas | NO |
| D01–D18 | OPEN. BD-D15 DEFERRED |
| Migration approved | NO |
| Database modified | NO |
| Migration code written | NO |

Three audited shapes now exist: Proedge’s August 2026 office month, VTL’s September 2026 factory month, and Atlas’s September 2026 office month. Atlas clusters with Proedge on status words, shift source, weekly-off storage, and missing-punch text. It does not collapse those into one required policy, because the clocks, late label, head list, leave-day behavior, and report buttons differ. The universal model remains one product with configuration, not an Atlas, Proedge, or VTL branch.

### Verification

| Check | Result |
|---|---|
| Proedge remains company 24 | Yes |
| VTL remains company 20 | Yes |
| Atlas or CEVA assumed active without evidence | No. Atlas activity was counted. CEVA was absent |
| Behavior invented | No |
| Database modified | No |
| Migration code written | No |
| D01–D18 selected | No |
| Target schema changed | No |
| Any company declared migration-approved | No |

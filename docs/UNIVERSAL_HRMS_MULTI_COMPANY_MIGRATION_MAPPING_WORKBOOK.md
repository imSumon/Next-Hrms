# Universal HRMS Multi-Company Migration Mapping Workbook

Status: design specification only. No migration code, SQL, database change, data load, model, API, or UI is included.

This workbook maps three audited reference companies into the approved target schema. It does not redesign that schema. The third-company audit found no schema gap, and none is introduced here.

No company is the universal template. Proedge, Viyellatex (VTL), and Atlas are sources. A behavior that appears in one of them stays that company’s historical fact.

Approved inputs:

- `docs/UNIVERSAL_HRMS_REQUIREMENTS.md`
- `docs/UNIVERSAL_HRMS_ARCHITECTURE_ASSESSMENT.md`
- `docs/UNIVERSAL_HRMS_LOGICAL_DATA_ARCHITECTURE_ASSESSMENT.md`
- `docs/UNIVERSAL_HRMS_PHYSICAL_DATA_ARCHITECTURE_ASSESSMENT.md`
- `docs/UNIVERSAL_HRMS_TARGET_DATABASE_SCHEMA_SPECIFICATION.md`
- `docs/UNIVERSAL_HRMS_MIGRATION_DATA_CONTINUITY_ARCHITECTURE.md`
- `docs/UNIVERSAL_HRMS_MULTI_COMPANY_MIGRATION_MAPPING_VALIDATION.md`
- `docs/UNIVERSAL_HRMS_THIRD_REFERENCE_COMPANY_CLOSED_MONTH_AUDIT.md`

```
SOURCE COUNT = LOADED + APPROVED EXCEPTIONS
```

Unexplained missing required records must be zero before any company can be approved. This workbook does not certify that equation. No company is MIGRATION APPROVED.

Local audit databases are rehearsal sources. They are not the production baseline. A later dry run against these copies is still not a certificate.

---

## 1. Purpose

Define, for Proedge, VTL, and Atlas:

```
LEGACY SOURCE → SOURCE FIELD → SOURCE MEANING → TRANSFORMATION → TARGET ENTITY/FIELD → VALIDATION → EXCEPTION CONDITION
```

The target is one schema. Company differences land in configuration, transformation, client extensions, legacy-only fields, or open decisions D01–D18. They do not land in a new table or an alias branch.

Historical facts stay historical:

- The attendance date the legacy system stored is the historical `work_date`.
- The punch timestamp stays the punch timestamp.
- A stored payroll amount stays that amount.
- An unknown source meaning stays unknown.

---

## 2. Source databases

Read-only local copies already loaded. No additional full database copy is part of this workbook.

| Role | Local database | What it is |
|---|---|---|
| Proedge company data | `legacy_proedge` | Local copy of `proedgea_newproedgehrm` |
| VTL company data | `legacy_vtlhrm` | Local copy of `proedgea_vtlhrm`. Company 20 only. Sibling aliases are other companies |
| Atlas company data | `legacy_atlas` | Local copy of `proedgea_atlas` |
| Shared punch evidence and company catalog | `legacy_master` | `companies` catalog and `all_att_log` |

`legacy_shared` is present in the environment and is not a source for these three companies.

Not used, and not imported by this workbook:

- `proedgea_ceva` is not loaded. CEVA is not a mapped company.
- `proedgea_payroll_aug_13` is not loaded as its own database.
- `proedgea_attlogvtl` is not a dependency. Device integration stays an external boundary.

Company attribution of a shared punch is through that company’s `emp_code_to_punch_card`. The `company` column on `all_att_log` was null on the traced days. It is not the tenant.

---

## 3. Company mapping

Target `companies.id` is the tenant. Legacy `db_alias` is not a target column and does not select behavior. Legacy `previous_db_id` is not the tenant.

Salary rows store `previous_db_id` in `employee_salary.company_id`. That number is remapped to the catalog company below. It is never loaded as a second tenant.

| Catalog | Name | Alias | Source database | Local copy | Salary `company_id` stored | Target tenant |
|---|---|---|---|---|---|---|
| 24 | Pro-edge Associates Limited | `proedge` | `proedgea_newproedgehrm` | `legacy_proedge` | 5, equal to `previous_db_id` | Company 24 |
| 20 | Viyellatex Group | `vtl` | `proedgea_vtlhrm` | `legacy_vtlhrm` | 5, equal to `previous_db_id` | Company 20 |
| 10 | Atlas Copco Bangladesh Ltd. | `atlas` | `proedgea_atlas` | `legacy_atlas` | 26, equal to `previous_db_id` | Company 10 |

Catalog company 5 is a different catalog row from companies 24 and 20. Catalog company 26 is Grameen Telecom Trust, a different company from Atlas. Mapping salary `company_id` 5 or 26 onto those catalog rows is a company-mapping exception, not a load.

`employees.emp_unit` repeats the same legacy key: 5 for Proedge and VTL, 26 for all 151 Atlas employees. It is not `company_id`.

### Population and limitations

| Item | Proedge 24 | VTL 20 | Atlas 10 |
|---|---|---|---|
| Studied month | August 2026 | September 2026 | September 2026 |
| How the month was judged complete | Attendance and payroll coverage. Lock tables had 0 rows | Coverage. Locks were not used for that month | Coverage. Not a lock. October 2026 has 0 attendance and 0 payroll rows in the copy |
| Month attendance | 1,370 rows, 47 employees, 2026-08 span | 7,231 rows, 244 employees, 2026-09-01 through 2026-09-30 | 1,800 rows, 60 employees, 30 dates, 2026-09-01 through 2026-09-30 |
| Month payroll | 540 rows, 45 employees, `company_id` 5 | 6,156 rows, 228 employees, 27 heads, `company_id` 5 | 1,239 rows, 59 employees, 21 heads, `company_id` 26 |
| Master population evidenced | `eligible_for_ot` was counted on 160 employees | `eligible_for_ot` was counted on 1,420 employees | 151 employees. `active` = 1 on 133. `disconrinue` = 1 on 92. Active and not discontinued: 59 |
| Source-specific limitations | One current employee row is not a full history. August roster is empty. Prior attendance versions were deleted by reprocessing and are not invented. Approver is a name, not a user id | Sibling companies are out of scope. Roster and employee shift both exist and differ on 30 September rows. Displayed correction times were often overwritten. A code branch blanks `ot_hours` for alias `vtl` while 173 September rows contain hours. Duplicate punch cards exist | Salary key 26 must not become Grameen. `active` and `disconrinue` can both be 1. September has no correction rows and no day OT. August shift-boundary punches have no proven writer. Leave status 3 does not mark every overlapped day. Late-label minute is unmeasured |

Full-history row counts beyond these audited figures are taken at extract. They are not estimated here.

---

## 4. Master data mapping

Rules that apply to every company. Field rows are in section 17.

| Object | Source | Target | Transformation | Validation | Exception |
|---|---|---|---|---|---|
| Company | `legacy_master.companies` row for id 24, 20, or 10 | `companies` | `company_name` from the catalog name. `source_key` is the catalog id. Alias is not loaded | One target company per catalog id | A salary `company_id` of 5 or 26 used as the tenant |
| Branch | Branch id where the source actually has a site. Atlas: `employees.company_branch_id` is set on 117 employees | `branches` | One branch per real source site. Factory depth is not turned into a required tree | Site count. Atlas branch ids resolve or are excepted | An id with no master row. Inventing a branch for Proedge or VTL when the audit did not establish one |
| Department | Department master | `departments` | Name and source key. Department is not forced under a branch | Name and count | Missing name |
| Designation | Designation master | `designations` | Name and source key | Name and count | Missing name |
| Employee | `employees` | `employees` | Code and display name. One company | Active code uniqueness inside the company | Duplicate active codes. Cross-company merge |
| Employee code | Employee code column | `employees.employee_code` | Text preserved on the original identity. Not the primary key. Not reassigned | Every source code accounted for | Duplicate code in one company is an explicit exception. Codes are not reused. Two identities are not merged |
| Employee status | `active`, and the legacy discontinue flag (`disconrinue` on Atlas) | `employment_episodes.episode_status` only when the pair is unambiguous | Open when active and not discontinued. Separated when discontinued, not active, and a leaving date exists | Counts of active, discontinued, and conflicts | Both flags true. Atlas EB9091 is that case. The status word is not invented for that row |
| Joining date | Joining date on the employee | `employment_episodes.joining_date` | The source date | Present when the source has it | Missing joining date on a row that is otherwise loaded |
| Leaving date | Discontinue / leaving date | `employment_episodes.last_working_date` | The source date. Empty when the source has none | Date present when the source has it | A September or August attendance day after the leaving date is kept as a historical day and listed. It is not deleted. Atlas EB9091 has `discontinue_date` 2026-08-25 and September attendance |
| Employment facts | Current department, designation, branch, status | `employment_assignments` | One interval from the only span the source row contains. Missing history stays missing | Fact references resolve | Overlapping invented intervals |
| Reporting | `boss`, and grand-boss (`g_father` / boss columns on the approval file) | `reporting_chains`, `reporting_links` | Optional chain evidence when both people exist in the same company. Sequence 1 is the nearest populated manager. A second populated person is sequence 2. This is not a workflow | Both employees resolve | Null boss. A boss id that is not an employee. Approval `boss_id` is not a step |
| Employee group | A real group or category used for shared policy, when one exists | `employee_groups` | Load only a confirmed group | Membership count | A salary-group, band, or cost-center label is not turned into `employee_groups`. Atlas cost center, band, and salary group stay archive attributes |
| Salary heads | Head master, including heads that appear on `employee_salary` | `salary_heads` | Code, name, and earning or deduction as the source labels them | Head list per company | A universal absenteeism head or a universal OT head |
| Salary structure | Structure identity if the source has one, plus employee master amounts | `salary_structures`, `employee_structure_assignments`, `employee_structure_head_values` | Amounts the source stored. No formula | Employees with master amounts | `salary_rule` / `salary_rule_condition` text. It is not a target rule |
| Shift definition | `att_shift` | `shifts`, `shift_versions` | Name, `start_time`, `end_time`. `next_day` sets `crosses_midnight` when that flag is present | Window present | Grace minutes. A `:59` start time is a stored clock, not grace |
| Shift assignment | `employees.shift_id` and `emp_shift_calendar` | `shift_assignment_candidates` | Default and roster are both candidates where rows exist. See section 7 | Candidate counts | A `shift_resolutions` row that encodes a future winner |
| Holiday | `att_holiday_calendar` and Holiday day tokens | `holiday_calendars`, `public_holidays` | Source date and name | Date count | A weekly off loaded as a public holiday |
| Weekly holiday | `employees.photo` weekday text, or `employees.weekly_holiday`, only after confirmation | `weekly_off_rules` | Confirmed weekday set only. See section 8 | Confirmed rows match the source text | Unconfirmed photo text. Disagreement between photo and `weekly_holiday` |

`policy_values` are not written from any of these sources.

---

## 5. Employee mapping

The business employee code is `employees.employee_code`. The target primary key is a new surrogate `id`. Legacy employee ids are `source_system` + `source_key`.

| Concern | Rule |
|---|---|
| Company scope | An employee loads under one target `company_id`: 24, 20, or 10. Shared codes across companies stay different people |
| Duplicate codes | Two active rows with the same code in one company are exceptions. They are not merged and not dropped |
| Legacy ids | Retained as source keys. They are not reused as target primary keys |
| Discontinued employees | Retained. `last_working_date` is the source leaving date when present. Historical attendance and payroll for that person still load |
| Historical employees | A person with old payroll or attendance and no current active flag still loads. Absence from the studied month is not a reason to drop the master row when that row is in the extract scope |
| Source-system ids | `source_system` names the legacy database (`legacy_proedge`, `legacy_vtlhrm`, `legacy_atlas`). `source_key` is the legacy employee primary key |
| Punch identity | Not copied from the employee code. A card that happens to equal the code is still a `punch_identities` row |
| Status conflict | Atlas can store `active` = 1 and `disconrinue` = 1 on the same row. That row is an exception until a reviewer accepts a single episode status. The row itself is not dropped |
| Pay on the master | `basic_salary` and similar master amounts are input amounts on `employee_structure_head_values` when the extract scope includes them. They do not recalculate a payroll result |

Studied-month evidence that must survive the employee map:

- Atlas EB9091 has September attendance, `discontinue_date` 2026-08-25, both flags set, and no September salary row. The employee loads. The attendance loads. The missing salary row is not created.
- Atlas September attendance has 60 people and September payroll has 59. Both counts are reconciled. The difference is an explained exception only after EB9091, or whoever the extract shows, is named. It is not closed by inserting a salary row.

Employee codes are not reused inside a company. The target uniqueness is `UNIQUE (company_id, employee_code)`. A duplicate in the source is an explicit exception and is not repaired by reassigning the code. Closed by `docs/UNIVERSAL_HRMS_STEP20_FINAL_EMPLOYEE_CODE_DECISION.md`.

---

## 6. Organization mapping

| Source fact | Proedge | VTL | Atlas | Target | Transformation |
|---|---|---|---|---|---|
| Company | Own database | Own database | Own database | `companies` | Catalog identity. Alias dropped |
| Department | Present | Present | Present | `departments` | Name under the company |
| Designation | Present | Present | Present | `designations` | Name under the company |
| Branch | Not established as a tenant or as a required site in the August trace | Not the tenant. Factory location fields exist | `company_branch_id` on 117 of 151 employees | `branches` | Create rows only for real sites. Extra factory depth, if later extracted, is optional `location_nodes`, not a required parent of department |
| Reporting | Boss and grand-boss columns | Boss and grand-boss columns. Some approval rows have a null boss | `boss` set on 141 employees. `g_father` exists. Approval `boss_id` and `gboss_id` are numeric | `reporting_links` | Chain evidence only. Not approval steps |
| Employee group | Not established as a policy group in the August trace | Not established as the universal group | Salary group is an attribute, not confirmed as `employee_groups` | `employee_groups` only when a real policy group is confirmed | Otherwise archive. Not a silent drop of the source value |

Organization Setting is assigned at Atlas with module `active` = No. Assignment is not proof that a target organization tree must be invented. Departments, designations, and Atlas branches are loaded from master rows, not from the module flag.

---

## 7. Shift mapping

No company’s shift source is the universal source. Cross-midnight stays a target capability on `shift_versions.crosses_midnight`.

| Company | What the source used in the studied month | Other source present |
|---|---|---|
| Proedge | `employees.shift_id`. Attendance `shift_id` equals it on all 1,370 August rows. `att_shift` id 1 is 09:00–17:00. Id 2 is 08:00–17:00 | `emp_shift_calendar` has 0 August rows |
| VTL | `emp_shift_calendar`. September day shift matched the calendar. 0 mismatches against the calendar | `employees.shift_id` differs on 30 rows. Those stay default candidates |
| Atlas | `employees.shift_id` = 1 on every employee. Attendance `shift_id` = 1 on all 1,800 September rows. 0 mismatches | `emp_shift_calendar` has 0 rows. `att_shift` id 2 exists and was not used in September |

Atlas shift id 1 stores 09:30:59–18:00:00 with `next_day` = 0. Shift id 2 stores 09:00:59–15:30:00 with `next_day` = 0. The `:59` start is the stored clock. It is not a grace value and it is not written to `policy_values`.

VTL shift windows can cross midnight. That capability sets `crosses_midnight` from the source next-day flag on the shifts that have it. It is not used to move historical attendance dates. Atlas September and the audited Proedge windows do not cross midnight. Their `crosses_midnight` value follows `next_day` = 0 or the audited same-day window. The target still accepts a cross-midnight version for any company that has one.

| Source | Target | Transformation | Validation | Exception |
|---|---|---|---|---|
| `att_shift` name, start, end, next-day flag | `shifts`, `shift_versions.start_time`, `shift_versions.end_time`, `shift_versions.crosses_midnight` | One shift identity per source shift id. One version for the window the audit established. Effective dates cover the span the source version was in force. Missing history of older windows is not invented | Shift count. September or August attendance `shift_id` resolves | A day whose `shift_id` does not exist |
| Punch window columns | `shift_versions.punch_window_start`, `punch_window_end` | The closed-month audits established start, end, and next-day. They did not establish a separate punch window. Start and end are not copied into the punch window | A real source window column, if the extract finds one, is mapped then | Until a source window is identified, the punch window is an exception. It is not a guessed interval and not a punch-selection policy |
| `employees.shift_id` | `shift_assignment_candidates.candidate_kind` = `default` | The employee’s shift as a candidate. Proedge and Atlas studied months use this candidate on the day. VTL keeps it beside the roster | Default candidate count | Treating this candidate as BD-D03 |
| `emp_shift_calendar` | `shift_assignment_candidates.candidate_kind` = `roster`, `roster_work_date` | Dated rows only. Proedge August and Atlas September emit zero roster rows. That empty result is validated, not filled | Roster count. VTL: calendar match count and the 30 default mismatches listed | Invented roster dates |
| Attendance `shift_id` on the day | Historical citation on the day result’s `shift_version_id` | The shift the legacy row stored. A historical resolution may cite that stored shift with `policy_citation_id` empty | Every day with a shift id cites that shift | A resolution that picks the other candidate when the day stored a different shift. That would decide BD-D03 |

`employment_assignments.fact_kind` = `shift_default` is not used for this legacy field. The only shift-candidate store in this mapping is `shift_assignment_candidates`, so default and roster are not also written as a second assignment fact.

---

## 8. Holiday and weekly-off mapping

`employees.photo` is legacy storage when it holds weekday names. It is not a target column and it is not the weekly-off design.

| Company | Public holiday evidence | Weekly off evidence | Photo |
|---|---|---|---|
| Proedge August 2026 | Holiday status on 88 attendance rows, distinct from Weekly Holiday | Weekly Holiday on 342 rows. `photo` = `Friday,Saturday`. `weekly_holiday` is also populated for some employees | Weekday names in `photo` are legacy-only storage |
| VTL September 2026 | September H days were weekly, not a separate public-holiday set in that month’s token | `weekly_holiday` on the employee, and calendar H. 950 H rows | `photo` was null |
| Atlas September 2026 | No Holiday status. `att_holiday_calendar` has 0 rows in September 2026 | Weekly Holiday on 480 rows = 8 days × 60 employees. Those dates are the four Fridays and four Saturdays | `photo` = `Friday,Saturday` for all 60 September attendees and for 74 employees overall. `weekly_holiday` is null or blank |

| Source | Target | Transformation | Validation | Exception |
|---|---|---|---|---|
| `att_holiday_calendar` date and name, when rows exist | `public_holidays` | Preserve the source date and name under a company holiday calendar | Date count for the extract | A weekly-off day inserted as a public holiday because the attendance token says Holiday or H. The token is attendance evidence. The calendar row is the holiday master |
| Proedge attendance status Holiday | Historical `status_code` on the day result | The word Holiday is kept as a legacy token. A public-holiday master row is created only from calendar evidence, not from the attendance word alone | Token count 88 for August stays on the day results | Using the 88 rows to invent calendar dates that were not in the holiday table |
| VTL status H in September | Historical `status_code` | Letter H kept. September H was weekly. It is not loaded as `public_holidays` for that month | 950 H rows remain H tokens | Reclassifying H as a public holiday |
| Atlas September | No public-holiday load for that month | Zero calendar rows is a real result | Confirm the September extract is empty | A Friday or Saturday inserted as a public holiday |
| `employees.weekly_holiday` when the business confirms it | `weekly_off_rules.weekday_set` | VTL: load the confirmed column as an employee-scope or company-scope rule, matching how the source applied it. The audit did not fix a single scope value, so scope waits on that confirmation | Confirmed employees match | Unconfirmed text |
| `employees.photo` when it is `Friday,Saturday` | `weekly_off_rules` only after confirmation | Proedge and Atlas stored that text. Every Atlas September attendee has it, and the processor may also default the same days. Both explanations fit Atlas September. The mapping does not choose | If confirmed, the weekday set is Friday and Saturday for the employees who have that exact text | Any other photo value. Disagreement with `weekly_holiday`. Loading the text without confirmation |
| Proedge rows where `photo` and `weekly_holiday` disagree | Exception | Neither value becomes the rule until a reviewer picks the source of truth for that employee | Listed pairs | Silent choice of one column |

Weekday rules are historical configuration evidence after confirmation. They are not `policy_values` for a future weekly-off decision beyond the preserved fact, and they do not decide holiday pay.

---

## 9. Punch mapping

Shared source: `legacy_master.all_att_log`. Company source: `emp_code_to_punch_card`. Company `att_log` was empty in the VTL September trace and is not a second punch store for that month. In-repo upload paths were not the writer of the traced closed-month rows.

`balid` and `area` have no assigned meaning. They are not a device id, not a branch, and not `source_event_id`.

| Target | Rule |
|---|---|
| `punch_sources` | One source per company. `source_name` = `legacy punch log, writer unknown`. No vendor name |
| `punch_identities` | `identity_value` = `punch_card_no`. `employee_id` from `emp_code`. Effective interval is the span the map row supports. A card equal to the employee code is still an identity row |
| `punch_events` | One event per source log row that maps to this company. `event_at` is the legacy `date` timestamp, unchanged. `ingested_at` is the migration load time. `source_event_id` stays empty. `source_key` is the legacy `all_att_log.id`. `withdrawal_status` = accepted. `company_id` comes from the identity map |
| Uninterpreted note | `area`, `balid`, `location`, `remark`, and null `company` stay on an uninterpreted archive note attached to that event’s source key. They are not business columns |

| Company | Map evidence | Event evidence |
|---|---|---|
| Proedge | The traced card for employee code 10266 equalled the code. Equality is still a map. Duplicates, if present in the full map, are not merged. The August trace did not publish a duplicate-card count | 2026-08-03, card 10266: two events, 07:50:04 and 17:01:33. `area` text `VSL1(4)`. `company` null |
| VTL | 1,247 map rows. Cards 126384 and 132058 each appear 3 times | 2026-09-02, employee 463, code 03123173, card 126065: five events. The day kept the first and the last. `area` text `Head Office`. `company` null |
| Atlas | 65 cards, 65 codes, 65 distinct card numbers. 8 equal the employee code. 57 differ. No duplicate card number in this table | 2026-09-02, code EB9006, identity 9006: eight events from 09:33:36 through 14:48:24. The day kept the first and the last. `area` text `Atlas`. `company` null. `update_datetime` null. `balid` populated and uninterpreted |

Same-second uniqueness on the legacy log may already have dropped an event. The migration does not recreate it. Atlas 09:33:36 and 09:33:37 are two stored events and both load.

A punch that maps to nobody, or to more than one company, is an exception. It is not assigned by the `area` text. The word Atlas in `area` does not by itself prove company 10. The punch map does.

Middle events stay on `punch_events`. They are not copied onto the attendance day. The day links only the timestamps that match the stored in and out.

`00:00:00` on a VTL day, used where an out punch was missing, is not loaded as a punch event.

---

## 10. Attendance mapping

Source: `attendance_detail`. One surviving legacy row becomes one historical calculation, one day-result version, and the current pointer.

Historical `work_date` is the date in `attendance_detail.date`. It is not recomputed from the shift window. Go-forward `work_date` is a later product rule and is not applied backwards.

`status_code` stores the legacy token, labeled as a legacy token. It is not registered as the company’s future vocabulary.

`incomplete_condition` is filled only from direct missing-punch evidence. The future outcome of that condition is BD-D02 and is not written as a new status.

`late_duration` stores the legacy late number when that number is elapsed time. The label threshold is not copied into grace policy. `early_duration` is filled only when the source stored a duration. A label alone stays a label.

Prior versions destroyed by legacy delete-and-insert are not invented. `generation_kind` for the surviving row is the historical load of that surviving row. It is not labeled as a reconstructed original when no pre-image exists.

| Company | Status tokens in the studied month | Missing punch | Late | OT on the day |
|---|---|---|---|---|
| Proedge August 2026 | Present 717, Weekly Holiday 342, Absent 180, Holiday 88, Leave 43 | Present plus day-status text | Seconds from shift start. The Late label appears after about 16 minutes. The seconds and the label are both preserved. The 16 minutes are not a policy | Seconds after shift end, where `ot_hours` is populated. See section 13 |
| VTL September 2026 | P 5130, H 950, A 899, SL 97, CL 95, EL 34, HP 26 | Missing out stored as A. Empty time `00:00:00` is flagged and is not a midnight punch | From the first minute. Example 117 seconds | Whole hours on 173 rows. See section 13 |
| Atlas September 2026 | Present 955, Weekly Holiday 480, Absent 340, Leave 25. No Holiday row | Present plus text: Out punch missing; Out punch missing, Late; In punch missingEarly Out. The last token has no separator and is stored as that text | Seconds from 09:30:59. 10:01:44 is not labeled Late. 10:04:55 is Late with `late` 2036. The exact label minute is not measured | Blank or zero on all 1,800 rows, and no filled `ot_hours` in the attendance table |

Atlas rows with an in-punch at 22:22 or 22:44 and `late` above 46,000 seconds are stored values. They are exceptions. They are not evidence of a cross-midnight shift, because shift id 1 has `next_day` = 0.

Selected punches:

| Stored day time | Target |
|---|---|
| In or out timestamp matches an `all_att_log` event for that identity | `attendance_selected_punches` with that `punch_event_id`. Role label is the legacy in or out label, not a universal role catalogue |
| Time is `00:00:00` or otherwise not an event | No selected-punch row for that side. `selection_state` records selected only when at least one real event matches. A day with no matching event uses `explicitly_empty` |
| Middle events | Remain events. No selected-punch row |

Shift on the version is the shift id stored on that attendance row, resolved to `shift_version_id`.

Leave letters or words on the day (Leave, EL, CL, SL, PL, P, and VTL leave letters) stay in `status_code` and, when the request is tied, in `leave_day_impacts`. The attendance token is not rewritten to a new leave policy.

---

## 11. Leave mapping

Source: `hrm_leave_emp_app` and the leave type master that those applications reference. Related structures are loaded only when the extract shows they exist. Balances are loaded only when a real balance store exists. The studied months did not establish a leave-balance ledger that payroll read. Absence of that ledger is “not in source.” A zero balance is not created.

| Target | Transformation |
|---|---|
| `leave_types` | Source code and name. Pay effect is not filled. Atlas types evidenced: Casual CL, Sick SL, Annual EL, Paternity PL |
| `leave_requests` | Employee, type, start, end, reason. `request_state` is not set from the legacy number. The number is retained as a source token on the archive note until that company validates it |
| `approval_instances` | A historical instance. Outcome stays unconverted. `acting_user_id` is the unknown marker when the source has a name or code rather than a user id |
| `leave_day_impacts` | Only dates whose attendance result shows that leave was applied. A request day that stayed Present, Absent, or Weekly Holiday does not get an impact row |

Status numbers are not universal:

| Company | What was observed | What is not done |
|---|---|---|
| Proedge | A status 3 sample became Leave on the day. Basic pay in the sample was unchanged | Status 3 is not the product’s approved value |
| VTL | A status 3 sample became a leave letter. Basic pay in the sample was unchanged | Same |
| Atlas September | 27 applications overlap the month. Status 3 = 19. Status 0 = 8. Status 0 overlaps are Absent in the sample. Status 3 often sits on Leave and sometimes on Present, Absent, or Weekly Holiday. Counts: status 3 CL is 6 Leave CL, 4 Present Late, 1 Present Late and Early Out; EL is 13 Leave EL and 2 Weekly Holiday; PL is 2 Leave PL and 1 Present Early Out; SL is 4 Leave SL and 2 Absent | Status 3 and 0 are not converted. Days that are not Leave do not become leave impacts. The day result is not edited to force Leave |

Payroll is not recalculated from these requests. BD-D06 stays open.

---

## 12. Correction and approval mapping

Source: `attendance_approval_file`.

Legacy numeric statuses are source tokens. They are not `correction_state` and not `action_outcome` until that company validates the number. Validation for one company does not validate another.

| Company | Studied-month evidence | Wider evidence |
|---|---|---|
| Proedge August 2026 | 16 rows, all employee 10318, posted by the name maminul. One status 1 and fifteen status 4. The sample days 2026-08-09 and 2026-08-10 stayed Absent. The request did not change the day | Status 1 and 4 are not given a universal meaning |
| VTL September 2026 | 589 rows at status 1 in that alias’s own vocabulary. The audit called that vocabulary HR-approved for non-`vtlnms` usage. That label stays a VTL source reading, not a universal approved code | Displayed punch moved toward the shift boundary. `actual_in` can differ. `actual_out` can be zeroed on an out-miss. Original displayed time was often overwritten |
| Atlas September 2026 | 0 rows | 305 rows from 2025-01-12 through 2026-08-31. Status 1 = 214, status 2 = 89, status 10 = 2. Meanings not assigned. Sampled August rows have null `intime` and `outtime`. `employee_id` and `posted_by` are employee codes. `first_approver_date` can be a string such as `14-09-2026`. The column `second_appover_date` keeps that legacy spelling in the source only |

Stages:

| Stage | Load when | Leave absent when |
|---|---|---|
| `original` | The source still has the pre-approval value | The day was overwritten or the approval row never stored a before-image. The limitation is recorded. A pre-image is not invented |
| `requested` | `intime`, `outtime`, or another requested field is present | Atlas samples with null `intime` and `outtime`. Empty requested times are not filled from the shift clock |
| `approved` | The approval row stored an approved value | A status number alone is not an approved value |
| `final` | The day result changed and the change is tied to this request | Proedge August sample: final is “day unchanged,” not a fabricated punch. Atlas August shift-boundary punches are not final values of the approval, because the approval row does not contain those times and the writer is not proven |

The raw punch is never updated. A shift-boundary time on a day result is not written to `punch_events`.

Actors: a name or an employee code is not `requested_by_user_id` or `acting_user_id`. Those fields use the unknown marker. The name or code stays on the archive note.

`boss_id` and `gboss_id` are not `approval_workflow_steps`. No step list is seeded.

`attendance_corrections.original_result_version_id` cannot be filled with a fabricated pre-image. Where the only surviving day is the post-change row, the correction waits as an exception with disposition “load with limitation” only after a reviewer accepts that the original version is absent. The schema is not changed to make the field optional. The row is not dropped.

A correction that cannot be tied to an employee and a date is an exception.

September Atlas corrections are not invented to fill the empty month.

---

## 13. OT mapping

Three figures stay separate. `attendance_detail.ot_hours` is not paid OT. `employee_overtime` is not calculated OT unless a traced row shows that meaning, which the studied 2026 months do not.

| Figure | Proedge | VTL | Atlas | Target |
|---|---|---|---|---|
| Calculated OT | August `ot_hours` stored as seconds after shift end | 173 September rows store integer hours 1 through 6. Each of those rows also has an attendance approval the same day. A code branch blanks `ot_hours` when the alias is `vtl`. That conflict is unresolved | No filled `ot_hours` on any attendance row in the copy. September is blank or zero | `overtime_calculations` only where the unit is confirmed for that company. Proedge: quantity with `quantity_unit_key` seconds, because the August audit identified the stored unit as seconds. This is historical evidence. It is not BD-D09. VTL: the 173 numbers are preserved and raised as ambiguous until the blanking conflict is resolved. They are not given a formula. Atlas September: no calculated row is created. Blank is “not in source,” not a zero-hour calculation |
| Payable OT | No separate store. `eligible_for_ot` = 1 on 5 of 160, and that flag does not match who was paid | No separate store. `eligible_for_ot` = 0 on all 1,420 while hours exist on 173 days | No September store. `eligible_for_ot` = 1 on 0 employees | `payable_overtime` stays empty for these histories. The flag is not loaded |
| Paid OT | Salary head Over Time, total 6,158.25 across 6 employees. The money is not the attendance seconds | No OT head in September. Paid amount is 0 because no amount exists | No OT head among the 21 September heads | `payroll_result_heads` for the OT head where that head exists. Proedge money loads as money. VTL and Atlas September have no paid-OT row to create |

`employee_overtime`:

| Company | Evidence | Treatment |
|---|---|---|
| Proedge | Newest audited rows 2018. None in 2026 | Not payable OT for August 2026. Empty for the studied year is recorded |
| VTL | 0 rows in the September 2026 company copy | Record the empty count. Do not synthesize rows |
| Atlas | 24,380 rows, years 2013 through 2019 only, with `total_ot_hour` and `total_ot_allowance` on the same legacy table. No 2026 rows. Lines were not traced | Archive evidence that an older allowance existed. Not September payable OT. Not split into calculated versus paid without a later decision. Out of the September scope |

Attendance approvals are not OT approvals. VTL’s 173 hour rows sit beside correction rows. That does not create `payable_overtime.approval_instance_id`.

BD-D08, BD-D09, BD-D10, BD-D11, and BD-D12 stay open. No rate, rounding, eligibility, or formula is derived. `basic/208×2` is not a mapped rate. It was a print path for other company ids, not for company 20’s September payslip.

---

## 14. Payroll mapping

Historical payroll loads as historical amounts. It is not recalculated from attendance, leave, or a new policy.

Source `employee_salary` columns evidenced: employee, month, year, head, amount, `company_id`, type. The table has no attendance snapshot. Payslip history for these companies reads those stored amounts. No payslip PDF was rendered for Atlas. The payslip conclusion is the report screen plus `employee_salary`.

| Target | Transformation |
|---|---|
| `payroll_periods` | One period per source year and month. Label and bounds are that calendar month. The studied months are 2026-08 for Proedge and 2026-09 for VTL and Atlas. Other months in scope follow the same rule at extract |
| `payroll_runs` | One historical run. `attendance_usage` = `declares_no_attendance`. `run_state` = finalized because the row is a past stored result. That state is not a close policy and does not write `payroll_period_events` |
| `payroll_run_declared_inputs` | No attendance keys |
| `payroll_results` | One row per employee per period. `finalization_state` = finalized. `is_current` set. `finalized_by_user_id` is the unknown marker. `net_pay` is the source net when a net column exists. These audits did not establish a net column, so net is the sum of the stored head amounts, recorded as a derived check, not as a new pay rule |
| `payroll_result_heads` | One row per `employee_salary` row. Amount unchanged. Earning or deduction from the source head kind |
| `payroll_result_inputs` | No attendance inputs. The absence is explicit |
| `payroll_result_optional_facts` | Not invented. BD-D18 stays open |
| `employee_structure_assignment_id` | The structure assignment that holds the master amounts for that employee. It identifies the structure. It does not cause the historical result to be recomputed from those master amounts |

Head lists stay per company. They are not unified.

| Company | Heads evidenced in the studied month | Amount facts that stay amounts |
|---|---|---|
| Proedge August 2026 | Includes Over Time. No Absenteeism head in the sample. 540 rows, 45 employees | Over Time 6,158.25 is paid OT. Basic did not move with absence in the sample. That pair is a stored fact |
| VTL September 2026 | 27 heads, 228 employees, 6,156 rows. Includes Absenteeism. No OT head | Absenteeism sometimes resembles gross/30 times absent days and often does not. The stored amount loads. The resemblance is not a formula |
| Atlas September 2026 | 21 heads, 59 employees, 1,239 rows. Earnings include Basic, Special Allowance, House Rent, Medical, Adhoc Allowance (Provident Fund E), Education, Conveyance, and several zero heads. Deductions: Income Tax, PF, Advance Adjustment, Others Deduction | PF 181919.71 equals the Adhoc Allowance total. The match is recorded and is not a formula. EB9050 basic 71527.15 equals the master basic despite 10 absences. That pair is stored. It does not decide BD-D07 |

`salary_month_lock` and `attendance_month_lock` are not period events.

| Company | Lock evidence | Load rule |
|---|---|---|
| Proedge | Both tables 0 rows | Record the zero. Do not mark August closed |
| VTL | Attendance last Yes 2020-09. Salary last Yes 2023-06. September 2026 was not locked | A Yes outside the studied month is baseline evidence. It is not cutoff or close |
| Atlas | No salary lock for 2026. One attendance lock: 2025-01 Yes, `company_id` 26. Not September 2026 | Do not mark September closed. Do not read company_id 26 on the lock as Grameen |

Client print layouts are not loaded as payroll amounts. The payslip in the target reads `payroll_results`.

---

## 15. Client extensions

These stay out of Universal HRMS Core. Default enablement is off. Another company’s dry run does not enable them.

| Company | Extension | Evidence | Mapping |
|---|---|---|---|
| Atlas | Atlas Bills, module id 16 | Assigned, admin view, not ESS | `extension_catalog` plus `company_extension_enablements` for company 10 only, if the business still requires the module. Data inside the module is not mapped until a later extension scope |
| Atlas | Bank Advise, Bank Forwarding, Atlas JV | Extra buttons on the company 10 payroll report screen | Extension outputs. They read payroll results. They are not a second net-pay store |
| Atlas | Introductory, increment, and promotion letters | Separate Atlas letter views | Extension documents. Not core employee history unless a later scope extracts letter rows. No letter content is copied into core tables by this workbook |
| VTL | Perquisite payslip layouts | Present for company 20 | Extension print. Not the snapshot |
| Other Viyellatex company ids | `vtlpdfpayslip` / NMS layouts | Company ids 42, 47, 46, 76, 77, 78, 38. Not company 20 | Out of scope. `nms_data` has 0 rows in the company 20 copy. Not loaded as attendance or payroll |
| Proedge | None required for the August core month | Generic payslip reads `employee_salary`. No company-id 24 payslip branch | No enablement row |

PF amounts at Atlas are payroll head rows, not the Atlas Bills extension. The PF Management module being assigned is noted. It is not reclassified as core policy and it is not given a formula.

Mobile location punch and any unnamed device protocol stay extensions. They are not the punch source of the studied months.

---

## 16. Legacy-only data

| Item | Class | Treatment |
|---|---|---|
| `db_alias` | LEGACY ONLY | Not loaded. Not a behavior switch |
| `previous_db_id`, `emp_unit`, salary `company_id` when it repeats that old id | LEGACY ONLY | Not a target column. Used only to avoid mapping 5 or 26 onto the wrong catalog company. The target tenant is 24, 20, or 10 |
| Legacy approval and leave status numbers | UNKNOWN until that company validates them | Archive token. Not a universal state. TRANSFORM only after validation |
| `employees.photo` weekday text | LEGACY ONLY as a column. Ambiguous as a rule | Not a target column. TRANSFORM into `weekly_off_rules` only after confirmation |
| `eligible_for_ot` | LEGACY ONLY | Not loaded. It did not match pay or hours in the audited populations |
| Boss and grand-boss as a workflow | LEGACY ONLY | Not steps. Optional TRANSFORM into `reporting_links` when both people exist |
| `balid` | UNKNOWN | ARCHIVE/REFERENCE. Not `source_event_id` |
| `area` | UNKNOWN | ARCHIVE/REFERENCE. Not company and not branch |
| Punch writer | UNKNOWN | Source name states writer unknown |
| `all_att_log.company` null | UNKNOWN as a tenant signal | Not used. Company comes from the punch map |
| Month-lock Yes / empty lock tables | LEGACY ONLY as proof of close | Not loaded as cutoff or close. ARCHIVE/REFERENCE as baseline evidence |
| `salary_rule` and `salary_rule_condition` | UNKNOWN as a rule | Not loaded as policy. ARCHIVE/REFERENCE |
| `00:00:00` empty punch | LEGACY ONLY sentinel | Not a punch timestamp |
| Min/max reduction on the day | LEGACY ONLY processing | Not repeated. Events stay events. The day keeps the stored in and out |
| Delete-and-insert of attendance | LEGACY ONLY | Prior versions are not reconstructed |
| `second_appover_date` spelling | LEGACY ONLY column name | The source date string, when present, is an archive timestamp. The misspelling is not a target column |
| Same-second uniqueness that already dropped events | ARCHIVE/REFERENCE | Not invented back |
| Atlas `employee_overtime` 2013–2019 combined hour and allowance | ARCHIVE/REFERENCE | Not split. Not September OT |
| Proedge `employee_overtime` through 2018, empty for 2026 | ARCHIVE/REFERENCE | Not August payable OT |
| VTL empty `employee_overtime` and empty `nms_data` | ARCHIVE/REFERENCE | Empty counts recorded |
| Cost center, band, salary group | ARCHIVE/REFERENCE | Not `employee_groups` and not a new core column |
| Catalog company 26 and catalog company 5 | MIGRATE only if those companies are later in scope | Not part of Atlas, Proedge, or VTL loads |

Direct migration, already covered above, remains: company identity, employee codes, names, departments, designations, Atlas branch ids, head names, punch timestamps, and salary amounts.

---

## 17. Field-level mapping

Business-critical fields only. Columns with no audited meaning are omitted on purpose.

### Company and employee

| Company | Source Table | Source Field | Source Meaning | Target Entity | Target Field | Transformation | Validation | Exception |
|---|---|---|---|---|---|---|---|---|
| All three | `companies` | `id` | Catalog tenant 24, 20, or 10 | `companies` | `source_key` | Preserve the catalog id. Target `id` is a new surrogate | One company each | Using salary `company_id` as this key |
| All three | `companies` | `company_name` | Legal name in the catalog | `companies` | `company_name` | Copy | Name match | Empty name |
| All three | `companies` | `db_alias` | Legacy code switch | None | None | Do not load | Absent from target | Any target column |
| All three | `companies` | `previous_db_id` | Old key. 5, 5, and 26 | None | None | Do not load | Salary rows with that number still land on tenant 24, 20, or 10 | Mapping 26 to Grameen or 5 to catalog company 5 |
| All three | `employees` | employee code | Business code | `employees` | `employee_code` | Copy text. Not the primary key | Active uniqueness | Duplicate active code |
| All three | `employees` | legacy primary key | Source identity | `employees` | `source_key` | Copy. `source_system` is the local database name | Key present | Missing key |
| All three | `employees` | name | Display name | `employees` | `display_name` | Copy | Non-empty | Empty name |
| All three | `employees` | joining date | Start of the known span | `employment_episodes` | `joining_date` | Copy the date. One episode for that span | Date present when source has it | Missing date |
| All three | `employees` | discontinue / leaving date | Last working date when stored | `employment_episodes` | `last_working_date` | Copy. Leave empty when the source is empty | Match | Attendance after this date is listed, not deleted |
| Atlas | `employees` | `active`, `disconrinue` | Two flags. Both can be 1 | `employment_episodes` | `episode_status` | Set only when one meaning is clear | 133 active, 92 discontinued, 59 active and not discontinued are source counts to reconcile | Both true, including EB9091 |
| Proedge, VTL | `employees` | active and discontinue flags | Current status pair. Exact conflict counts were not published | `employment_episodes` | `episode_status` | Same unambiguous rule | Counts taken at extract | Both true |
| All three | `employees` | `emp_unit` | Legacy unit key, not tenant | None | None | Do not load | Atlas all 151 are 26 | Using it as `company_id` |
| Atlas | `employees` | `company_branch_id` | Branch id on 117 employees | `branches` and `employment_assignments` | branch `source_key`, assignment `fact_reference_id` | Resolve the branch. Assignment fact kind location | 117 resolve or are excepted | Unknown branch id |
| All three | `employees` | department and designation ids | Current org | `departments`, `designations`, `employment_assignments` | `name`, `fact_reference_id` | One current interval | References resolve | Missing master |
| All three | `employees` | `boss`, grand-boss / `g_father` | Reporting columns, not a workflow | `reporting_links` | `manager_employee_id`, `sequence_no` | Load when the manager employee exists. Sequence starts at 1 | Atlas boss set on 141 | Unknown manager. Approval boss columns |
| All three | `employees` | `shift_id` | Default shift candidate | `shift_assignment_candidates` | `candidate_kind`, `shift_version_id` | Kind `default` | Proedge and Atlas month: day shift matches this id | Using it as the winner where VTL’s calendar differs |
| Proedge, Atlas | `employees` | `photo` | `Friday,Saturday` on the audited attendees | `weekly_off_rules` | `weekday_set` | Only after confirmation | Exact text match | Other values. Unconfirmed |
| VTL | `employees` | `photo` | Null in the September audit | None | None | No weekly-off rule from photo | Null count | Inventing Friday, Saturday because another company used it |
| VTL | `employees` | `weekly_holiday` | Weekly off column | `weekly_off_rules` | `weekday_set` | After confirmation | Match | Disagreement with another column |
| Proedge | `employees` | `weekly_holiday` | Also populated for some employees | `weekly_off_rules` | `weekday_set` | Only when it agrees with the confirmed photo text, or a reviewer selects it | Disagreements listed | Silent preference |
| Atlas | `employees` | `weekly_holiday` | Null or blank | None | None | No second rule | Empty | Filling it from photo without confirmation |
| All three | `employees` | `eligible_for_ot` | Legacy switch. Does not match studied pay or hours | None | None | Do not load | Proedge 5 of 160. VTL 0 of 1,420. Atlas 0 | Any eligibility row |
| All three | `employees` | `basic_salary` and other master amounts | Stored input amounts | `employee_structure_head_values` | `amount` | Copy the amount. Do not run it through attendance | Atlas EB9050 basic 71527.15 | Using the master amount to replace a different `employee_salary` amount |

### Shift, holiday, punch

| Company | Source Table | Source Field | Source Meaning | Target Entity | Target Field | Transformation | Validation | Exception |
|---|---|---|---|---|---|---|---|---|
| All three | `att_shift` | name, `start_time`, `end_time` | Shift window | `shifts`, `shift_versions` | `name`, `start_time`, `end_time` | Copy. Atlas id 1 is 09:30:59–18:00:00. Proedge id 1 is 09:00–17:00 and id 2 is 08:00–17:00 | Attendance shift ids resolve | Unknown shift id |
| All three | `att_shift` | `next_day` | Next-calendar-day window when set | `shift_versions` | `crosses_midnight` | True only when the source flag says the window crosses. Atlas September flags are 0 | Flag match | Inferring cross-midnight from a late-night punch |
| All three | `att_shift` | punch window, if a distinct column is not in the audit | Not established | `shift_versions` | `punch_window_start`, `punch_window_end` | Do not copy start and end into these fields | Extract may later find a real column | Guessed window |
| VTL | `emp_shift_calendar` | employee, date, shift | Roster that matched September days | `shift_assignment_candidates` | `candidate_kind` = `roster`, `roster_work_date` | One candidate per source roster row | 0 mismatches versus September attendance. 30 differences from `employees.shift_id` listed | Collapsing the 30 |
| Proedge, Atlas | `emp_shift_calendar` | rows | Empty in the studied scope | None | None | Load zero roster candidates | Proedge August 0. Atlas 0 | Invented rows |
| All three | `att_holiday_calendar` | date, name | Public holiday master when rows exist | `public_holidays` | `holiday_date`, `name` | Copy | Atlas September 0 | Weekly off inserted here |
| All three | `emp_code_to_punch_card` | `emp_code`, `punch_card_no` | Identity map. Card may equal the code | `punch_identities` | `employee_id`, `identity_value` | Separate from employee code | Atlas 65/65/65, 8 equal, 57 differ, no duplicate card. VTL 1,247 rows | VTL cards 126384 and 132058 each three times. Unmapped code |
| All three | `all_att_log` | `id` | Legacy row identity | `punch_events` | `source_key` | Copy | Present | Missing id |
| All three | `all_att_log` | `date` | Punch timestamp | `punch_events` | `event_at` | Unchanged. Not converted to `work_date` | Traced clocks match, including Atlas eight events and VTL five events | Any rewritten clock |
| All three | `all_att_log` | `emp_code` | Punch identity value as stored in the log | `punch_events` | `punch_identity_id` | Resolve through the company map | One company | Maps to none or to two companies |
| All three | `all_att_log` | `company` | Null on traced rows | None | None | Do not use | Null remains uninterpreted | Using null as company 10, 20, or 24 |
| All three | `all_att_log` | `area`, `balid` | Unknown texts. Atlas area text is Atlas. Proedge traced area is VSL1(4). VTL traced area is Head Office | Archive note | None | Retain uninterpreted | Present on the note when the source had them | `source_event_id`, device, or branch |
| All three | `all_att_log` | `update_datetime` | Null on the traced external-writer rows | Archive note | None | Not an event time | Not copied onto `event_at` | Using it to replace `date` |

### Attendance, leave, correction

| Company | Source Table | Source Field | Source Meaning | Target Entity | Target Field | Transformation | Validation | Exception |
|---|---|---|---|---|---|---|---|---|
| All three | `attendance_detail` | `date` | The day the legacy system stored | `attendance_day_result_versions` | `work_date` | Copy the date. Also the calculation and the current pointer | Proedge 1,370. VTL 7,231. Atlas 1,800 | Re-dating from a cross-midnight rule |
| All three | `attendance_detail` | employee | The person | `attendance_day_result_versions` | `employee_id` | Resolve inside the company | 47, 244, and 60 people | Unknown employee |
| All three | `attendance_detail` | `shift_id` | Shift stored on that day | `attendance_day_result_versions` | `shift_version_id` | Cite that shift. Policy citation empty | Proedge 1,370 match employee shift. Atlas 1,800 match. VTL matches calendar | Winner chosen from the other candidate |
| All three | `attendance_detail` | attendance status | Legacy token. Words at Proedge and Atlas. Letters at VTL | `attendance_day_result_versions` | `status_code` | Copy the token unchanged, marked legacy | Token counts in section 10 | Translating P to Present or Present to P |
| Proedge, Atlas | `attendance_detail` | `day_status` | Late, Early Out, missing-punch text, and Atlas leave codes on the day | `attendance_day_result_versions` | `incomplete_condition` when the text says a punch is missing. Label retained | Missing text sets the condition. Status stays the stored word. Atlas glued token `In punch missingEarly Out` is kept as stored text | Condition counts match the missing-punch rows | Cleaning the glued token into a new status. Changing Present to Absent |
| VTL | `attendance_detail` | status A with missing out | Incomplete stored as absent | `attendance_day_result_versions` | `status_code` = A, `incomplete_condition` when the missing out is direct | Preserve A. Flag `00:00:00` | A count 899 includes the source mix. The mix is not re-split by a new rule | Inventing a midnight punch |
| All three | `attendance_detail` | `late` | Elapsed seconds where stored | `attendance_day_result_versions` | `late_duration` | Copy the number. Do not copy the label threshold | Atlas 2036 seconds for 10:04:55. VTL example 117 seconds | Atlas late above 46,000 with 22:xx punches, listed as exceptions. Grace policy |
| Proedge, Atlas | `attendance_detail` | early label in `day_status` | Label. Atlas has no separate early-seconds column for that label | `attendance_day_result_versions` | `early_duration` only if a duration exists | Label preserved as evidence. Duration empty when the source had no number | Early Out count | A duration computed during migration |
| All three | `attendance_detail` | stored in and out | Punches the processor kept | `attendance_selected_punches` | `punch_event_id`, `selection_role` | Link only on timestamp match | Traced first and last match | Middle punches added to the day |
| All three | `attendance_detail` | `ot_hours` | See section 13 | `overtime_calculations` | `calculated_quantity`, `quantity_unit_key` | Proedge seconds when populated. VTL held as ambiguous. Atlas September not loaded | Separate from payroll money | Treating the number as paid OT |
| All three | `hrm_leave_emp_app` | type, dates, reason | Request | `leave_types`, `leave_requests` | code, name, `start_date`, `end_date`, `reason` | Copy. Pay effect empty | Atlas 27 overlapping applications | Missing employee or type |
| All three | `hrm_leave_emp_app` | status number | Source token. Atlas 0 and 3. Other companies have their own numbers | Archive note on the request | None on `request_state` until validated | Do not convert | Status counts | Status 3 forced to approved for every company |
| All three | `attendance_detail` where the day shows leave | status / day token | Applied leave day | `leave_day_impacts` | `work_date` | Create only when the result shows leave | Atlas 25 Leave rows. VTL leave letters. Proedge 43 Leave | Atlas status 3 days that stayed Present, Absent, or Weekly Holiday |
| All three | `attendance_approval_file` | employee, date, reason, legacy id | Correction request | `attendance_corrections` | `employee_id`, `work_date`, `reason`, `source_key` | Load the request. State waits on validated status | Proedge 16. VTL 589 in the studied status. Atlas September 0. Atlas table 305 historical | Untied employee or date. Invented September Atlas rows |
| All three | `attendance_approval_file` | `intime`, `outtime`, actual fields | Requested or stored times when present | `attendance_correction_values` | `value_stage`, `field_key`, `value_text` | Stages only where the value exists | Null Atlas sample times stay null | Shift clock written into a null time. Boundary time written onto `punch_events` |
| All three | `attendance_approval_file` | status | 1, 2, 4, 10 and other numbers by company | Archive note | None | Unconverted | Counts per company | A shared meaning |
| All three | `attendance_approval_file` | `posted_by`, approver names, `employee_id` when it holds a code | Actor text | Archive note | `requested_by_user_id` unknown marker | Not a user id | Text retained | A guessed user |
| All three | `attendance_approval_file` | `boss_id`, `gboss_id` | Numeric columns | None as steps | None | Not a workflow | Not loaded as steps | Seeded manager and HR steps |

### Payroll and OT tables

| Company | Source Table | Source Field | Source Meaning | Target Entity | Target Field | Transformation | Validation | Exception |
|---|---|---|---|---|---|---|---|---|
| All three | `employee_salary` | `salary_year`, `salary_month` | Paid period | `payroll_periods` | `period_start`, `period_end`, `label` | Calendar month of that year and month | Period exists once | Locked flag used as the period bound |
| All three | `employee_salary` | `employee_id` | Person paid | `payroll_results` | `employee_id` | One result per person per period | 45, 228, and 59 | Unknown employee. A result invented for an unpaid person |
| All three | `employee_salary` | `salary_head`, `salary_head_total`, `type` | Stored head amount and kind | `payroll_result_heads` | `salary_head_id`, `amount`, `head_kind` | Copy amount. Map kind from the source label | 540, 6,156, and 1,239 head rows. Atlas head totals in section 14 | Formula applied. OT seconds added into the OT head |
| All three | `employee_salary` | `company_id` | `previous_db_id` | `payroll_results` | `company_id` | Replace with catalog 24, 20, or 10 | No row left on tenant 5 or 26 | Grameen or catalog company 5 |
| Proedge | `employee_salary` | Over Time head | Paid OT money | `payroll_result_heads` | `amount` | 6,158.25 total across 6 employees, as stored per row | Money reconciled apart from seconds | Equality with `ot_hours` |
| VTL | `employee_salary` | Absenteeism head | Stored deduction or earning amount as labeled | `payroll_result_heads` | `amount` | Copy. Not gross/30 | Row amounts | A computed replacement |
| Atlas | `employee_salary` | Income Tax, PF, Advance Adjustment, earnings list | Stored heads | `payroll_result_heads` | `amount` | Copy, including zeros that were stored as rows | 59 employees × 21 heads = 1,239 | Dropping zero rows. PF formula |
| All three | `employee_overtime` | hours and allowance | Separate legacy table. Not the 2026 calculated figure | None for the studied months | None | Proedge and VTL 2026 empty. Atlas 2013–2019 archived | Empty or archive count | Payable rows synthesized |
| All three | `salary_month_lock`, `attendance_month_lock` | `year`, `month`, `month_lock`, `company_id` | Legacy flag | None | None | Not a period event | Zero, stale, or 2025-01 Atlas row recorded | September or August marked closed |
| All three | payslip layout | printed amounts | Reads stored salary | `payroll_results` | the result the payslip will read | No second amount table | Amounts match `employee_salary` | NMS, perquisite, or Atlas bank files loaded as core net pay |

---

## 18. Reconciliation rules

For every required object, on each company separately:

```
SOURCE COUNT = LOADED + APPROVED EXCEPTIONS
```

An exception without a disposition does not count. A disposition of excluded is allowed only when the reviewer records why the row is not required production data. That exclusion is visible.

Shared punches are reconciled twice: events mapped to the company, and events left unmapped. Unmapped events are exceptions until each one is loaded to the right company or approved.

Studied-month baselines already counted. Full-history baselines are counted at extract. “Not in source” is used when the object was counted and is empty. Zero is not used as a substitute for “not counted.”

| Block | Proedge | VTL | Atlas |
|---|---|---|---|
| Employees | Every extracted employee. Active and separated counts from the extract. Duplicate active codes listed. August attendance people 47. August payroll people 45 | Extracted employees, including the 1,420 in the flag count if that is the master scope. September attendance people 244. September payroll people 228 | 151 master. 133 `active` = 1. 92 discontinued. 59 active and not discontinued. September attendance people 60. September payroll people 59. EB9091 accounted for |
| Attendance | 1,370 days. Token counts Present 717, Weekly Holiday 342, Absent 180, Holiday 88, Leave 43. Historical work dates unchanged | 7,231 days. P 5130, H 950, A 899, SL 97, CL 95, EL 34, HP 26 | 1,800 days. Present 955, Weekly Holiday 480, Absent 340, Leave 25. Day-status counts in the audit, including the unseparated missing-in token |
| Raw punches | Every `all_att_log` row mapped by the Proedge punch map, plus unmapped exceptions. Timestamps unchanged. Traced pair 07:50:04 and 17:01:33 | Every row mapped by the VTL map. Traced five events all retained. Duplicate cards excepted, not merged | Every row mapped by the 65 Atlas identities. Traced eight events all retained |
| Leave | Requests in the extract. Day impacts only where the result is Leave. Status numbers unconverted. Balances not in source unless the extract finds a ledger | Same. Status 3 is a source token | 27 overlapping September applications. Impacts only for the 25 Leave days. The other overlapped days remain their attendance tokens and are listed |
| Corrections | 16 August rows. Days unchanged in the sample stay unchanged | Every September request, including the 589 at the audited status. Missing original stages counted as limitations | September 0, and that zero is the source count. Historical 305 rows are in scope only when the extract scope includes them. They are not added to the September count |
| OT | Calculated seconds reconciled apart from Over Time money 6,158.25. Payable not in source | 173 hour rows reconciled as ambiguous evidence, apart from paid OT 0. Payable not in source | September calculated not in source. September paid not in source. 2013–2019 archive count 24,380 if that archive is in scope, otherwise recorded as out of scope |
| Payroll employees | 45 | 228 | 59 |
| Payroll heads | Every August head row, 540 | 27 heads, 6,156 rows | 21 heads, 1,239 rows |
| Payroll amounts | Earnings, deductions, and the derived net check, in source currency units. OT head separate | Same. Absenteeism amount, not a recomputed amount | Same. Tax, PF, and advance totals as stored. PF equals Adhoc Allowance as a recorded match |

Company 24, company 20, and company 10 do not share one sign-off.

---

## 19. Historical data rules

| Fact | Rule |
|---|---|
| Attendance status | The legacy token remains identifiable on `status_code`. Words are not converted to letters or the reverse |
| Attendance date | `work_date` is the stored attendance date |
| Payroll amount | The `employee_salary` amount remains the result amount. Attendance is not applied again |
| Punch timestamp | `event_at` remains the log timestamp, including times after midnight and the Atlas 22:xx punches |
| Unknown meaning | `balid`, `area`, unvalidated status numbers, and the punch writer remain unknown |
| Shift winner | Historical day cites the shift stored on that day. Candidates from the other source stay candidates |
| Correction pre-image | If the source no longer has it, it stays absent |
| Leave pay and absence | Sample rows that left basic unchanged stay unchanged. They do not become a rule that edits other rows |
| OT | Seconds, whole hours, and money stay in their own places and are allowed to differ |
| Locks | A studied month is not marked closed |
| Policy | `policy_values` are not filled from these histories |

New calculations after cutover use the target shift model, including cross-midnight, and whatever D01–D18 values a company later sets. Those calculations do not rewrite loaded rows.

---

## 20. Exception model

No required row is dropped in silence. The register is the only waiting place.

Categories:

| Category | Examples from these three companies |
|---|---|
| Duplicate | Active employee code reused inside one company. VTL punch cards 126384 and 132058 |
| Missing reference | Punch with no map. Branch id with no branch. Salary row with no employee. Shift id with no `att_shift` |
| Invalid value | Impossible dates. A leaving date that the reviewer rejects as corrupt. A timestamp that is not a real clock |
| Ambiguous meaning | Status numbers. VTL `ot_hours` versus the blanking branch. Atlas late rows at 22:22 and 22:44. PF total equal to Adhoc Allowance. Photo text before confirmation |
| Unmappable field | `balid`, `area`, `db_alias`, `eligible_for_ot`, `salary_rule` as a formula |
| Historical reconstruction impossible | Destroyed attendance pre-images. Overwritten correction originals. Same-second events the legacy unique key never stored. August Atlas shift-boundary punches with no writer and no approval times |
| Company mapping issue | Salary `company_id` 5 or 26. `emp_unit` used as a tenant. `area` text used as a company |
| Employee mapping issue | `active` and discontinued both true. Attendance after `discontinue_date`. Code reuse after separation |
| Business decision required | Any attempt to fill D01–D18, including grace, incomplete-punch outcome, shift winner, leave pay, absence, OT rules, cutoff, close, correction fields, approval steps, and optional snapshot lines |

Register fields are the ones already specified: company, source reference, reason, severity, resolution, resolver, resolution date, and final disposition. Dispositions are load with limitation, map to an existing target row, or exclude as not required data.

A blocking exception keeps the company out of MIGRATION APPROVED. A limitation can be approved only when the reviewer accepts the preserved source value and the recorded gap.

---

## 21. First dry-run recommendation

The first dry-run candidate is **Proedge, company 24**, on the local copy `legacy_proedge`, studied month August 2026.

This is a rehearsal order. It does not make Proedge the template. A Proedge dry run does not validate VTL or Atlas. The dry run is not executed by this workbook. The local copy is not the production baseline.

| Criterion | Proedge 24 | Atlas 10 | VTL 20 |
|---|---|---|---|
| Studied-month size | 1,370 attendance rows, 47 people, 540 salary rows, 45 people | 1,800 attendance rows, 60 people, 1,239 salary rows, 59 people | 7,231 attendance rows, 244 people, 6,156 salary rows, 228 people |
| Attendance complexity | Employee shift, empty roster, word tokens, missing punch as text | Same office pattern, own clock 09:30:59–18:00:00, unmeasured late-label gap, 22:xx late exceptions | Roster and default both live, 30 disagreements, letter tokens, missing out stored as A, cross-midnight capability |
| Payroll complexity | Head rows without an absence formula to defend. One OT head whose money is already known to differ from seconds | 21 heads, tax, PF, advance. A recorded PF match that must not become a formula. Salary key 26 | 27 heads, Absenteeism amounts that do not share one formula |
| Correction and leave | 16 requests. Sample days stayed unchanged | September corrections 0. August writer unproven. Status 3 does not mark every leave day | Hundreds of requests. Displayed times overwritten. Originals often gone |
| Punch map | Traced card matched the code. Full duplicate count still to be taken at extract | 65 identities, no duplicate card | 1,247 rows, duplicate cards |
| Audit knowledge | August 2026 trace complete | September 2026 trace complete | September 2026 trace complete |
| Local data | `legacy_proedge` and `legacy_master` | `legacy_atlas` and `legacy_master` | `legacy_vtlhrm` and `legacy_master` |

Proedge is first because the studied month is the smallest, the shift source in that month is a single candidate, the sampled corrections do not require a reconstructed pre-image, and both a calculated OT quantity and a paid OT amount exist so the three-way split can be checked on real rows. The remaining Proedge exceptions are still real: unvalidated status numbers, photo versus `weekly_holiday`, unknown punch writer, `area` and `balid`, salary `company_id` 5, and no production snapshot. Those exceptions block approval. They are smaller than VTL’s overwrite, duplicate-card, and formula conflicts, and they avoid Atlas’s tenant-key collision with a different live catalog company.

Atlas is the second rehearsal when a second company is authorized. It repeats the office attendance shape and adds the tenant-key test, the leave-day mismatch, and the extension boundary. VTL is the third because it is the largest and it is the roster, correction-overwrite, and letter-status case. That order tests the common transform before the factory transform. It does not rank the companies as standards.

Before that dry run starts, the punch-window exception in section 7 is either filled from a real source column or approved as a limitation. The run still writes no production data and no legacy data.

---

## 22. D01–D18 protection

This mapping does not decide D01–D18. A historical value is preserved as history. It is not copied into `policy_values`.

| ID | What the three histories contain | What stays open |
|---|---|---|
| BD-D01 | Three different late labels. Seconds still start at shift start where seconds were stored | Grace length |
| BD-D02 | Present plus text at Proedge and Atlas. A at VTL | Incomplete-punch outcome |
| BD-D03 | Employee shift at Proedge and Atlas. Calendar at VTL | Winner |
| BD-D04 | Stored late seconds | When measurement starts |
| BD-D05 | Early labels. No pay change traced from them | Early-out pay |
| BD-D06 | Leave marks differ. Basic unchanged in the samples | Leave pay |
| BD-D07 | No clean absence formula. Atlas basic matched the master despite 10 absences. VTL Absenteeism varies | Absence formula |
| BD-D08 | `eligible_for_ot` does not match the months | Eligibility |
| BD-D09 | Seconds, disputed whole hours, or no quantity | Formula |
| BD-D10 | No confirmed rounding in the loaded quantities | Rounding |
| BD-D11 | No separate OT approval type | Whether OT approval exists |
| BD-D12 | Paid money only where an OT head exists | Rate and payment |
| BD-D13 | Studied months are not attendance-locked | Cutoff |
| BD-D14 | Studied months are not payroll-locked | Close |
| BD-D15 | No reopen evidence | Deferred |
| BD-D16 | Originals and requested times are partial | Which fields a correction may change |
| BD-D17 | Boss columns exist | Approval steps |
| BD-D18 | Salary rows have no attendance copy | Optional snapshot lines |

BD-D15 remains deferred. No reopen role is migrated.

---

## 23. Readiness and next step

| Item | Assessment |
|---|---|
| Proedge, VTL, and Atlas represented | YES. Companies 24, 20, and 10 |
| Any company used as the universal template | NO |
| Source to target mapping explicit | YES. Sections 4 through 17 |
| Transformations explicit | YES |
| Unknown meanings left unknown | YES. `balid`, `area`, writer, unvalidated status numbers |
| Historical facts preserved | YES |
| Raw punch timestamps unchanged | YES |
| Historical `work_date` | The stored attendance date |
| Historical payroll recalculated | NO |
| Client extensions kept out of core | YES. Section 15 |
| Continuity equation | SOURCE COUNT = LOADED + APPROVED EXCEPTIONS |
| Unexplained missing required records | Not allowed |
| Schema changed | NO. No schema gap |
| Migration code written | NO |
| Database modified | NO |
| Data loaded | NO |
| D01–D18 | OPEN. BD-D15 DEFERRED |
| Any company MIGRATION APPROVED | NO |
| First dry run | Recommended for Proedge company 24. Not executed |

The next step, when authorized, is a read-only dry-run rehearsal of company 24 against `legacy_proedge` and the shared punch log, using this mapping version. That rehearsal still needs its own exception register. It does not approve Proedge, and it does not start VTL or Atlas.

### Verification

| Check | Result |
|---|---|
| Proedge, VTL, and Atlas are represented | Yes. Sections 3 through 17 |
| No company is the universal template | Yes. Sections 1 and 21 |
| Source to target mapping is explicit | Yes. Section 17 |
| Transformations are explicit | Yes |
| Unknown meanings remain unknown | Yes. Sections 9 and 16 |
| Historical facts are preserved | Yes. Section 19 |
| Raw punch timestamps remain unchanged | Yes. Sections 9 and 19 |
| `work_date` follows the stored legacy attendance date | Yes. Sections 10 and 19 |
| Payroll history is not recalculated | Yes. Section 14 |
| Client extensions remain separate | Yes. Section 15 |
| SOURCE COUNT = LOADED + APPROVED EXCEPTIONS | Yes. Section 18 |
| Unexplained missing records are not allowed | Yes. Section 18 |
| Migration code written | No |
| Database modified | No |
| D01–D18 remain open | Yes. Section 22 |

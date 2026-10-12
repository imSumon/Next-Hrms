# Universal HRMS Architecture Assessment

Status: architecture assessment only. No implementation, schema, API, or UI is specified.

Evidence: `legacy-reference/hrms/docs/UNIVERSAL_HRMS_REQUIREMENTS.md` (166 requirements), the closed-month audits of Proedge August 2026 and Viyellatex September 2026, and the legacy Yii source only where it explains current structure.

Proedge behavior is evidence, not the target. Viyellatex behavior is evidence, not the target.

```
UNIVERSAL HRMS CORE
  + COMPANY-CONFIGURABLE POLICIES
  + OPTIONAL CLIENT EXTENSIONS
```

Classifications used throughout: CORE, CONFIGURABLE, CLIENT EXTENSION, LEGACY ONLY, BUSINESS DECISION, UNKNOWN.

---

## 1. Executive Summary

The legacy HRMS is one Yii codebase that behaves like several products. A database alias (`proedge`, `vtl`, `vtlnms`, and others) selects the attendance engine, the status vocabulary, the shift source, the overtime writer, and the payslip. Companies also live in separate databases, while raw punches for the traced months sit in one shared table, `legacy_master.all_att_log`, with no company key.

The requirements specification already separates capability from policy. This assessment judges whether that split is architecturally sound, which legacy structures must not be carried forward, and which choices are still business decisions rather than technical decisions.

The architectural direction that is confirmed:

- One product, with company policy as data.
- Punch events, day results, corrections, calculated overtime, payable overtime, and finalized pay are separate.
- One effective shift assignment shall exist for each employee/work-date after roster/default resolution. Which source wins is configuration. `work_date` is that business date. It must not simply equal the punch calendar date. A shift may cross midnight.
- Incomplete attendance is detectable. What it becomes is configuration.
- Approval is an ordered workflow. Boss and grand boss are not fixed steps.
- The payslip reads a finalized snapshot. Recalculating attendance does not change that snapshot.
- Authorization is enforced on the server.

Not confirmed, and not decided here: grace minutes, shift-winner value, leave pay, absence formula, overtime rate, and whether a company turns period close on. The vendor that fills `all_att_log` is UNKNOWN. Physical tables, services, and frameworks are not chosen.

## 2. Assessment Objective and Scope

This document answers: what architectural shape can satisfy the universal requirements without copying either client.

In scope: boundaries, responsibilities, configuration levels, the eighteen legacy architectural problems, an architecture decision register, open business decisions, and evidence gaps.

Out of scope: database tables, migrations, APIs, screens, framework choice, deployment, and resolving D01–D18.

## 3. Evidence Base

| Source | What it establishes | What it does not establish |
|---|---|---|
| Requirements specification, 166 IDs | Capabilities, configuration points, exclusions | Policy values |
| Proedge August 2026, company 24, `legacy_proedge` | Word statuses, employee shift, about 16 minutes before a Late label, Present plus missing-punch text, OT seconds, a paid OT head that does not equal those seconds, payslip from `employee_salary`, empty lock tables | A universal standard |
| Viyellatex September 2026, company 20, `legacy_vtlhrm` | Letter statuses, shift calendar, late from the first minute, missing out stored as A, correction writing the shift boundary, OT hours only beside corrections, paid OT of 0, payslip from `employee_salary`, locks unused for that month | A universal standard |
| `legacy_master.all_att_log`, about 13.9 million rows | Shared punch log. Traced days match first and last punch. `area` and `balid` are populated. `company` is null. In-repo writers did not produce those rows | Vendor, device, or the meaning of `balid` and `area` |
| Legacy source | Alias branches, two processors, module-only `moduleAccess()`, commented `att_log` reader | That the code path which ran in production is the only writer |

Atlas and CEVA have separate process actions in source. Their operated months were not traced. Their policies are UNKNOWN.

## 4. Current Legacy Architecture (as-is)

The as-is system is a single advanced Yii application.

| Layer | What exists today | Classification |
|---|---|---|
| Tenant | One `companies` row per client. `db_alias` selects a connection. `previous_db_id` is stored on employees as `emp_unit` | LEGACY ONLY as the isolation mechanism |
| Behavior selection | `if ($db == 'vtl')`, `if ($db == 'proedge')`, and company-id checks in views | LEGACY ONLY |
| Employee | Company database `employees`. Some columns are reused (`photo` as weekend for Proedge) | CORE data, LEGACY ONLY storage tricks |
| Shift | `att_shift` plus either `employees.shift_id` (Proedge August, calendar empty) or `emp_shift_calendar` (Viyellatex September) | CONFIGURABLE need, two LEGACY implementations |
| Punches | Shared `all_att_log`. Company `att_log` empty for both studied companies | CORE ingestion, LEGACY ONLY shape |
| Punch map | `emp_code_to_punch_card` in the company database. Primary key only | CORE map, weak integrity |
| Day result | `attendance_detail`, delete-and-insert per employee-day in the processors | CORE result, LEGACY ONLY overwrite |
| Correction | `attendance_approval_file`. Status numbers differ by alias. Approver is not a user id | CORE request, LEGACY ONLY workflow encoding |
| Leave | `hrm_leave_emp_app`. Status 3 can mark the day | CORE leave, LEGACY ONLY status number |
| Overtime | Attendance column, `employee_overtime` (empty in both 2026 months), and a salary head for some Proedge people | Three concepts collapsed or disconnected. LEGACY ONLY |
| Payroll | `employee_salary` amounts only. No attendance snapshot | CORE stored result, missing snapshot |
| Payslip | `singlepayslip` reads `employee_salary` for companies 20 and 24. Other views read `nms_data` | CORE for the studied payslips. NMS is CLIENT EXTENSION |
| Close | Lock tables exist. Proedge tables are empty. Viyellatex last Yes is years before the studied month | Capability unused. LEGACY ONLY as proof of close |
| Security | Session company plus module membership. Action strings hide buttons | LEGACY ONLY |

There is no separate attendance service, no workflow engine, and no policy store. Policy is source code.

## 5. Target Architecture Principles

| ID | Principle | Class |
|---|---|---|
| AP-01 | One product serves many companies | CORE |
| AP-02 | Company policy is configuration, not a code branch | CORE |
| AP-03 | A capability is specified once. A policy value is chosen per company | CORE |
| AP-04 | Records that mean different things stay separate: event, day, correction, calculated OT, payable OT, paid OT, snapshot | CORE |
| AP-05 | A finalized snapshot does not follow later attendance recalculation. Draft payroll may be recalculated. A finalized snapshot changes only through an explicitly authorized reopen or reversal, subject to the open business decision | CORE |
| AP-06 | History keeps original, requested, approved, and final values, with actor and time | CORE |
| AP-07 | The server authorizes the action. The screen does not | CORE |
| AP-08 | An extension is off until a company enables it | CORE |
| AP-09 | Unknown upstream facts are not invented into the core | UNKNOWN stays UNKNOWN |
| AP-10 | No universal numeric default where the clients disagree | BUSINESS DECISION |

## 6. Architectural Boundaries

The target is a set of responsibilities. It is not a service diagram and not a deployment.

| Boundary | Owns | Must not own |
|---|---|---|
| Company and identity | Tenant, users, roles, scopes | Attendance formulas |
| Organization and employee | Structure, lifecycle, effective-dated job facts | Pay calculation |
| Time policy | Shift definitions, weekly off, public holidays, and resolution of one effective shift assignment for each employee/work-date | The punch vendor |
| Ingestion | Accept events, name the source, map punch identity to an employee in one company, detect duplicates, and retain each punch timestamp | Assigning `work_date` or deciding Present or Absent |
| Attendance | Assign `work_date` from the effective shift, build the original day result, and recalculate a new result version after an approved correction | Writing pay, or altering the raw punch |
| Leave | Types, requests, outcomes, and the instruction to mark a day | The deduction formula |
| Correction and approval | Request, steps, actors, original and proposed values | Silent replacement of history |
| Overtime | Calculated, eligibility, optional approval, payable figure | The payslip layout |
| Payroll | Declared inputs, run, snapshot, finalization | Re-reading live punches |
| Period control | Optional cutoff and audited reopen | Pretending an unused flag is a close |
| Reporting and ESS | Read stored results. Submit requests the company enabled | A second calculation |
| Extension | Company-switched behavior outside the core | Becoming the default path |

Cross-boundary rule: attendance may be recalculated according to permitted rules and may publish a day result for a `work_date`. Payroll may consume a declared subset of that result at calculation time and must copy what it used into the snapshot. Draft payroll may be recalculated. A finalized payroll snapshot does not change automatically because attendance was later recalculated. A finalized payroll can change only through an explicitly authorized reopen or reversal, subject to the open business decision.

## 7. Multi-Company and Tenancy

CORE. Every operational fact belongs to one company. Reads and writes check that company.

CONFIGURABLE. Location depth, which reports exist, which extensions are on.

LEGACY ONLY. A physical database per client, `db_alias` as the behavior switch, and `previous_db_id` stuffed into `emp_unit`. Those are today’s isolation tactics. The requirement is the boundary (ORG-CORE-001, ORG-CORE-002, ORG-CORE-003), not “one MySQL schema per alias.”

BUSINESS DECISION. None on tenancy itself. Whether a branch may override a company rule is allowed by ORG-CONF-002 and stays off until a company needs it.

UNKNOWN. How many future companies and what peak punch volume is. NFR-CORE-001 states the quality, not a number.

## 8. Conceptual Domain Ownership

This is ownership, not a schema.

| Concept | Owner boundary | Effective dating | Class |
|---|---|---|---|
| Company, branch, department, designation, group | Organization | As required for structure changes | CORE |
| Employee identity and status | Employee | Status changes are dated | CORE |
| Job facts (department, designation, location, manager) | Employee | Yes | CORE |
| Reporting line | Employee, consumed by approval | Yes | CORE. Length is CONFIGURABLE |
| Shift definition | Time policy | Version when the window changes, including a window that crosses midnight | CORE |
| Shift assignment for a work-date | Time policy | One assignment per employee/work-date after roster/default resolution | CORE |
| Weekly off and public holiday | Time policy | Dated | CORE. Who differs is CONFIGURABLE |
| Punch identity | Ingestion, scoped to a company | Active interval | CORE |
| Punch event | Ingestion | Actual calendar date and time. The timestamp is not rewritten to `work_date` | CORE |
| Day result | Attendance | The `work_date` assigned by the effective shift or roster | CORE |
| Leave type and request | Leave | Request dates | CORE. Pay effect is CONFIGURABLE |
| Correction | Correction | Action time and work date | CORE |
| Approval step outcome | Approval | Action time | CORE. The step list is CONFIGURABLE |
| Calculated OT, payable OT | Overtime | The work date or period | CORE. Formula is CONFIGURABLE |
| Salary structure and heads | Payroll | Effective dates on the structure | CORE. Head list is CONFIGURABLE |
| Payroll snapshot | Payroll | The period | CORE. Optional lines are CONFIGURABLE |

Employee code and punch identity stay different concepts (PUNCH-CORE-008). A card that happens to equal the employee code, as Proedge 10266 did, is still a mapping, not proof they are the same field.

## 9. Attendance Pipeline

Confirmed pipeline for the traced months, and the target split:

```
External source (UNKNOWN writer)
  → punch event, with source name, punch identity, and actual calendar timestamp
  → company mapping
  → work_date assigned by the effective shift or roster
  → one effective shift assignment for that employee/work-date after roster/default resolution
  → holiday and approved leave
  → selected punches (company rule)
  → original day result
```

`work_date` is the business date assigned by the effective shift or roster. `work_date` must not simply equal punch date. A shift may cross midnight. A shift from Oct 9 09:00 to Oct 10 09:00 has `work_date` = Oct 9. Punch timestamps retain their actual calendar date and time. Attendance calculation uses the effective shift and `work_date` context.

One effective shift assignment shall exist for each employee/work-date after roster/default resolution. Which source wins remains BD-D03.

An approved correction follows the flow in section 11. It does not erase the raw punch or the original day result.

LEGACY ONLY pieces of the current pipeline:

- `all_att_log` unique on (`date`, `emp_code`) only, so two events in the same second cannot both exist.
- Processors read `MIN(date)` and `MAX(date)` and drop the middle. Viyellatex card 126065 on 2026-09-02 had five events. The day stored 08:17:41 and 17:34:36.
- Proedge card 10266 on 2026-08-03 stored 07:50:04 and 17:01:33, matching the only two events, with `area` text `VSL1(4)` and a null company.
- Company `att_log` is empty and the reader is commented.
- In-repo CSV upload and mobile `transferlocationdata` write different columns from the rows that were traced. Those paths exist. They did not produce the two closed months.

CORE. Keep events (PUNCH-CORE-004). Calculate a stored day (ATT-CORE-001, ATT-CORE-002).

CONFIGURABLE. Which events count (PUNCH-CONF-003). Status vocabulary (ATT-CONF-001).

UNKNOWN. The external writer. Do not design a vendor adapter yet (EXT-CLIENT-004 is a slot, not a protocol).

## 10. Leave and the Day Result

CORE. A company defines leave types. A request is approved or rejected through the workflow. An approved outcome can mark the day (LEAVE-CORE-001 through LEAVE-CORE-004).

CONFIGURABLE. Which outcome writes the day (LEAVE-CONF-001). Whether the type is paid, unpaid, or partial (LEAVE-CONF-002).

LEGACY ONLY. Status number 3 as the universal “approved” token. Both studied months used it. That is evidence of one legacy convention, not the future vocabulary.

BUSINESS DECISION. BD-D06. Sixteen Viyellatex leave days and five Proedge leave days left basic unchanged. That does not prove every leave type is paid.

Leave does not calculate salary. It publishes a day classification. Payroll decides the money.

## 11. Correction and History

CORE. A correction is not a punch and not the day result (CORR-CORE-001). The architecture keeps four values: original, requested, approved, final (CORR-CORE-002), plus actor, time, and reason (CORR-CORE-003). Updating the day adds a final value. It does not destroy the earlier ones (CORR-CORE-004).

Logical flow:

```
Raw Punch
→ Original Attendance Result
→ Correction Request
→ Approval
→ Approved Correction
→ Attendance Recalculation
→ New/Final Day Result
```

A correction never modifies the original raw punch event. Original values remain available for history and audit. Applying an approved correction may create a new attendance result version. The final attendance result reflects that a correction was applied. Which fields a correction may change remains BD-D16.

CONFIGURABLE. Which fields may change (CORR-CONF-001, BD-D16).

LEGACY ONLY, demonstrated:

- Viyellatex 2026-09-17, employee 03129487: displayed in became the shift start `09:00:59`, while `actual_in` held `14:58:20`.
- Viyellatex 2026-09-13, employee 05000356: displayed out became the shift end, and `actual_out` was `00:00:00`. The original out is gone.
- Proedge August, employee 10318: approval stored 08:00 and 17:00 at status 1 or 4, and the day stayed Absent with null punches. The request and the result were not linked.

Neither client is the pattern to copy. The pattern is retained history plus an explicit apply step.

## 12. Approval

CORE. Ordered steps (APP-CORE-001). A step may be a role, a reporting-line manager, an organizational approver, or a named fallback (APP-CORE-002). The actor is a user (APP-CORE-003). The server checks that this user may take this step (SEC-CORE-006).

CONFIGURABLE. The list, and whether a later role may skip earlier steps (APP-CONF-001, BD-D17).

LEGACY ONLY. Fixed `boss_id` and `gboss_id`. Status 1 meaning HR-approved for `vtl` and a different number meaning the same thing for `vtlnms`. `posted_by` as a name (`Zakirh`, `maminul`). Approver date stored as a string. Null boss on rows that are already fully approved.

Employee → Manager → HR → Payroll is a legal configuration. It is not the architecture.

## 13. Overtime Architecture

Three stores, three meanings (OT-CORE-001, OT-CORE-002, OT-CORE-003):

| Figure | Meaning | May feed payroll |
|---|---|---|
| Calculated OT | What the day formula produced | Only if the company says so |
| Payable OT | What survived eligibility, rounding, limits, and approval if approval is on | Yes, as an input |
| Paid OT | The money on the snapshot | It is the snapshot |

CONFIGURABLE. Eligibility by group (OT-CONF-001), formula by shift with a company default (OT-CONF-002), rounding (OT-CONF-003), whether approval exists (OT-CONF-004), rate and limits (OT-CONF-005).

LEGACY ONLY. `processattendancenew` blanking `ot_hours` for alias `vtl`, while `actionProcessEmpDay` can write whole hours later. Proedge storing seconds and also a salary head that is not those seconds. `eligible_for_ot` = 0 for all 1,420 Viyellatex employees while 173 September rows have hours. `eligible_for_ot` = 0 for two Proedge people who still have an Over Time amount. `employee_overtime` empty for both 2026 months. `basic/208×2` printed and not multiplied.

BUSINESS DECISION. BD-D08 through BD-D12. No rate is chosen.

## 14. Payroll, Snapshot, and Payslip

CORE. A run for a company and a period (PAY-CORE-003). Declared inputs (PAY-CORE-007). A snapshot with employee, period, structure, heads, net, version, actor, and time (PAY-CORE-004). Draft versus finalized (PAY-CORE-005). Payslip reads the finalized snapshot only (PAY-CORE-006).

Attendance recalculation and payroll recalculation are different:

- Attendance may be recalculated according to permitted rules.
- Draft payroll may be recalculated.
- A finalized payroll snapshot does not change automatically because attendance was later recalculated (ATT-CORE-007, DI-CORE-003).
- A finalized payroll can change only through an explicitly authorized reopen or reversal, subject to the future business decision. Who may reopen remains BD-D15. Whether close is enabled remains BD-D13 and BD-D14.

A historical payroll result should be reproducible from the source facts used for that calculation, the effective policy that applied, and the relevant calculation or version information. Section 17 states the same rule for attendance. This assessment does not design the physical structures that retain those facts.

CONFIGURABLE. Payable-day basis, absence formula, late or early deduction, and which optional lines are copied into the snapshot (PAY-CONF-001 through PAY-CONF-004, BD-D07, BD-D18).

LEGACY ONLY. `employee_salary` as amounts with no inputs. Company 20 and company 24 payslips happen to read that table, which supports “payslip reads stored pay,” and does not support “the current columns are the snapshot.” NMS-based payslips for other company ids are an extension, and `nms_data` in `legacy_vtlhrm` has 0 rows.

Absence evidence stays a decision, not an architecture rule. Proedge basic matched the employee master despite 12 and 25 absent days. Viyellatex Absenteeism sometimes resembled gross/30 times A and often did not.

## 15. Period Control

Four controls, not one flag:

| Control | Architectural role | Class |
|---|---|---|
| Attendance cutoff | Stops rebuilding day results for a period | CORE capability, CONFIGURABLE use (PER-CORE-001, PER-CONF-001) |
| Payroll finalization | Marks the snapshot as the payslip source | CORE (PAY-CORE-005) |
| Payroll close | Stops regeneration | CORE capability, CONFIGURABLE use (PER-CORE-002, PER-CONF-002) |
| Reopen | Authorized, audited undo of a close | CORE shape (PER-CORE-003). Who may do it is DEFERRED (PER-CONF-003, BD-D15) |

LEGACY ONLY. `attendance_month_lock` and `salary_month_lock` as evidence that a month was closed. Proedge’s tables have 0 rows. Viyellatex’s last Yes values are 2020-09 and 2023-06. September 2026 and August 2026 were processed without a lock.

A company that never enables close is a valid configuration. The product still has the switch.

## 16. Security Architecture

CORE, from SEC-CORE-001 through SEC-CORE-011.

| Concern | Architectural rule |
|---|---|
| Authorization | Every state change checks role and action on the server |
| Tenant | Company id on the principal must match the record |
| Scope | Optional branch, department, or employee scope |
| ESS | Session is one employee in one company |
| Approval | The actor must be allowed for that step of that request |
| Payroll | Separate permission from attendance view |
| Close and reopen | Separate permission |
| UI | Hiding a control is presentation. It is not the control |

LEGACY ONLY. `ApuadminComponent::moduleAccess()` checking that the Yii module is assigned. Hyphenated `view-create-edit-delete` strings that the grid reads and many actions do not. Commented-out verb filters.

## 17. Audit and Retention

CORE. Who, when, old value, new value (AUDIT-CORE-001). Correction history survives the day update (AUDIT-CORE-002). No hard delete of events, day results, corrections, approvals, or finalized pay (DI-CORE-001). Unknown actors migrated from legacy stay marked unknown (MIG-CORE-010).

Historical attendance and payroll results should be reproducible from the source facts used for the calculation, the effective policy and configuration that applied to that employee and `work_date` or period, and the relevant calculation or version information. Original punch events and the original day result remain part of that history when a later result version is produced by an approved correction. This assessment does not design the physical structures that retain those facts.

CONFIGURABLE / BUSINESS DECISION. How long events are kept. The legacy archive-to-`all_att_log_old` then delete is LEGACY ONLY. Retention duration is not chosen.

## 18. Integration Boundary

CORE. An ingestion boundary that can accept more than one named source (PUNCH-CORE-001, PUNCH-CORE-003, NFR-CORE-007). Company attribution happens by punch-identity mapping inside a company (PUNCH-CORE-007), because the traced raw rows have a null company.

Paths that exist in code, classified:

| Path | Class | Used by the two closed months |
|---|---|---|
| External writer into `all_att_log` | UNKNOWN source. The rows are real | Yes |
| CSV upload of `[date]empcode` | LEGACY ONLY import shape. May inform a future controlled import | No. It does not set `area` or `balid` |
| Mobile location then `transferlocationdata` | CLIENT EXTENSION candidate (EXT-CLIENT-003) | No. It would set `update_datetime` |
| Company `att_log` | LEGACY ONLY. Empty and unread | No |
| ISML-specific upload | CLIENT EXTENSION of the old system | Not in these two companies |

`area` values `Head Office` and `VSL1(4)` are PROVEN stored text and UNKNOWN as device identity. `balid` is a PROVEN integer, not the local primary key, and UNKNOWN as a vendor event id.

Do not specify a protocol. EXT-CLIENT-004 remains an empty slot.

## 19. Configuration, Reporting, ESS, and Notifications

Configuration resolution (CFG-CORE-001): product, then company, then a lower level only when that policy allows it.

Configurable policies have effective dates and deterministic precedence. For a given employee/`work_date`, exactly one applicable policy value wins at each required policy level. Overlapping active configurations at the same precedence must be rejected or otherwise deterministically resolved. Behavior must never depend on database or query ordering. This rule does not choose any policy value. D01–D18 stay open.

| Policy | Architectural level |
|---|---|
| Grace, late, early, OT formula | Company default, shift override |
| Incomplete punch, absence, leave pay, payable days, rounding, approval steps, cutoff, snapshot lines | Company |
| Weekly off | Company, then employee group |
| Shift winner | Company |
| OT eligibility and rate | Employee group. Person exception is rare |
| Extensions, reports, ESS actions, notifications | Company switches |

Reporting reads the day result and the snapshot (RPT-CORE-001, RPT-CORE-002). It does not recompute. Client legal-entity layouts are EXT-CLIENT-001.

ESS submits enabled request types as the bound employee (ESS-CORE-001). A cap such as “four per month” is a possible company rule. The legacy cap that September’s volumes did not follow is not architecture.

Notifications are a switchable capability (NOTIF-CORE-001). The Viyellatex missing-punch mail is not the default, and sending it was not verified.

## 20. The Eighteen Legacy Architectural Problems

Each problem is LEGACY ONLY unless a later workshop adopts the underlying need as configuration.

| # | Problem | Evidence | Architectural response |
|---|---|---|---|
| 1 | Alias and company id select behavior | `processattendancenew` versus `processproedge`. View checks on company id 20, 24, 42, and others | AP-02. Policy data. No `if alias` |
| 2 | One codebase, many engines | Letter engine and word engine in `AttendancedetailController` | One attendance responsibility. Vocabulary is ATT-CONF-001 |
| 3 | Two shift authorities | Proedge August: calendar 0 rows, day shift equals `employees.shift_id`. Viyellatex September: day shift equals calendar, 30 rows differ from the employee shift | SHIFT-CORE-002 plus SHIFT-CONF-001. Do not keep both as silent competitors |
| 4 | Weekly off stored in `employees.photo` | Proedge `photo` = Friday,Saturday. Viyellatex `photo` null and `weekly_holiday` set | HOL-CORE-001. A weekend rule, not a photo column |
| 5 | Shared punch log without company or source | `all_att_log.company` null. Area text does not match the HRMS company on the Proedge trace (`VSL1(4)` for a Proedge employee) | PUNCH-CORE-003 and PUNCH-CORE-007 |
| 6 | Same-second events cannot both exist | Unique (`date`, `emp_code`) only | PUNCH-CORE-005 and PUNCH-CONF-002. Identity of an event is an architectural gap until the source is known |
| 7 | Only the earliest and latest punch survive into the day | Five Viyellatex punches reduced to two | PUNCH-CORE-004. Selection is PUNCH-CONF-003 |
| 8 | Incomplete punch has no state | Proedge: Present plus “Out punch missing”. Viyellatex: status A | ATT-CORE-004. Outcome is ATT-CONF-002 |
| 9 | Two different grace behaviors are hardcoded | About 16 minutes before Proedge’s Late label. Viyellatex late at 117 seconds. Proedge still stores seconds from shift start | ATT-CONF-003. No copied number |
| 10 | Correction either overwrites the punch or does not apply, and history is incomplete | Sections 11 above | CORR-CORE-002, CORR-CORE-004 |
| 11 | Approval encoding is client-specific and weakly identified | Status 1 versus status 3. Name strings. Fixed boss and grand boss. HR rows with null boss | APP-CORE-001, APP-CORE-003 |
| 12 | Two overtime writers disagree | Viyellatex blanking branch versus `actionProcessEmpDay` hour writer | One calculation responsibility. OT-CORE-001 |
| 13 | Calculated, payable, and paid overtime are not separate | Hours present and pay zero, or pay present and hours not equal to the amount. Eligibility flag ignored | OT-CORE-001, OT-CORE-002, OT-CORE-003 |
| 14 | Payroll has no input snapshot, and some payslips read a second attendance source | `employee_salary` amounts only. `vtlpdfpayslip` reads `nms_data` for other companies | PAY-CORE-004, PAY-CORE-006. NMS is EXT-CLIENT-002 |
| 15 | Lock tables are treated as period close but are not operated | Empty or stale | Section 15. Close is optional and explicit |
| 16 | Authorization is module membership and hidden buttons | `moduleAccess()`. Action string not rechecked. Bulk “delete” approves because delete is commented out | SEC-CORE-001, SEC-CORE-005, DI-CORE-001 |
| 17 | A code branch and a payslip file per client | `vtlpdf*`, `vslpdf*`, `singlepayslipban*` | EXT-CORE-001. Layouts are extensions. The core payslip is the snapshot |
| 18 | Hard delete and archive-delete of business facts | `all_att_log` archive deletes. Process delete-and-insert of the day. No before image | DI-CORE-001, AUDIT-CORE-002 |

Problems 8 and 9 are attendance rules with architectural consequences: the day result must be able to represent incomplete and late without baking in A, Present, or 16 minutes. Problems 12 and 13 are the overtime split. They are eighteen problems, not eighteen new policies.

## 21. Architecture Decision Register

Decisions here are architectural. They do not pick grace, rates, or leave pay.

| ADR | Decision | Status | Class | Consequence |
|---|---|---|---|---|
| ADR-001 | One product. Client behavior is configuration or an extension, never an alias branch | Accepted for the target | CORE | Legacy `if ($db == …)` is not migrated as code |
| ADR-002 | The company is the tenant. Isolation is a rule on every operation. Physical database-per-client is not required by the requirements | Accepted as a principle. Physical design not started | CORE | Do not copy `db_alias` as the security model |
| ADR-003 | Punch intake is a boundary in front of attendance. The core does not speak a vendor protocol | Accepted | CORE. Protocol UNKNOWN | EXT-CLIENT-004 stays empty |
| ADR-004 | Punch events are retained independently of the day result | Accepted | CORE | Min/max reduction is not the storage rule |
| ADR-005 | One effective shift assignment shall exist for each employee/work-date after roster/default resolution. `work_date` is assigned by that shift or roster and must not simply equal the punch calendar date. A shift may cross midnight. The winner among default and roster is company configuration | Accepted. Winner value open | CORE plus BUSINESS DECISION BD-D03 | Both legacy sources can be migrated. One must be marked effective. Punch timestamps keep their actual calendar date and time |
| ADR-006 | Incomplete punch is a first-class condition. The resulting status and pay effect are company policy | Accepted. Outcome open | CORE plus BUSINESS DECISION BD-D02 | Do not migrate A or “Present plus text” as the only representation |
| ADR-007 | Late and early are measurable durations against a configured threshold | Accepted. Numbers open | CONFIGURABLE. BD-D01, BD-D04, BD-D05 | No 16-minute or zero-grace default in the product |
| ADR-008 | Correction stores original, requested, approved, and final values, with user, time, and reason. An approved correction recalculates attendance into a new result version and never modifies the raw punch | Accepted. Editable fields open | CORE plus BUSINESS DECISION BD-D16 | The final day result reflects that a correction was applied. Original values stay available |
| ADR-009 | Approval is an ordered configurable workflow. The actor is a user | Accepted. Step list open | CORE plus BUSINESS DECISION BD-D17 | Do not create boss and grand-boss columns as the model |
| ADR-010 | Calculated OT, payable OT, and paid OT are separate | Accepted. Formula and rate open | CORE plus BUSINESS DECISION BD-D08–BD-D12 | Payroll reads payable OT only when the company pays OT |
| ADR-011 | Payroll snapshot is the payslip source. Draft payroll may be recalculated. A finalized snapshot does not change automatically when attendance is later recalculated | Accepted. Optional lines open | CORE plus BUSINESS DECISION BD-D18 | A finalized payroll changes only through an explicitly authorized reopen or reversal, subject to BD-D15 |
| ADR-012 | Period close is optional. Reopen is an audited permission and does not exist without a close | Accepted. Use and roles open | CONFIGURABLE. BD-D13, BD-D14. BD-D15 DEFERRED | Do not migrate stale lock rows as “closed” |
| ADR-013 | Server-side authorization on the action, plus company match | Accepted | CORE | Button visibility is not ported as security |
| ADR-014 | Extensions are company switches and default off | Accepted | CORE | NMS, mobile GPS, factory print, and a future device protocol are extensions |
| ADR-015 | No universal numeric policy default where evidence conflicts | Accepted | BUSINESS DECISION | Workshop sets company values later |
| ADR-016 | Application framework, database engine, and table design | Not started | DEFERRED | This assessment does not choose them |
| ADR-017 | Meaning of legacy `balid` and `area`, and the external writer | Not decidable | UNKNOWN | Do not map them to device id |

## 22. Business Decisions Still Open

These block company go-live configuration. They do not block the architectural split above. No value is selected.

| ID | Question | Architectural home | Status |
|---|---|---|---|
| BD-D01 | Grace length | ATT-CONF-003 at shift, company default | BUSINESS DECISION |
| BD-D02 | Incomplete punch outcome and pay effect | ATT-CONF-002 | BUSINESS DECISION |
| BD-D03 | Which shift assignment wins | SHIFT-CONF-001 | BUSINESS DECISION |
| BD-D04 | When late duration starts | ATT-CONF-003 | BUSINESS DECISION |
| BD-D05 | Early-out threshold and pay effect | ATT-CONF-004, PAY-CONF-003 | BUSINESS DECISION |
| BD-D06 | Paid, unpaid, or partial leave by type | LEAVE-CONF-002 | BUSINESS DECISION |
| BD-D07 | Whether absence reduces pay, and the formula | PAY-CONF-002 | BUSINESS DECISION |
| BD-D08 | Who is eligible for payable OT | OT-CONF-001 | BUSINESS DECISION |
| BD-D09 | OT formula | OT-CONF-002 | BUSINESS DECISION |
| BD-D10 | OT rounding | OT-CONF-003 | BUSINESS DECISION |
| BD-D11 | Whether OT approval is separate | OT-CONF-004 | BUSINESS DECISION |
| BD-D12 | Whether OT is paid, the rate, minimum, maximum | OT-CONF-005 | BUSINESS DECISION |
| BD-D13 | Whether attendance cutoff is on | PER-CONF-001 | BUSINESS DECISION |
| BD-D14 | Whether payroll close is on | PER-CONF-002 | BUSINESS DECISION |
| BD-D15 | Who may reopen | PER-CONF-003 | DEFERRED until close is enabled |
| BD-D16 | Which correction fields may change | CORR-CONF-001 | BUSINESS DECISION |
| BD-D17 | Approval steps and bypass | APP-CONF-001 | BUSINESS DECISION |
| BD-D18 | Which optional facts are stored on the snapshot | PAY-CONF-004 | BUSINESS DECISION |

Payable-day basis (PAY-CONF-001) is open with BD-D07. No universal 30-day or calendar default.

## 23. Evidence Gaps and Unknowns

UNKNOWN EVIDENCE, not business decisions and not requirements:

- The process that inserts the bulk of `all_att_log`.
- Whether `balid` is an upstream event id.
- Whether `area` is a site, a terminal group, or free text. `Head Office` and `VSL1(4)` are not the same kind of label.
- Whether `proedgea_attlogvtl` is relevant. It is not in the local audit databases and was not opened. It is not an architectural dependency until someone names it as the writer.
- Atlas and CEVA closed-month behavior.
- A joiner or leaver month, so proration is only a code path today.
- A Proedge correction that changed the stored day.
- Whether the Viyellatex missing-punch email was sent.
- The earlier Architecture Assessment Plan was not in the workspace. This document does not claim to reconcile it.

## 24. Migration Implications

Functional only. No scripts and no column mapping.

| Legacy fact | Architectural treatment |
|---|---|
| Company and org structure | Load as company data. Do not load alias branches as rules |
| Employee | Load identity, status, and effective-dated job facts. Do not reuse `photo` as weekend |
| Punch map | Load mappings. Flag duplicate active cards for a person to resolve (MIG-CORE-003, DI-CORE-005) |
| `all_att_log` | Load as events where retained. Record that middle punches may already have been dropped from day results. Do not invent them |
| `area`, `balid` | Carry as uninterpreted legacy attributes until UNKNOWN items in section 23 are resolved. Do not promote them to device id |
| Day results | Load as historical results, separate from events |
| Leave and corrections | Load outcomes. Where before/after is missing, store unknown. Do not fabricate the original punch |
| `employee_salary` | Load as historical pay amounts. Do not pretend attendance inputs were snapshotted |
| Lock rows | Do not mark a period closed unless the business confirms that Yes meant closed for that month |
| `eligible_for_ot` | Do not load it as the eligibility rule |
| Client payslip files | Do not migrate as code. A company that still needs a layout uses an extension |

## 25. What Must Not Be Designed Yet

- Tables, keys, and document shapes, including the physical store for reproducible calculation facts.
- APIs, events, and queue names.
- A vendor SDK or cloud connector.
- A meaning for `balid` or `area`.
- Grace, late, early, absence, leave-pay, and OT numbers.
- Status code lists copied from either client.
- Boss and grand-boss columns.
- A mandatory period close.
- Framework, language, and hosting.

Design of those items waits on ADR-016 and on the business decisions that affect behavior. The boundaries in section 6 can be discussed without them.

## 26. Architecture Readiness

| Item | Assessment |
|---|---|
| Architectural principles (AP-01–AP-10) | READY |
| Responsibility boundaries | READY |
| Separation of event, day, correction, OT figures, and snapshot | READY |
| Configuration levels | READY |
| Extension rule (off by default) | READY |
| Security rule (server-side, company-scoped) | READY |
| Legacy problems identified and excluded | READY |
| Business policy values D01–D18 | DECISION REQUIRED |
| Upstream punch writer and `balid` / `area` | UNKNOWN |
| Physical architecture, schema, API, UI | NOT STARTED |
| Development | NOT STARTED |

### Readiness statement

The target architecture is ready to guide a later design: one multi-company product, policy as configuration, and the record split in AP-04. It is not ready for schema or build. Policy values are still workshop items. The punch source is still an unknown behind a boundary. Choosing Proedge’s engine or Viyellatex’s engine as that design would recreate problems 1 through 18.

### Quality check

| # | Check | Result |
|---|---|---|
| 1 | Proedge adopted as the architecture | No. Employee shift, 16-minute grace, and Present-plus-text are evidence |
| 2 | Viyellatex adopted as the architecture | No. Calendar-as-only-source, letter codes, and unpaid OT are evidence |
| 3 | Legacy columns required | No. They are named as exclusions or uninterpreted migration attributes |
| 4 | Status numbers made universal | No. Vocabulary is configuration |
| 5 | A client OT formula made universal | No. ADR-010 keeps three figures. Rates stay open |
| 6 | A client pay rule made universal | No. BD-D06 and BD-D07 stay open |
| 7 | Boss and grand boss hardcoded | No. ADR-009 |
| 8 | An open decision silently closed | No. Section 22 lists them open. D15 stays deferred |
| 9 | A capability reduced to “only a business decision” | No. Detection, history, snapshot, and payslip-from-snapshot are accepted architecture |
| 10 | A policy value placed in the core | No. ADR-015 |
| 11 | Correction history required | Yes. ADR-008 |
| 12 | OT figures separated | Yes. ADR-010 |
| 13 | Attendance separated from payroll | Yes. Section 6 cross-boundary rule and ADR-011 |
| 14 | Finalized pay protected from live attendance | Yes. ADR-011 |
| 15 | Server-side authorization | Yes. ADR-013 |
| 16 | UNKNOWN separated from BUSINESS DECISION | Yes. Sections 22 and 23 |
| 17 | `work_date` distinguished from punch timestamp, including a cross-midnight shift | Yes. Sections 8, 9, and ADR-005. Oct 9 09:00–Oct 10 09:00 has `work_date` = Oct 9 |
| 18 | Approved correction recalculates a new day result and leaves the raw punch unchanged | Yes. Section 11 and ADR-008 |
| 19 | Attendance recalculation separated from finalized payroll change | Yes. Sections 6, 14, and ADR-011. Reopen remains BD-D15 |
| 20 | Configuration precedence is deterministic and effective-dated, with no policy value chosen | Yes. Section 19. D01–D18 stay open |
| 21 | Historical results reproducible without a physical design | Yes. Sections 14, 17, and 25 |

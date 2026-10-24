# Universal HRMS Requirements Specification

Status: requirements only. Architecture not started. Development not started.

Evidence sources: Proedge August 2026 (`legacy_proedge`, company 24) and Viyellatex September 2026 (`legacy_vtlhrm`, company 20), plus the legacy functional audits. Those clients are not the product standard.

Product shape:

```
UNIVERSAL HRMS CORE
  + COMPANY-CONFIGURABLE POLICIES
  + OPTIONAL CLIENT EXTENSIONS
```

---

## 1. Executive Summary

The future system is one multi-company HRMS. Each company keeps its own attendance, leave, overtime, and payroll policy as configuration. The product does not hard-code Proedge’s word statuses, grace, or employee shift, and it does not hard-code Viyellatex’s letter statuses, zero grace, or shift calendar.

What is ready to specify is the capability: a company boundary, an effective shift per person per day, punch events kept separate from the day result, incomplete-punch detection, correction history, separate calculated / payable / paid overtime, a payroll snapshot, and a payslip that reads that snapshot. Policy numbers remain workshop decisions D01–D18.

## 2. Product Vision

One platform serves Proedge, Viyellatex, Atlas, CEVA, and later companies. A new company is onboarded by configuration and, only when truly special, by an extension that is off for everyone else. Business behavior is not selected by a database alias.

## 3. Universal HRMS Design Principles

1. A capability is what the product can do. A policy is the value a company chooses.
2. Client evidence justifies a configuration point. It does not justify a universal number.
3. Raw events, day results, corrections, calculated overtime, payable overtime, and pay are different records.
4. Finalized pay does not change because live attendance later changes.
5. Authorization is enforced on the server. Hiding a button is not authorization.
6. History of original, requested, approved, and final values is kept.
7. The lowest configuration level is used only when there is a business reason. Employee-level overrides are exceptions.

### Distinctions that the requirements preserve

| # | Distinction |
|---|---|
| 1 | Raw punch event is not the attendance result |
| 2 | Employee code is not the punch identity |
| 3 | Punch event is not a correction request |
| 4 | Correction request is not the final attendance result |
| 5 | Calculated OT is not payable OT |
| 6 | Payable OT is not paid OT |
| 7 | Attendance result is not the payroll result |
| 8 | Payroll calculation is not the finalized snapshot |
| 9 | Payroll snapshot is not live attendance |
| 10 | Company policy is not the product core |
| 11 | A client extension is not a universal capability |
| 12 | A hidden control is not authorization |

## 4. Scope

In scope: organization, employee, lifecycle, permissions, shift and roster, holidays, punch ingestion, attendance day result, leave, correction, approval, overtime, payroll inputs, calculation, snapshot, payslip, optional period close, reports, ESS, notifications, audit, configuration, extensions, functional migration, security, and integrity.

## 5. Out of Scope

- Laravel, React, Flutter, APIs, database tables, and deployment.
- Choosing grace minutes, OT rates, leave-pay rules, or absence formulas.
- Rebuilding the legacy application.
- Copying legacy column names into the new model.
- A named device vendor, cloud, or the meaning of legacy `balid` and `area`.
- Treating `proedgea_attlogvtl` as a required source. That database was not available and is not a requirement.

## 6. Requirement Classification Model

| Class | Meaning |
|---|---|
| CORE | Required for every company |
| CONFIGURABLE | Shared capability; value or behavior set by configuration |
| CLIENT EXTENSION | Named-client behavior, off by default |
| BUSINESS DECISION | Policy value not yet chosen. Not a silent default |
| DEFERRED | Postponed until a prerequisite exists |

Priority: P0 foundational, P1 important, P2 normal, P3 later.

Configuration levels: PRODUCT, COMPANY, BRANCH, DEPARTMENT, EMPLOYEE GROUP, SHIFT, EMPLOYEE EXCEPTION.

## 7. Multi-Company / Tenant Requirements

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| ORG-CORE-001 | The system shall isolate each company’s employees, attendance, leave, and payroll from every other company. | CORE | P0 | COMPANY | — | Legacy uses separate databases. The requirement is the boundary, not a database-per-company design. |
| ORG-CORE-002 | The system shall enforce the company boundary on every read and write. | CORE | P0 | COMPANY | ORG-CORE-001 | |
| ORG-CORE-003 | The system shall attach business policy to the company as configuration, not to a code alias. | CORE | P0 | COMPANY | CFG-CORE-002 | Replaces `if ($db == 'vtl')` and `if ($db == 'proedge')`. |
| ORG-CORE-004 | The system shall support a branch or site under a company. | CORE | P1 | BRANCH | ORG-CORE-001 | |
| ORG-CORE-005 | The system shall support departments under a company. | CORE | P1 | COMPANY | ORG-CORE-001 | |
| ORG-CORE-006 | The system shall support designations under a company. | CORE | P1 | COMPANY | ORG-CORE-001 | |
| ORG-CORE-007 | The system shall support employee groups used for shared policy. | CORE | P1 | EMPLOYEE GROUP | ORG-CORE-001 | Groups are the preferred place for eligibility and rate, not one-off person rules. |
| ORG-CORE-008 | The system shall support a reporting line of configurable length. | CORE | P1 | COMPANY | APP-CORE-001 | Not a fixed boss and grand boss. |
| ORG-CONF-001 | The company shall be able to choose how deep its location tree is. | CONFIGURABLE | P2 | COMPANY | ORG-CORE-004 | Unit, floor, and line are optional depth, not a universal factory model. |
| ORG-CONF-002 | The company shall be able to allow a branch to override selected company policies. | CONFIGURABLE | P2 | BRANCH | ORG-CORE-004 | Off unless a site truly differs. |

## 8. Organization Structure

Covered by ORG-CORE-004 through ORG-CORE-008 and ORG-CONF-001 through ORG-CONF-002.

The system must represent company, branch, department, designation, employee group, and reporting structure as data. Reporting structure supplies approvers. It does not hard-code two levels.

## 9. Employee Master

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| EMP-CORE-001 | The system shall keep an employee master for each person in a company. | CORE | P0 | COMPANY | ORG-CORE-001 | |
| EMP-CORE-002 | The system shall require every employee to belong to exactly one company. | CORE | P0 | COMPANY | ORG-CORE-001 | |
| EMP-CORE-003 | The system shall support at least active, inactive, and separated status. | CORE | P0 | EMPLOYEE | EMP-CORE-001 | Legacy `disconrinue` is a spelling, not a field name to copy. |
| EMP-CORE-004 | The system shall store employment attributes that affect processing as effective-dated facts. | CORE | P1 | EMPLOYEE | EMP-CORE-001 | Department, designation, group, location, and shift default. |
| EMP-CONF-001 | The company shall be able to enable additional employee attributes. | CONFIGURABLE | P2 | COMPANY | EMP-CORE-001 | Viyellatex `emp_fields` shows that field sets differ. The list is company configuration. |

## 10. Employment Lifecycle

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| LIFE-CORE-001 | The system shall record a joining date and shall not process days before joining. | CORE | P1 | EMPLOYEE | EMP-CORE-001 | |
| LIFE-CORE-002 | The system shall record a transfer of location as an effective-dated change. | CORE | P2 | EMPLOYEE | EMP-CORE-004 | |
| LIFE-CORE-003 | The system shall record a designation change as an effective-dated change. | CORE | P2 | EMPLOYEE | EMP-CORE-004 | |
| LIFE-CORE-004 | The system shall record a department change as an effective-dated change. | CORE | P2 | EMPLOYEE | EMP-CORE-004 | |
| LIFE-CORE-005 | The system shall record a shift change as an effective-dated change. | CORE | P1 | EMPLOYEE | SHIFT-CORE-002 | |
| LIFE-CORE-006 | The system shall support an inactive or suspended state that stops attendance processing and pay according to company policy. | CORE | P1 | EMPLOYEE | EMP-CORE-003 | The pay effect of suspension is D06/D07 territory if money is involved. |
| LIFE-CORE-007 | The system shall record separation, including the last working date, and shall not invent days after that date. | CORE | P1 | EMPLOYEE | EMP-CORE-003 | |
| LIFE-CORE-008 | The system shall support rejoining as a new employment episode without erasing the previous episode. | CORE | P2 | EMPLOYEE | LIFE-CORE-007 | |

Partial-month pay is not specified here. It is PAY-CONF-001 and BD-D07 / payable days.

## 11. User, Role and Permission Requirements

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| SEC-CORE-001 | The system shall authorize every state-changing action on the server. | CORE | P0 | PRODUCT | — | Legacy `moduleAccess()` checks only the module. |
| SEC-CORE-002 | The system shall reject access to another company’s data. | CORE | P0 | COMPANY | ORG-CORE-002 | |
| SEC-CORE-003 | The system shall be able to limit a user to a branch, department, or employee scope. | CORE | P1 | COMPANY | ORG-CORE-004 | |
| SEC-CORE-004 | The system shall grant permissions by role. | CORE | P1 | COMPANY | SEC-CORE-001 | |
| SEC-CORE-005 | The system shall not treat a hidden button as authorization. | CORE | P0 | PRODUCT | SEC-CORE-001 | |
| SEC-CORE-006 | The system shall verify that the actor is allowed to approve the specific request. | CORE | P1 | COMPANY | APP-CORE-001 | |
| SEC-CORE-007 | The system shall bind an ESS session to one employee in one company. | CORE | P1 | EMPLOYEE | ESS-CORE-001 | |
| SEC-CORE-008 | The system shall restrict payroll read and write to authorized payroll roles. | CORE | P1 | COMPANY | PAY-CORE-003 | |
| SEC-CORE-009 | The system shall restrict attendance correction to authorized roles and to the allowed fields. | CORE | P1 | COMPANY | CORR-CONF-001 | |
| SEC-CORE-010 | The system shall restrict period close and reopen to authorized roles. | CORE | P1 | COMPANY | PER-CORE-001 | |
| SEC-CORE-011 | The system shall audit changes to pay, attendance results, corrections, and permissions. | CORE | P1 | PRODUCT | AUDIT-CORE-001 | |

## 12. Shift and Roster Management

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| SHIFT-CORE-001 | The system shall define a shift with a start, an end, and a punch window. | CORE | P1 | COMPANY | — | Grace is not implied by the shift. See D01. |
| SHIFT-CORE-002 | The system shall resolve exactly one effective shift for each employee on each date. | CORE | P0 | EMPLOYEE | SHIFT-CORE-001 | |
| SHIFT-CORE-003 | The system shall support a dated roster assignment. | CORE | P1 | EMPLOYEE | SHIFT-CORE-002 | |
| SHIFT-CORE-004 | The system shall allow a shift to cross midnight. | CORE | P1 | SHIFT | SHIFT-CORE-001 | Both clients have next-day windows. Using them is optional. |
| SHIFT-CONF-001 | The company shall configure which assignment wins when an employee default and a dated roster both exist. | CONFIGURABLE | P0 | COMPANY | SHIFT-CORE-002 | No universal winner. Proedge used the employee shift. Viyellatex used the calendar. See D03. |

## 13. Weekly Off and Public Holiday

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| HOL-CORE-001 | The system shall support a weekly-off rule that marks those dates on the day result. | CORE | P1 | COMPANY | SHIFT-CORE-002 | Not `employees.photo`. |
| HOL-CORE-002 | The system shall support a public-holiday calendar that can mark a date on the day result. | CORE | P1 | COMPANY | SHIFT-CORE-002 | |
| HOL-CONF-001 | The company shall be able to vary weekly off by employee group, and by person only as an exception. | CONFIGURABLE | P2 | EMPLOYEE GROUP | HOL-CORE-001 | |
| HOL-CONF-002 | The company shall be able to limit a public holiday to selected shifts. | CONFIGURABLE | P2 | SHIFT | HOL-CORE-002 | |

## 14. Punch/Event Ingestion

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| PUNCH-CORE-001 | The system shall accept punch events through an ingestion boundary before attendance calculation. | CORE | P0 | PRODUCT | — | Upstream vendor is unknown and is not specified. |
| PUNCH-CORE-002 | The system shall store each accepted event with an event time and a punch identity. | CORE | P0 | PRODUCT | PUNCH-CORE-001 | |
| PUNCH-CORE-003 | The system shall record the named source of each event. | CORE | P1 | COMPANY | PUNCH-CORE-001 | “Named source” is required. The legacy `area` text is not that design. |
| PUNCH-CORE-004 | The system shall keep every accepted event until a processing rule selects which events form the day. | CORE | P0 | PRODUCT | PUNCH-CORE-002 | Min/max-only is not the universal rule. |
| PUNCH-CORE-005 | The system shall detect a duplicate event. | CORE | P1 | PRODUCT | PUNCH-CORE-002 | |
| PUNCH-CORE-006 | The system shall distinguish event time from the time the event was ingested. | CORE | P1 | PRODUCT | PUNCH-CORE-002 | |
| PUNCH-CORE-007 | The system shall associate an ingested event with a company only through an explicit mapping, not through an empty company field. | CORE | P0 | COMPANY | ORG-CORE-001 | Legacy `all_att_log.company` was null on traced rows. |
| PUNCH-CONF-001 | The company shall configure which sources it accepts. | CONFIGURABLE | P1 | COMPANY | PUNCH-CORE-003 | |
| PUNCH-CONF-002 | The company shall configure how a duplicate event is treated. | CONFIGURABLE | P1 | COMPANY | PUNCH-CORE-005 | |
| PUNCH-CONF-003 | The company shall configure which punches in a day are used for the result. | CONFIGURABLE | P1 | COMPANY | PUNCH-CORE-004 | First/last may be a company choice. It is not the core. |

## 15. Punch Identity / Employee Mapping

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| PUNCH-CORE-008 | The system shall map one or more punch identities to an employee inside one company. | CORE | P0 | EMPLOYEE | EMP-CORE-001 | Identity may differ from the employee code. |
| PUNCH-CORE-009 | The system shall reject a punch identity that is active for two employees in the same company at the same time, unless the company explicitly allows a documented overlap. | CORE | P1 | COMPANY | PUNCH-CORE-008 | Viyellatex had duplicate cards. That is a defect to control, not a rule to copy. |
| PUNCH-CONF-004 | The company shall configure whether an employee may hold multiple active punch identities. | CONFIGURABLE | P2 | COMPANY | PUNCH-CORE-008 | |

## 16. Attendance Processing

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| ATT-CORE-001 | The system shall calculate a day result from the effective shift, selected punch events, holidays, approved leave, and approved corrections. | CORE | P0 | COMPANY | SHIFT-CORE-002 | |
| ATT-CORE-002 | The system shall store the day result separately from raw punch events. | CORE | P0 | COMPANY | ATT-CORE-001 | |
| ATT-CORE-003 | The system shall use the stored day result as the source for attendance reports. | CORE | P1 | COMPANY | ATT-CORE-002 | Reports must not invent a second formula. |
| ATT-CORE-007 | The system shall not change a finalized payroll snapshot when attendance is recalculated. | CORE | P0 | COMPANY | PAY-CORE-005 | |

ATT-CORE-004 through ATT-CORE-006 are in sections 19–21.

## 17. Effective Shift Resolution

The system must produce one shift per employee-date (SHIFT-CORE-002). The company chooses the winner when sources compete (SHIFT-CONF-001, decision D03). No default of “employee shift” or “roster” is specified.

## 18. Attendance Day Result

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| ATT-CONF-001 | The company shall define its attendance status vocabulary and the meaning of each status. | CONFIGURABLE | P0 | COMPANY | ATT-CORE-002 | Proedge words and Viyellatex letters are both legacy vocabularies. |
| ATT-CORE-008 | The system shall store, on the day result, the effective shift, the punches selected for the result, the status, and any measured late or early duration. | CORE | P1 | COMPANY | ATT-CORE-002 | |

## 19. Incomplete Punch Handling

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| ATT-CORE-004 | The system shall detect a missing in punch and a missing out punch as incomplete attendance, distinct from a completed presence and from a determined absence. | CORE | P0 | COMPANY | ATT-CORE-001 | |
| ATT-CONF-002 | The company shall configure what an incomplete punch becomes and whether it affects pay. | CONFIGURABLE | P0 | COMPANY | ATT-CORE-004 | See D02. No universal outcome of Absent or Present. |

## 20. Late Arrival

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| ATT-CORE-005 | The system shall be able to mark a day late and to store a late duration. | CORE | P1 | SHIFT | SHIFT-CORE-002 | |
| ATT-CONF-003 | The company shall configure grace, the late threshold, and whether duration is measured from shift start or from the end of grace. | CONFIGURABLE | P1 | SHIFT | ATT-CORE-005 | Company default, shift override. See D01 and D04. No universal minutes. |

## 21. Early Departure

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| ATT-CORE-006 | The system shall be able to mark a day early and to store an early duration. | CORE | P1 | SHIFT | SHIFT-CORE-002 | |
| ATT-CONF-004 | The company shall configure the early-out threshold and whether it affects pay. | CONFIGURABLE | P1 | SHIFT | ATT-CORE-006 | See D05. |

## 22. Leave Management

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| LEAVE-CORE-001 | The system shall support company-defined leave types. | CORE | P1 | COMPANY | — | |
| LEAVE-CORE-002 | The system shall support a leave request for a date range, with a reason. | CORE | P1 | EMPLOYEE | LEAVE-CORE-001 | |
| LEAVE-CORE-003 | The system shall approve or reject leave through the configurable approval workflow. | CORE | P1 | COMPANY | APP-CORE-001 | |
| LEAVE-CORE-004 | The system shall be able to apply an approved leave to the day result. | CORE | P1 | COMPANY | ATT-CORE-002 | |
| LEAVE-CONF-001 | The company shall configure which approval outcome writes the day result. | CONFIGURABLE | P1 | COMPANY | LEAVE-CORE-004 | Legacy status 3 is not the universal code. |
| LEAVE-CONF-002 | The company shall configure, per leave type, whether the leave is paid, unpaid, or partial. | CONFIGURABLE | P1 | COMPANY | LEAVE-CORE-001 | See D06. Both closed months left basic unchanged. That is not proof of a universal paid-leave rule. |
| LEAVE-CORE-005 | The system shall record a proposed leave-balance position for one company, one employee, one leave type, and one leave period. The position shall hold the proposed opening amount, the period start and end or the period label, the source evidence, and the stated calculation or source basis. | CORE | P1 | COMPANY | LEAVE-CORE-001 | The basis is a recorded statement or a reference. This requirement chooses no earning formula, divisor, or leave year. |
| LEAVE-CORE-006 | The system shall record each balance movement and each reversal as its own entry, with the amount, the direction, the related request or adjustment when one exists, the actor, and the time. A reversal shall leave the original movement readable. | CORE | P1 | COMPANY | LEAVE-CORE-005 | No earning formula and no approval role is implied. |
| LEAVE-CORE-007 | The system shall record, on a proposed position, the reconciliation result, the variance, the exception status, and the authorized confirmation. Without that confirmation, the position shall not be available as an approved balance for leave approval. | CORE | P1 | COMPANY | LEAVE-CORE-005 | The states are proposed, excepted, and confirmed. Variance is a recorded difference. An unreconciled variance stays empty. No numerical tolerance is set. |
| LEAVE-CONF-003 | The company shall configure its leave period separately from the payroll month and from the financial year. | CONFIGURABLE | P1 | COMPANY | FY-CONF-001 | A stored legacy label is evidence of what was stored. It is not the signed rule. LEAVE-OPENING stays unsigned. |
| LEAVE-CORE-008 | When an encashment is included in pay, the payroll result shall reference the encashment record, and the paid amount shall remain distinguishable from the leave-ledger day count. | CORE | P1 | COMPANY | LEAVE-CORE-006, PAY-CORE-007 | PROPOSED. Not approved. It chooses no formula, no eligibility rule, and no approval role. F-35 and F-36 are the legacy evidence that those stores are separate. |

## 23. Attendance Correction

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| CORR-CORE-001 | The system shall treat a correction request as a record distinct from the punch event and from the day result. | CORE | P0 | COMPANY | ATT-CORE-002 | |
| CORR-CORE-002 | The system shall store the original value, the requested value, the approved value, and the final calculated value. | CORE | P0 | COMPANY | CORR-CORE-001 | |
| CORR-CORE-003 | The system shall store the actor, the time, and the reason for each correction action. | CORE | P0 | COMPANY | CORR-CORE-001 | Actor is a user identity, not a free-text name. |
| CORR-CORE-004 | The system shall not replace history when an approval changes the day result. | CORE | P0 | COMPANY | CORR-CORE-002 | Shift-boundary substitution is not the requirement. |
| CORR-CONF-001 | The company shall configure which fields a correction may change. | CONFIGURABLE | P1 | COMPANY | CORR-CORE-001 | Punch, status, shift, late, early, calculated OT, payable hours. See D16. |

## 24. Approval Workflow

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| APP-CORE-001 | The system shall run approval as an ordered list of steps. | CORE | P0 | COMPANY | — | |
| APP-CORE-002 | The system shall allow a step to be a role, a reporting-line manager, an organizational approver, or a named fallback. | CORE | P1 | COMPANY | APP-CORE-001 | |
| APP-CORE-003 | The system shall record the acting user on each approval action. | CORE | P0 | COMPANY | AUDIT-CORE-001 | |
| APP-CONF-001 | The company shall configure the steps, including whether a later role may bypass earlier steps. | CONFIGURABLE | P1 | COMPANY | APP-CORE-001 | See D17. Employee → Manager → HR → Payroll is one allowed setup, not the product. |

## 25. Overtime

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| OT-CORE-001 | The system shall be able to calculate overtime from the day result and the effective shift, and shall store that figure as calculated OT. | CORE | P0 | SHIFT | ATT-CORE-002 | |
| OT-CORE-002 | The system shall store payable OT as a figure separate from calculated OT. | CORE | P0 | COMPANY | OT-CORE-001 | |
| OT-CORE-003 | The system shall store paid OT only as an amount on the payroll snapshot, separate from payable OT. | CORE | P0 | COMPANY | PAY-CORE-004 | |
| OT-CONF-001 | The company shall configure who is eligible for payable OT, preferably by employee group. | CONFIGURABLE | P1 | EMPLOYEE GROUP | OT-CORE-002 | See D08. The legacy `eligible_for_ot` flag is not this rule. |
| OT-CONF-002 | The company shall configure the calculation formula. | CONFIGURABLE | P1 | SHIFT | OT-CORE-001 | See D09. No universal “seconds after end” or “whole hours”. |
| OT-CONF-003 | The company shall configure rounding. | CONFIGURABLE | P2 | COMPANY | OT-CORE-001 | See D10. |
| OT-CONF-004 | The company shall configure whether payable OT requires its own approval. | CONFIGURABLE | P1 | COMPANY | APP-CORE-001 | See D11. A punch correction is not an OT approval. |
| OT-CONF-005 | The company shall configure whether OT is paid, the rate, and any minimum or maximum. | CONFIGURABLE | P1 | COMPANY | OT-CORE-002 | Rate may vary by employee group. See D12. `basic/208×2` is not the rate. |

## 26. Payroll Inputs

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| PAY-CORE-001 | The system shall support a salary structure for an employee. | CORE | P1 | EMPLOYEE | EMP-CORE-001 | |
| PAY-CORE-002 | The system shall support company-defined salary heads. | CORE | P1 | COMPANY | PAY-CORE-001 | Head lists differ by client and stay configurable. |
| PAY-CORE-007 | The system shall declare which attendance, leave, and OT inputs a payroll run is allowed to use. | CORE | P0 | COMPANY | ATT-CORE-002 | A run that ignores attendance must say so. A run that uses it must name the inputs. |
| PAY-CONF-001 | The company shall configure the payable-day basis. | CONFIGURABLE | P1 | COMPANY | PAY-CORE-007 | Calendar, fixed count, or attendance-based. No universal 30. |
| PAY-CONF-002 | The company shall configure whether absence reduces pay, and the formula if it does. | CONFIGURABLE | P1 | COMPANY | ATT-CONF-002 | See D07. |
| PAY-CONF-003 | The company shall configure whether late or early time reduces pay. | CONFIGURABLE | P1 | COMPANY | ATT-CONF-003 | See D04 and D05. Both closed months did not deduct. That is not a universal “never deduct” rule. |

## 27. Payroll Calculation

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| PAY-CORE-003 | The system shall execute a payroll run for a company and a period. | CORE | P0 | COMPANY | PAY-CORE-002 | |
| PAY-CORE-008 | The system shall calculate each head from the structure and the declared inputs. | CORE | P1 | COMPANY | PAY-CORE-007 | |

## 28. Payroll Snapshot / Finalization

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| PAY-CORE-004 | The system shall store a snapshot containing employee, period, structure identity, head amounts, net pay, calculation version, actor, and time. | CORE | P0 | COMPANY | PAY-CORE-003 | |
| PAY-CORE-005 | The system shall mark a run as draft or finalized. | CORE | P0 | COMPANY | PAY-CORE-004 | |
| PAY-CONF-004 | The company shall configure which optional facts are stored on the snapshot: payable days, paid and unpaid leave, absence, late or early deductions, calculated OT, approved OT, and payable OT. | CONFIGURABLE | P1 | COMPANY | PAY-CORE-004 | See D18. Include a line only when that policy is on. |

## 29. Payslip

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| PAY-CORE-006 | The system shall produce the payslip from the finalized snapshot only. | CORE | P0 | COMPANY | PAY-CORE-005 | Both company 20 and company 24 already read stored salary. Live attendance and `nms_data` are not the payslip source. |

## 30. Attendance Period Lock

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| PER-CORE-001 | The system shall be able to stop attendance recalculation for a company period. | CORE | P1 | COMPANY | ATT-CORE-001 | The capability is optional to use. |
| PER-CONF-001 | The company shall choose whether an attendance cutoff is enabled and on which date. | CONFIGURABLE | P1 | COMPANY | PER-CORE-001 | See D13. A lock table is not proof a month was closed. |

## 31. Payroll Period Lock

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| PER-CORE-002 | The system shall be able to stop regeneration of a finalized payroll period. | CORE | P1 | COMPANY | PAY-CORE-005 | |
| PER-CONF-002 | The company shall choose whether payroll close is enabled. | CONFIGURABLE | P1 | COMPANY | PER-CORE-002 | See D14. |

## 32. Reopen / Controlled Reversal

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| PER-CORE-003 | The system shall allow reopen only as an authorized action on a period that is closed, and shall audit that action. | CORE | P1 | COMPANY | PER-CORE-002 | |
| PER-CONF-003 | The company shall name the roles allowed to reopen a closed period. | DEFERRED | P2 | COMPANY | PER-CONF-001 | Deferred until that company turns close on. See D15. |

## 32A. Company financial year

Added in Stage 2. This ID is new. D01–D18 are not renumbered. The start and end months are not approved.

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| FY-CONF-001 | The company shall configure its financial-year start month and end month. | CONFIGURABLE | P1 | COMPANY | PER-CORE-002 | Calendar year, company financial year, payroll period, and leave year stay separate. January–December, June–May, and March–February are examples, not stored company settings. The chosen months are unsigned. See decision FY-BOUNDARY. |

## 33. Reports

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| RPT-CORE-001 | The system shall produce attendance reports from the stored day result. | CORE | P1 | COMPANY | ATT-CORE-003 | |
| RPT-CORE-002 | The system shall produce payroll reports and payslips from the snapshot. | CORE | P1 | COMPANY | PAY-CORE-006 | |
| RPT-CONF-001 | The company shall choose which reports are enabled. | CONFIGURABLE | P2 | COMPANY | RPT-CORE-001 | Legal-entity print layouts are extensions, not the core report. |

## 34. ESS / Employee Self Service

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| ESS-CORE-001 | The system shall allow an employee to submit the request types the company has enabled. | CORE | P1 | COMPANY | SEC-CORE-007 | |
| ESS-CONF-001 | The company shall choose which ESS actions are available. | CONFIGURABLE | P2 | COMPANY | ESS-CORE-001 | A monthly cap is a possible policy. The legacy cap of four is not the requirement. |

## 35. Notifications

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| NOTIF-CORE-001 | The system shall be able to notify a configured audience when a configured event occurs. | CORE | P2 | COMPANY | — | |
| NOTIF-CONF-001 | The company shall choose the events and the recipients. | CONFIGURABLE | P3 | COMPANY | NOTIF-CORE-001 | The Viyellatex missing-punch email is not the default. |

## 36. Audit Trail / Change History

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| AUDIT-CORE-001 | The system shall record who changed a sensitive fact, when, the old value, and the new value. | CORE | P0 | PRODUCT | SEC-CORE-001 | |
| AUDIT-CORE-002 | The system shall retain correction and approval history after the day result is updated. | CORE | P0 | COMPANY | CORR-CORE-004 | |

## 37. Configuration Hierarchy

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| CFG-CORE-001 | The system shall resolve policy in this order: product default where one exists, company, then branch, department, employee group, shift, and employee exception only when that lower level is enabled for that policy. | CORE | P0 | PRODUCT | — | Most policies stop at company or shift. |
| CFG-CORE-002 | The system shall store policy as configuration data scoped to a company. | CORE | P0 | COMPANY | ORG-CORE-003 | |

Preferred levels for the major policies:

| Policy | Level |
|---|---|
| Grace, late, early | Company default, shift override |
| Incomplete punch, absence, leave pay, payable days, OT rounding, OT approval, correction fields, approval steps, cutoff | Company |
| Weekly off | Company, then employee group |
| Effective-shift winner | Company |
| OT formula | Company default, then shift |
| OT eligibility and rate | Employee group, rare person exception |
| Reopen roles | Company |
| Snapshot optional lines | Company |

## 38. Client Extension Framework

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| EXT-CORE-001 | The system shall allow an optional extension to be enabled for one company and left off for all others. | CORE | P1 | COMPANY | ORG-CORE-003 | |
| EXT-CLIENT-001 | A company may enable a factory-style payslip or register layout that prints that company’s legal identity. | CLIENT EXTENSION | P3 | COMPANY | EXT-CORE-001 | Not the universal payslip. |
| EXT-CLIENT-002 | A company may enable an external day-count feed for payslip attendance figures. | CLIENT EXTENSION | P3 | COMPANY | PAY-CORE-006 | Legacy NMS is this kind of extension. It was empty for Viyellatex company 20. |
| EXT-CLIENT-003 | A company may enable mobile location punches as a named source. | CLIENT EXTENSION | P3 | COMPANY | PUNCH-CONF-001 | The traced closed months did not use this path. |
| EXT-CLIENT-004 | A company may enable a named device protocol after that protocol is identified. | CLIENT EXTENSION | P3 | COMPANY | PUNCH-CORE-001 | No vendor is specified. |

## 39. Migration Requirements

Functional only. Legacy columns do not map one-for-one.

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| MIG-CORE-001 | Migration shall load companies, branches, departments, designations, and groups without copying alias-based rules as code. | CORE | P1 | COMPANY | ORG-CORE-001 | |
| MIG-CORE-002 | Migration shall load employees, status, and effective-dated job facts. | CORE | P1 | COMPANY | EMP-CORE-004 | |
| MIG-CORE-003 | Migration shall load punch-identity mappings and shall flag duplicates for a person to resolve. | CORE | P1 | COMPANY | PUNCH-CORE-009 | |
| MIG-CORE-004 | Migration shall be able to load historical punch events as events, not only as day results. | CORE | P2 | COMPANY | PUNCH-CORE-002 | Where the source has only min/max, the loss is recorded. It is not invented back. |
| MIG-CORE-005 | Migration shall load historical day results as results, separate from events. | CORE | P1 | COMPANY | ATT-CORE-002 | |
| MIG-CORE-006 | Migration shall load leave requests and their outcomes. | CORE | P2 | COMPANY | LEAVE-CORE-002 | |
| MIG-CORE-007 | Migration shall load correction requests and shall not invent missing before/after values. | CORE | P2 | COMPANY | CORR-CORE-002 | |
| MIG-CORE-008 | Migration shall load historical payroll results as snapshots, without claiming attendance inputs that were not stored. | CORE | P1 | COMPANY | PAY-CORE-004 | |
| MIG-CORE-009 | Migration shall load shift definitions and whatever dated assignments exist, and shall record when only an employee default existed. | CORE | P2 | COMPANY | SHIFT-CORE-002 | |
| MIG-CORE-010 | Migration shall preserve actor and time when the source has them, and shall mark unknown actor as unknown. | CORE | P1 | COMPANY | AUDIT-CORE-001 | |
| MIG-CORE-011 | Migration shall load a legacy leave balance only as a proposed position with its source evidence. It shall not mark that position confirmed, and it shall not create an approved balance where the opening figure, the reconciliation, or the confirmation is unresolved. | CORE | P1 | COMPANY | LEAVE-CORE-007 | An explicit exception and a recorded sign-off are the only path from proposed to confirmed. No tolerance is invented. A zero balance is not created to fill a gap. |

## 40. Security Requirements

Specified in section 11: SEC-CORE-001 through SEC-CORE-011. In addition, payroll, correction, and period close are explicit permissions. ESS identity is bound to one employee. Approval checks the actor for that request. Sensitive changes are audited.

## 41. Data Integrity Requirements

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| DI-CORE-001 | The system shall not hard-delete punch events, day results, corrections, approvals, or finalized payroll. | CORE | P0 | PRODUCT | AUDIT-CORE-001 | Withdrawal is a status, not a silent delete. |
| DI-CORE-002 | The system shall be able to show the inputs that produced a day result. | CORE | P1 | COMPANY | ATT-CORE-002 | |
| DI-CORE-003 | The system shall keep a finalized snapshot unchanged except through an authorized reopen. | CORE | P0 | COMPANY | PER-CORE-003 | |
| DI-CORE-004 | The system shall stamp company ownership on operational records. | CORE | P0 | COMPANY | ORG-CORE-002 | |
| DI-CORE-005 | The system shall prevent two active uses of the same punch identity in one company unless an explicit overlap is recorded. | CORE | P1 | COMPANY | PUNCH-CORE-009 | |

## 42. Non-Functional Requirements

| ID | Requirement | Class | Pri | Level | Dependency | Notes |
|---|---|---|---|---|---|---|
| NFR-CORE-001 | The system shall scale to multiple companies and to high punch volume without sharing one company’s policy with another. | CORE | P0 | PRODUCT | ORG-CORE-001 | Technology is not chosen here. |
| NFR-CORE-002 | The system shall remain auditable for attendance and payroll changes. | CORE | P0 | PRODUCT | AUDIT-CORE-001 | |
| NFR-CORE-003 | The system shall protect confidentiality of pay and personal data within the company boundary. | CORE | P0 | COMPANY | SEC-CORE-002 | |
| NFR-CORE-004 | The system shall keep interactive attendance and payroll operations usable at company scale. | CORE | P1 | PRODUCT | — | No numeric SLO is set. |
| NFR-CORE-005 | The system shall complete a payroll run and a payslip from stored data without recalculating live attendance. | CORE | P1 | COMPANY | PAY-CORE-006 | |
| NFR-CORE-006 | The system shall reject or quarantine a failed import without partial silent writes. | CORE | P1 | COMPANY | PUNCH-CORE-005 | |
| NFR-CORE-007 | The system shall treat an external punch feed as replaceable behind the ingestion boundary. | CORE | P1 | PRODUCT | PUNCH-CORE-001 | |
| NFR-CORE-008 | The system shall keep company policy in configuration so that a new company does not require a code branch. | CORE | P0 | PRODUCT | CFG-CORE-002 | |

## 43. Business Decision Register D01–D18

No decision below is resolved. No universal numeric default is recommended.

| ID | Domain | Business question | Universal capability | Policy variable | Level | Pri | Depends | Owner | Status | Evidence | Must not freeze |
|---|---|---|---|---|---|---|---|---|---|---|---|
| BD-D01 | Attendance | How long is grace? | ATT-CORE-005, ATT-CONF-003 | Minutes, or none | SHIFT | P1 | D03 | Attendance/Admin, HR | BUSINESS DECISION | Proedge label after about 16 minutes. Viyellatex late at 117 seconds. | 16 minutes, or zero |
| BD-D02 | Attendance | What does an incomplete punch become? | ATT-CORE-004, ATT-CONF-002 | Resulting state and pay effect | COMPANY | P0 | D03 | Attendance/Admin, HR, Payroll | BUSINESS DECISION | Proedge: Present plus text. Viyellatex: A | Absent, or Present |
| BD-D03 | Attendance | Which assignment is the day’s shift? | SHIFT-CORE-002, SHIFT-CONF-001 | Winner rule | COMPANY | P0 | — | Attendance/Admin | BUSINESS DECISION | Proedge: employee shift, calendar empty. Viyellatex: calendar, 30 mismatches | Either source as the product default |
| BD-D04 | Attendance | From when are late minutes counted? | ATT-CONF-003 | Start of measurement | SHIFT | P1 | D01, D03 | Attendance/Admin | BUSINESS DECISION | Proedge seconds ran from shift start even when the label waited. Viyellatex seconds ran from shift start | A hidden second threshold |
| BD-D05 | Attendance | What is early out, and does it affect pay? | ATT-CORE-006, ATT-CONF-004, PAY-CONF-003 | Threshold and pay switch | SHIFT | P1 | D03 | Attendance/Admin, Payroll | BUSINESS DECISION | Both months showed early time and did not reduce basic | A deduction formula |
| BD-D06 | Leave | Which leave types are paid? | LEAVE-CONF-002 | Paid, unpaid, or partial per type | COMPANY | P1 | D02, payable days | HR, Payroll, Finance | BUSINESS DECISION | 16 Viyellatex leave days and 5 Proedge leave days left basic unchanged | “All leave is paid” |
| BD-D07 | Payroll | Does absence reduce pay? | PAY-CONF-002 | On/off and formula | COMPANY | P1 | D02, PAY-CONF-001 | Payroll, Finance, HR | BUSINESS DECISION | Proedge: no deduction. Viyellatex Absenteeism sometimes near gross/30 × A, often not | gross/30 |
| BD-D08 | OT | Who may earn OT? | OT-CONF-001 | Group rule | EMPLOYEE GROUP | P1 | D09 | HR, Payroll | BUSINESS DECISION | Legacy flag did not match either month | `eligible_for_ot` |
| BD-D09 | OT | How is OT calculated? | OT-CONF-002 | Formula | SHIFT | P0 | D03 | Attendance/Admin, Payroll | BUSINESS DECISION | Seconds after end, or whole hours on correction days | Either formula |
| BD-D10 | OT | How does OT round? | OT-CONF-003 | Method | COMPANY | P2 | D09 | Payroll | BUSINESS DECISION | Viyellatex code uses a 0.75 fraction. Proedge kept seconds | That fraction |
| BD-D11 | OT | Must OT be approved on its own? | OT-CONF-004 | Required or not | COMPANY | P1 | D09, D17 | HR, Attendance/Admin | BUSINESS DECISION | Neither month had an OT request type | Treating punch approval as OT approval |
| BD-D12 | OT | What is paid, and at what rate? | OT-CONF-005, OT-CORE-002, OT-CORE-003 | Pay switch, rate, min, max | COMPANY | P1 | D08–D11 | Payroll, Finance, HR | BUSINESS DECISION | Proedge paid 6,158.25 not equal to the seconds. Viyellatex paid 0. A print showed basic/208×2 and did not multiply | basic/208×2 |
| BD-D13 | Period | Is attendance frozen? | PER-CONF-001 | On/off and date | COMPANY | P1 | D02, D16 | Attendance/Admin, Management | BUSINESS DECISION | Both studied months were open | “The lock table means closed” |
| BD-D14 | Period | Is payroll frozen after generation? | PER-CONF-002, PAY-CORE-006 | On/off | COMPANY | P1 | D18 | Payroll, Management | BUSINESS DECISION | Payslip reads stored salary. Regeneration was not blocked | Uncontrolled regeneration as the standard |
| BD-D15 | Period | Who may reopen? | PER-CORE-003, PER-CONF-003 | Roles | COMPANY | P2 | D13, D14 | Management, Payroll | DEFERRED | Reopen was never observed | A reopen path with no close |
| BD-D16 | Correction | What may a correction change? | CORR-CONF-001, CORR-CORE-002 | Allowed fields | COMPANY | P0 | D02, D04, D05 | HR, Attendance/Admin | BUSINESS DECISION | One client rewrote the punch to the shift boundary. The other left the day unchanged | Silent overwrite |
| BD-D17 | Approval | What is the step list? | APP-CONF-001 | Steps and bypass | COMPANY | P1 | D16, D11 | HR, Management | BUSINESS DECISION | Boss and grand boss were columns. Some approvals had no boss | Fixed boss and grand boss |
| BD-D18 | Payroll | Which optional facts are snapshotted? | PAY-CONF-004 | The optional lines | COMPANY | P0 | D06, D07, D12 | Payroll, Finance, Product Owner | BUSINESS DECISION | Both snapshots were amounts only | A snapshot that omits inputs the formula used |

Payable-day basis is part of BD-D07’s dependency and PAY-CONF-001. It has no universal default.

## 44. Open Questions / Evidence Gaps

### Unknown evidence

These are not requirements and not decisions.

- The system that writes the shared legacy punch log.
- The meaning of legacy `balid` and `area`.
- The device or cloud protocol.
- Whether `proedgea_attlogvtl` is that writer.
- Atlas and CEVA operated policy. Their process code exists. A closed month was not traced.
- Payable-day behavior for a joiner or leaver. The studied months were full months.
- A Proedge correction that actually changed the day. August 2026’s did not.
- Whether the Viyellatex missing-punch email was sent.
- The Architecture Assessment Plan was not in the workspace.

### Business decisions

D01–D18 in section 43. They are choices. They are not missing facts about the legacy system.

## 45. Requirements Traceability

| Legacy evidence | Universal capability | Configuration or extension | IDs |
|---|---|---|---|
| Two companies, two databases, alias branches | Company boundary and policy as data | Company configuration | ORG-CORE-001, ORG-CORE-003, CFG-CORE-002 |
| Proedge employee shift vs Viyellatex calendar | One effective shift per date | Winner rule | SHIFT-CORE-002, SHIFT-CONF-001, BD-D03 |
| `photo` vs `weekly_holiday` vs calendar H | Weekly-off rule | Group variation | HOL-CORE-001, HOL-CONF-001 |
| Shared `all_att_log`, empty company, min and max only | Event ingestion, keep all events | Which punches count; named source | PUNCH-CORE-001, PUNCH-CORE-004, PUNCH-CORE-007, PUNCH-CONF-003 |
| Card sometimes equals employee code, sometimes does not; duplicates exist | Punch identity map | How many identities | PUNCH-CORE-008, PUNCH-CONF-004 |
| Present-plus-text vs A | Incomplete punch detection | Company outcome | ATT-CORE-004, ATT-CONF-002, BD-D02 |
| About 16 minutes vs 117 seconds | Late measurement | Shift grace | ATT-CORE-005, ATT-CONF-003, BD-D01, BD-D04 |
| Status 3 leave becomes a day code; basic unchanged | Leave can mark the day | Pay effect per type | LEAVE-CORE-004, LEAVE-CONF-002, BD-D06 |
| Approval rewrote Viyellatex punches; Proedge approval did not | History plus a policy of what may change | Allowed fields | CORR-CORE-002, CORR-CONF-001, BD-D16 |
| Boss, grand boss, and null boss | Ordered steps | Company chain | APP-CORE-001, APP-CONF-001, BD-D17 |
| Seconds vs whole hours; paid amount not equal to hours; Viyellatex paid zero | Calculated, payable, and paid OT | Formula, eligibility, rate | OT-CORE-001, OT-CORE-002, OT-CORE-003, OT-CONF-002, OT-CONF-005 |
| Salary rows with no attendance columns | Declared inputs and a snapshot | Which lines are stored | PAY-CORE-004, PAY-CORE-007, PAY-CONF-004, BD-D18 |
| Payslip reads stored salary | Payslip from snapshot | — | PAY-CORE-006 |
| Locks empty or years old | Optional cutoff | On or off | PER-CORE-001, PER-CONF-001, BD-D13 |
| Module check only; buttons hide actions | Server-side authorization | Roles | SEC-CORE-001, SEC-CORE-005 |
| NMS payslip, factory headers, mobile GPS, unnamed device | Extension slot | Named extensions, off by default | EXT-CORE-001, EXT-CLIENT-001–004 |

### Legacy behavior that is not a universal requirement

Unless a later workshop adopts one as a company policy or a named extension, the following are excluded:

- `employees.photo` as weekly holiday.
- Database or company aliases controlling behavior.
- A numeric status code with a universal meaning.
- Hardcoded boss and grand boss.
- `eligible_for_ot` as the OT mechanism.
- `basic/208×2` as the OT rate.
- Missing punch automatically becoming Absent.
- Missing punch automatically becoming Present.
- Approval silently overwriting attendance.
- Approval without before and after values.
- Reprocessing attendance changing finalized payroll.
- Hidden buttons as authorization.
- A shared punch table with no source and no company.
- Hardcoded report headers.
- Two conflicting OT writers.
- NMS logic as universal behavior.
- A code branch per legacy database.
- Hard delete of business-critical records.
- A lock flag treated as a complete close and audit control.

## 46. Requirements Readiness

### REQUIREMENTS READINESS

| Item | Status |
|---|---|
| CORE CAPABILITIES | READY |
| CONFIGURATION MODEL | READY |
| BUSINESS POLICY VALUES | DECISION REQUIRED |
| ARCHITECTURE | NOT STARTED |
| DEVELOPMENT | NOT STARTED |

Stage 2 note: those two lines are the wording of this section as first written. They are not the current checkpoint. Steps 20A–20O built foundation and lifecycle tables. Attendance, leave, overtime, and payroll tables remain specified and unbuilt. Policy values remain unsigned. The control center is the current status.

### REQUIREMENTS THAT MUST NOT YET BE FROZEN

- Grace minutes (D01).
- Incomplete-punch outcome and its pay effect (D02).
- Shift winner (D03).
- Late measurement start and any late deduction (D04, D05).
- Early-out threshold and any early deduction (D05).
- Paid, unpaid, or partial leave by type (D06).
- The accepted opening leave balance, the operational leave year, encashment meaning, and the person who confirms a balance (LEAVE-OPENING). Stored year labels are not that decision.
- Absence formula, including whether any formula exists (D07).
- Payable-day basis.
- OT eligibility, formula, rounding, approval, minimum, maximum, and rate (D08–D12).
- Whether attendance or payroll close is turned on (D13, D14).
- Who may reopen (D15), until close is on.
- Which correction fields are editable (D16).
- The approval step list (D17).
- Which optional snapshot lines are stored (D18).
- Company financial-year start and end months (FY-BOUNDARY). Examples are not company settings.
- Any vendor protocol, `balid`, or `area` meaning.
- Proedge status words and Viyellatex status letters.
- `basic/208×2`, gross/30, and the 0.75 OT fraction.

## 47. Quality check

| # | Check | Result |
|---|---|---|
| 1 | Did Proedge behavior become a universal requirement? | No. Employee shift, 16-minute grace, Present-plus-text, and the OT head are configuration or excluded. |
| 2 | Did Viyellatex behavior become a universal requirement? | No. Calendar-as-winner, letter codes, zero grace, and unpaid OT are not the standard. |
| 3 | Did a legacy database field become a required design? | No. `photo`, `disconrinue`, `eligible_for_ot`, `balid`, `area`, and `nms_data` are named only as exclusions. |
| 4 | Did a numeric status become universal? | No. Status vocabulary is ATT-CONF-001. Leave write state is LEAVE-CONF-001. |
| 5 | Did a client OT rule become universal? | No. Calculated, payable, and paid OT are separate. Rate and formula are open. |
| 6 | Did a client leave or payroll rule become universal? | No. Pay effect and absence deduction are open. Full basic in the samples is evidence, not a rule. |
| 7 | Did an approval hierarchy become hardcoded? | No. APP-CORE-001 requires ordered configurable steps. |
| 8 | Did an unresolved decision get answered? | No. Section 43 leaves D01–D18 open. D15 is deferred. |
| 9 | Did a capability get classified only as a business decision? | No. Detection, history, snapshot, and payslip-from-snapshot are CORE. The values are BUSINESS DECISION. |
| 10 | Did a configurable policy get classified as CORE? | No. Grace, formulas, and vocabularies are CONFIGURABLE. |
| 11 | Is original / requested / approved / final history required? | Yes. CORR-CORE-002 and CORR-CORE-004. |
| 12 | Are calculated, payable, and paid OT separate? | Yes. OT-CORE-001, OT-CORE-002, OT-CORE-003. |
| 13 | Is the attendance result separate from payroll? | Yes. ATT-CORE-002 and PAY-CORE-004. |
| 14 | Can live attendance change finalized payroll? | No. ATT-CORE-007 and DI-CORE-003. |
| 15 | Is server-side authorization required? | Yes. SEC-CORE-001 and SEC-CORE-005. |
| 16 | Are unknown evidence and business decisions distinguished? | Yes. Section 44 separates them. |

---

## Appendix A. Requirement index counts

Counted from the requirement rows in this document. Each ID appears once.

| Class | Count |
|---|---:|
| CORE | 116 |
| CONFIGURABLE | 34 |
| CLIENT EXTENSION | 4 |
| BUSINESS DECISION | 17 |
| DEFERRED | 2 |
| Total | 173 |

P0 rows: 52. That is 47 capability or configuration requirements marked P0, plus BD-D02, BD-D03, BD-D09, BD-D16, and BD-D18. The two deferred rows are PER-CONF-003 and BD-D15. The earlier inventory was 166. Stage 2 added FY-CONF-001 as P1 CONFIGURABLE, which made 167: 111 CORE and 33 CONFIGURABLE. Six later rows make 173: LEAVE-CORE-005, LEAVE-CORE-006, LEAVE-CORE-007, LEAVE-CORE-008, and MIG-CORE-011 are CORE, and LEAVE-CONF-003 is CONFIGURABLE. That is 116 CORE and 34 CONFIGURABLE. LEAVE-CORE-008 stays proposed and is not an approved pay rule. D01–D18 were not renumbered.

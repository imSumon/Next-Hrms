# Universal HRMS — Incremental Delivery and Decision Gates

Date: 2026-10-12

Status: **PLAN PREPARED**. This record does not approve a policy, start a stage, or mark a company migration-approved. The DEC-01 meeting has not occurred. No Step 20R decision field was filled.

## 1. Delivery principle

Proceed with development when the required architecture, data integrity rules, security controls, and dependencies are sufficiently defined. Resolve a business decision when the feature that depends on it is about to be built.

A proposal, including the D6 session text and the external D1, D3, and D4 draft, is not an approval.

## 2. What is already in place

Stages 4A, 4B, and 4C are recorded in their implementation reports. This plan did not rerun their tests. The Stage 4C report records `php artisan test` as 31 passed and 147 assertions on `proedge_new_hrms_test`. That count is the report’s figure.

In the Laravel application today:

- Employee identity is a ULID. The employee code is unique inside one company and is not changed after create.
- An employee has at most one `Open` episode. An `Open` episode cannot store a last working date.
- Login, an active company membership, and `can_manage_employees` gate the employee screens.
- Rejoin, as approved, is a new episode on the same employee and the same code. The previous episode remains. The screen still refuses to set an existing non-Open episode back to `Open`.

AUD-01 through AUD-06 stay complete. They were not repeated.

## 3. Classification

**Blocks all implementation:** none. Company, employee, episode, user, and membership tables exist. The approved identity and company-scope rules are already enforced. No open item removes the ability to change code that stays inside those rules.

**Approved,** from Step 20R section 2 and Step 20B section 5: ID-APPROVED, CODE-APPROVED, NOMERGE-APPROVED, D7-APPROVED, SEED-DONE, RECON-APPROVED (the contract, not an executed load), ARCH-APPROVED, PUNCH-APPROVED, and LIFE-CORE-008. Rejoin keeps the employee identity and employee code, inserts a new episode, and leaves the previous episode in place. One `Open` episode, and an empty last working date while `Open`, stay approved. The three company rows are tenant records. They are not a migration approval.

**Deferred:** BD-D15, as already deferred in Step 20R. PER-CONF-003, as already deferred in the requirements. SCOPE-01 stays out of the first release until an owner adds a module. This plan does not change those labels.

## 4. Decision gates

| ID | Status | Class | Dependent feature | Permitted before approval | Prohibited until approval | Required by | Approver or evidence already named |
| --- | --- | --- | --- | --- | --- | --- | --- |
| D1 | Unsigned. Option A is a proposal | Blocks legacy name load | `display_name` for a legacy row, including VTL id 437 | Manual entry of a display name on a new identity | A load that composes the three legacy name fields | Before the employee migration that writes those names | HR operations. Steps 20G–20K |
| D2 | Unsigned | Blocks legacy zero-date load | Episode dates copied from `0000-00-00` | Storing a real date entered in the application | Treating `0000-00-00` as a target date | Before a load that reads those source dates | HR operations |
| D3 | Unsigned. Option A is a proposal | Blocks legacy status load | Episode status when `active` and `disconrinue` are both 1 | The three stored statuses on a manually entered episode | Inferring `inactive` or `separated` from that flag pair | Before a load of those rows | HR operations |
| D4 | Unsigned hold. No merge and no silent recode are approved | Blocks the 14 duplicate rows | Those seven VTL codes | Keeping both identities distinct | Merge, silent recode, or loading the group without a disposition | Before a load of those 14 rows | HR operations |
| D5 | Unresolved. No definition | Can be resolved later | Any later domain that reads `employees.dis` | Leaving the column out of identity and status | Mapping `dis` into identity or episode status | Only if a later domain uses the column | Not assigned |
| D6 | Unsigned. Register proposal is Option A. The session proposal is also unsigned | Blocks legacy episode creation | Creating an episode from a legacy employee row | Creating an episode from data entered in the application | Creating an episode from an unverified legacy row, and adopting either proposal as the load rule | Before a load that creates episodes | HR operations. Approver and date for the session proposal are not recorded |
| LIFE-OVERLAP | Unresolved. No recommendation | Blocks a specific check | A rule that rejects overlapping episode dates, and a load that writes a second episode | The existing one-`Open` rule. The current screen may store dates without an overlap check | A new overlap rejection, and a migration that writes a second episode | Before that check or that load | HR operations, before a second episode is designed |
| LIFE-ORDER | Unresolved. No recommendation | Blocks a specific check | Rejecting a last working date before the joining date | The current screen, which can store reversed dates and says the rule is not approved | A validation that rejects reversed dates | Before that validation or a load that needs it | HR operations |
| LIFE-INACTIVE-PAY | Unresolved. No recommendation | Blocks payroll use of `inactive` | Pay result of episode status `inactive` | Storing the status. LIFE-CORE-006 remains the requirement that inactive follows company policy | A pay amount, a payable-day change, or a payroll formula for `inactive` | Before payroll reads `inactive` | HR operations and Finance. D06/D07 |
| Inactive last working date | Unresolved. No decision ID and no options | Blocks a specific check | Whether `inactive` may store `last_working_date` | The current screen stores a supplied date and does not treat it as pay | A rule that requires or forbids that date | Before that rule | Not named beyond Step 20B section 5 |
| Assignment | No decision ID. Columns are not defined | Blocks assignment | `employment_assignments` and department, designation, group, location, and shift history | Foundation nodes remain separate tables | An assignment migration, screen, or payroll use of those facts | Before that schema | EMP-CORE-004 and LIFE-CORE-002 through LIFE-CORE-005. The owner must name the columns and references |
| BD-D01 | Unsigned | Blocks grace | Late and early grace | — | Freezing 16 or 31 minutes, or another grace value | Before attendance grace is built | Attendance/Admin, HR |
| BD-D02 | Unsigned | Blocks an incomplete punch | Missing in or out outcome | Detecting that the day is incomplete, without copying a company result | An approved outcome or pay effect | Before that outcome is built | HR operations |
| BD-D03 | Unsigned | Blocks shift selection | Employee default versus roster | Storing both candidates | Choosing a winner | Before shift selection is built | HR operations |
| BD-D04 | Unsigned | Blocks late counting | Start of late minutes | — | A hidden second threshold | Before late counting is built | Attendance/Admin |
| BD-D05 | Unsigned | Blocks early-out pay | Early-out definition and pay effect | — | An early-out deduction formula | Before that pay effect is built | Attendance/Admin, Payroll |
| BD-D06 | Unsigned | Blocks paid leave | Which leave types are paid | — | A rule that all leave is paid | Before leave pay is built | HR, Payroll, Finance |
| BD-D07 | Unsigned | Blocks absence and joiner or leaver pay | Absence deduction and partial-month pay | The AUD-06 source description | A payable-day or proration formula, including a 30-day or calendar divisor | Before payroll calculation | Payroll, Finance, HR |
| BD-D08 | Unsigned | Blocks overtime eligibility | Who may earn overtime | — | Freezing `eligible_for_ot` | Before overtime eligibility is built | HR, Payroll |
| BD-D09 | Unsigned | Blocks overtime calculation | How overtime is calculated | — | Either studied formula | Before overtime calculation | Attendance/Admin, Payroll |
| BD-D10 | Unsigned | Blocks overtime rounding | Rounding rule | — | The 0.75 fraction | Before overtime rounding is built | Payroll |
| BD-D11 | Unsigned | Blocks overtime approval | Whether overtime needs its own approval | — | Treating punch approval as overtime approval | Before that approval is built | HR, Attendance/Admin |
| BD-D12 | Unsigned | Blocks overtime pay | Rate, minimum, and maximum | — | basic/208×2 or either studied amount | Before overtime pay is built | Payroll, Finance, HR |
| BD-D13 | Unsigned | Blocks attendance close | Attendance cutoff | — | Treating a Yes flag as a cutoff | Before attendance freeze is built | HR operations |
| BD-D14 | Unsigned | Blocks payroll close behavior | Freeze after generation | The approved snapshot rule: a finalized snapshot does not follow later attendance | A regeneration block beyond that approved rule | Before that close behavior is built | Finance |
| BD-D15 | Deferred | Deferred | Who may reopen a closed period | — | A reopen action | Remains deferred until an owner owns a reopen rule | Management, Payroll |
| BD-D16 | Unsigned | Blocks correction fields | Which fields a correction may change | The approved mechanism: original, requested, approved, and final values. The raw punch is not edited | A field list | Before correction editing is built | HR operations |
| BD-D17 | Unsigned | Blocks approval steps | The approval step list | — | A fixed boss and grand-boss sequence | Before that workflow is built | HR, Management |
| BD-D18 | Unsigned | Blocks optional snapshot lines | Which extra facts are snapshotted | Snapshot separation, which is approved | Invented snapshot lines | Before those lines are built | Finance |
| FY-BOUNDARY | Unsigned | Blocks financial-year reports | Start and end month of a company financial year | A payroll month that is not a financial year | January–December, June–May, or March–February as a company’s setting | Before a financial-year report | Finance. FY-CONF-001 |
| PUNCH-WITHDRAW | Unsigned | Blocks punch withdrawal | Who may withdraw a raw punch, and the audit record | Punch immutability | Deleting or withdrawing a punch | Before a withdrawal action | HR operations |
| SEC-CORE-003 | Requirement. Scope model unsigned | Blocks a specific limit | Branch, department, or employee scope | Company membership as implemented | A scope narrower than the company | Before that limit is built | Not named as a person |
| SEC-CORE-004 | Requirement. Role list unsigned | Blocks a role matrix | Permissions granted by role | `can_manage_employees` on the employee module only | Copying that flag onto payroll, attendance correction, or period close | Before a role list is built | Not named as a person |
| DB-01 | Open until MySQL is already running | Can be resolved later | Live catalog comparison | Application work on the approved schema | Treating the 25 / 101 / 416 catalog as live-verified. Starting MySQL from this plan | When MySQL is already running | Step 21C id list |
| SCOPE-01 | Deferred in Step 21E | Deferred | Recruitment, PMS, funds, and the other inventory-only groups | — | An audit or build of those modules | When an owner adds one to the first release | Owner named by that addition |

Employee migration stays blocked until D1, D2, D3, D4 dispositions, and D6 are signed, the exception register is signed, reconciliation has been executed, and a named company approval exists. A company row does not provide that approval.

## 5. Current checkpoint

The rejoin-message correction is complete. The validation message and the employee screens state the approved rejoin rule. That correction is not the next task.

The next implementation milestone is not yet authorized.

Two unsigned review documents are available:

- `docs/UNIVERSAL_HRMS_PRODUCT_SURFACE_AND_ACCESS_MODEL_DECISION.md`
- `docs/UNIVERSAL_HRMS_BD_D16_BD_D17_OWNER_DECISION_WORKSHEET.md`

No module, permission, schema, or migration should be implemented merely because this documentation correction is complete. The next implementation requires the applicable owner decisions and the gates already recorded in this file. This section does not change a decision status, fill Step 20R, or authorize a company migration.

## 6. Statement

No application code, migration, database, or environment file was changed. No business decision was approved. AUD-01 through AUD-06 were not repeated.

INCREMENTAL DELIVERY PLAN PREPARED

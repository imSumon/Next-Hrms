# Universal HRMS — Decision Readiness Review

Date: 2026-10-16

Status: **REVIEW FOR THE OWNER.** This file organizes decisions that are already unsigned. It does not approve a policy, sign a worksheet, fill Step 20R, or authorize implementation. No company is migration-approved.

The current documentation set is `next-hrms\docs`. The parent `docs` folder still has an older control center and an older incremental-delivery plan. Those older copies are not the checkpoint used here.

## A. Current project position

### Implemented

The Laravel application has session login, an active company, POST logout, the employee directory and profile, display-name editing and history, episode protections, and the approved rejoin rule. Department and designation masters exist under the Stage 5D working direction. That direction is not an owner signature. The two membership flags, `can_manage_employees` and `can_manage_organization_masters`, are module grants. They are not a role list. Employees are not assigned to departments or designations. Branch writes are not authorized.

An earlier full run reported 33 tests and 198 assertions. A later focused run reported 50 tests and 500 assertions. Those runs are automated evidence. They are not signed-in browser UAT. This review did not rerun them.

### Audited or documented

AUD-01 through AUD-06 are source audits. AUD-07 records approval callers and the payroll reads for company ids 24, 20, and 10. It does not rewrite AUD-02 and it does not authorize a design. The access-model brief and the BD-D16 / BD-D17 worksheet are unsigned review papers. Architecture principles AP-01 through AP-05, ADR-008, and ADR-009 are accepted for the product shape. They do not choose field lists, step lists, grace, or rates.

### Incomplete

Dynamic RBAC, platform administration, product-default administration, HR and manager workflows, ESS as a real session, the policy engine, attendance, leave, overtime, payroll, period close, PF/GF, reconciliation, UAT, and cutover are not built. Assignment columns are undefined. Ninety-one legacy submodule rows remain inventory. Live catalog comparison has not been done.

### Evidence versus an approved decision

| Kind of record | What it may be used for | What it does not do |
| --- | --- | --- |
| AUD-02, AUD-07, and the architecture legacy sections | Show what the old system does | Sign BD-D16 or BD-D17 |
| Stage 5D permission note | Explain the two flags already in the application | Create a company-administrator role |
| Access-model brief and BD worksheet | Put questions in front of the owner | Select an alternative |
| Step 20R proposed recommendations | Record an earlier proposal marked **PROPOSED — NOT APPROVED** | Fill the blank decision fields |
| Company rows for catalog ids 24, 20, and 10 | Identify tenant records | Approve a migration |
| Automated tests | Show the foundation behaves as coded | Prove browser UAT or a signed policy |

### Implementation authorization

The next implementation milestone is not authorized. The incremental-delivery plan, section 5, says no module, permission, schema, or migration should be built merely because a documentation correction is complete. The next implementation requires the applicable owner decisions and the gates already recorded.

## B. Decision register

Owner approval is required before implementation of the feature each row blocks. A row that can be deferred does not block maintenance of the employee foundation already built.

### 1. Access model and product surfaces

Source: `docs/UNIVERSAL_HRMS_PRODUCT_SURFACE_AND_ACCESS_MODEL_DECISION.md`, SEC-CORE-003 and SEC-CORE-004 in the gates file, AUD-03, ADR-009, and ADR-013. Status: unsigned. Blocks the next product milestone that would add a permission, a role, a scope, or another administration screen. Can be deferred only for maintenance of the existing employee and master screens.

| Question already written | Why it matters | Options already documented | If left unsigned |
| --- | --- | --- | --- |
| SEC-CORE-004: permissions are granted by role. The role list is blank | The next module otherwise becomes another Boolean | The gates permit `can_manage_employees` on the employee module only. They prohibit copying that flag onto payroll, attendance correction, or period close. The Stage 5D note says the master flag does not grant those writes. The access brief's proposal, not a decision, is to add no third flag until a signed role model replaces the two that exist | A new screen has no honest grant |
| SEC-CORE-003: the system shall be able to limit a user to a branch, department, or employee scope. The scope model is unsigned | A narrower limit can hide or expose the wrong people | The gates permit company membership as implemented. They prohibit a scope narrower than the company until that model is signed | Company-wide access inside the active company remains the implemented boundary |
| Does a platform principal exist, and which cross-company actions does it have? | AUD-03 found legacy level `supadmin`. The audit says that cross-company access is not the default | No platform role is listed. The brief states the question and does not choose an answer | Platform administration stays unbuilt |
| Is every role company-scoped, or may a signed role act across companies? | AUD-03 left this unsigned | No global-administrator default is documented as approved | Same |
| How is a role given to a user inside a company? | Assignment of access is not the same as membership | No assignment rule is documented | Same |
| Which settings are product defaults, and which are company policy? | CFG-CORE-001 gives the order: product, then company, then a lower level only when that policy allows it. D01–D18 are the values | The order is accepted. The values are not chosen | Product administration stays unbuilt |

The five surfaces in the brief are responsibilities in one product. They are not five systems to build now. Organization nodes remain approval scopes, not departments or boss columns. That domain decision is already in force.

### 2. Attendance correction and approval — BD-D16 and BD-D17

Source: the owner worksheet, AUD-02, AUD-07, architecture sections 11 and 12, ADR-008, and ADR-009. Step 20R already contains a proposed recommendation to avoid seeding a field list or a boss step. That recommendation is **PROPOSED — NOT APPROVED.** This review does not adopt it.

Both decisions block attendance correction and the approval workflow. They do not block the employee foundation. Signing them does not unlock payroll calculation, period close, or a role catalogue.

**BD-D16.** Which approved correction values may affect a calculated day, and under what conditions.

Already decided, and not part of the choice: a correction is not the raw punch and not the day row. The record keeps original, requested, approved, and final values. The raw punch is not edited. An approved correction may produce a new day-result version.

Confirmed evidence, not a rule to copy:

- Reprocessing applies a correction when `attendance_approval_file.status = 1`, then rebuilds the day.
- Proedge and Viyellatex normal approval does not set status `1` and does not change the day.
- Atlas, and CEVA grand-boss approval, can set status `1` and can rewrite a day only when the row already exists. Several branches write a shift boundary rather than the requested time.
- For company ids 24, 20, and 10, studied salary generation does not read overtime, late, or absence from the day. Company 20's register displays those figures beside stored pay. It does not write pay.
- A finalized snapshot does not follow later attendance. That is AP-05. Draft recalculation and absence pay stay on BD-D14 and BD-D07.

The worksheet already lists three alternatives and selects none:

| Alternative already written | Consequence supported by the sources | Dependency that stays open |
| --- | --- | --- |
| A. A signed field list becomes input to a new day-result version when signed conditions hold | The day can change. History remains | Copying status `1`, or "only if the row exists," would preserve one alias |
| B. Store the approval and change the day only on a separate apply step | Matches the Proedge observation that an approval left the day Absent | Reports that read only the current day would not show the approval. The apply step is not designed |
| C. The calculated day stays the punch result | Corrections are a record only | Atlas and CEVA grand-boss day rewrites would not carry forward unless a later signed exception says so |

Open questions, not options: the field list itself, whether a missing day may be created, which stored state means "apply," and whether a requested time may be replaced by a shift boundary.

**BD-D17.** Who may approve, and how that authority is configured.

Already decided: approval is an ordered workflow. The actor is a user. A step may be a role, a reporting-line manager, an organizational approver, or a named fallback. The server checks this user for this step. A fixed boss and grand-boss sequence is the prohibited copy. A role grant is not, by itself, the approver of one request. The access brief says the same thing and is itself unsigned.

Confirmed evidence: the legacy sequence follows the database alias. Atlas and CEVA grand-boss can rewrite a day. Proedge and `vtl` change application status only. Other aliases submit different status numbers through `attendanceapprovernms`. Some writers do not check that the session employee is the assigned approver.

The worksheet already lists three alternatives and selects none:

| Alternative already written | Consequence supported by the sources | Dependency that stays open |
| --- | --- | --- |
| A. Each company stores its own ordered step list. Each step uses one of the four accepted actor kinds | Companies can differ | A role-typed step waits on SEC-CORE-004. An organization node is not a department |
| B. One product list. A company may turn steps on or off and may not reorder them | Less variation | If that single list is boss then grand-boss, it is the prohibited copy |
| C. More than one list in a company, chosen by a later signed condition | Different request kinds can differ | The condition is not specified. Inventing it would be a new policy |

Open questions, not options: how many steps, whether a later step may skip an earlier one, whether every company shares one list, and which stored outcome means approved, rejected, or returned. Architecture section 12 says an employee-then-manager-then-HR-then-payroll list can be a legal configuration and is not the architecture. This review does not adopt that list.

### 3. Other decisions that block the next attendance or payroll build

These do not block the employee foundation. They block the feature named in the gates file. This review does not add alternatives where the source only states the question and the prohibited copy.

| ID | Question in the gates | Blocks | Prohibited until signed | Can it wait |
| --- | --- | --- | --- | --- |
| BD-D02 | Incomplete punch outcome and pay effect | That outcome | Copying one company's result | Until incomplete-punch behavior is built |
| BD-D03 | Which shift assignment wins | Shift selection | Choosing a winner | Until shift selection is built |
| BD-D07 | Whether absence reduces pay, and joiner or leaver pay | Payroll calculation | A payable-day or proration formula, including a 30-day divisor | Until payroll calculation. AUD-06 describes a code path. It does not approve the formula |
| BD-D09, BD-D11, BD-D12 | Overtime formula, whether overtime approval is separate, and the rate | Overtime calculation and pay | Either studied formula, treating punch approval as overtime approval, and `basic/208×2` | Until that overtime behavior is built |
| BD-D13 | Whether an attendance cutoff is on | Attendance freeze | Treating a Yes flag as a cutoff | Until a freeze is built. The studied approval writers do not check the attendance lock |
| BD-D14 | Payroll close beyond the approved snapshot rule | That extra close behavior | A regeneration block beyond AP-05 | The snapshot rule itself is already approved |
| BD-D18 | Which extra facts are stored on the snapshot | Those extra lines | Invented snapshot lines | Until those lines are built |
| BD-D15 | Who may reopen a closed period | A reopen action | Any reopen action | Deferred until an owner owns a reopen rule |
| FY-BOUNDARY | A company's financial year | A financial-year report | Adopting January–December, June–May, or March–February as the setting | Until that report |

BD-D01, BD-D04, BD-D05, BD-D06, BD-D08, and BD-D10 are also unsigned in the gates file. They block grace, late counting, early-out pay, paid leave, overtime eligibility, and overtime rounding. They are the same class as the rows above. They are not restated here as a second list of choices.

### 4. Migration and production readiness — later gates

Source: Step 20R and the control center. These block an employee load and cutover. They do not block the application that is already running.

| ID | Plain question | Why it is later | Approval required before a load |
| --- | --- | --- | --- |
| D1 | How legacy name parts become `display_name` | A load must not invent a name | Yes. Step 20R lists options. The blank decision field is the only approval. This review does not choose one |
| D2 | How MySQL `0000-00-00` is treated | The target cannot store that token as a real date | Yes |
| D3 | Episode status when `active` and `disconrinue` are both 1 | The flags disagree | Yes |
| D4 | Disposition of the duplicate-code rows | No merge and no silent recode are already approved. The disposition of those rows is not | Yes |
| D6 | Whether a legacy row may create an episode | Two unsigned proposals exist. Neither is the load rule | Yes |
| Exception register, reconciliation, and a named company | The load contract | Design exists. Execution does not. The three company rows are not that approval | Yes |

SCOPE-01 keeps provident fund, gratuity, and the other inventory-only groups out until an owner adds one. The proposal's Phase 1 list does not override that. DB-01, the live catalog comparison, waits until MySQL is already running. This review did not start MySQL.

## C. Recommended review sequence

This is an order of review. It is not a choice of policy.

1. **Access model.** Review the unsigned brief first. Any next administration or workflow screen needs a grant. The two existing flags are already in the product and must not be copied. SEC-CORE-004 and the platform-principal question sit here. SEC-CORE-003 can be reviewed in the same sitting and left deferred if the owner is not ready to limit anyone below the company.

2. **BD-D16 and BD-D17 together.** Correction effect and approval authority are one worksheet. A field list without a step list, or a step list without a field list, still does not specify an attendance workflow. Do this after the access review because a step of kind "role" depends on SEC-CORE-004, and because approval authority is not a module flag.

3. **The other attendance and payroll gates, only when that behavior is about to be specified.** The delivery principle is to resolve a business decision when the feature that depends on it is about to be built. BD-D07, BD-D13, and BD-D14 matter before payroll calculation and period close. They do not have to be decided in order to keep the employee foundation.

4. **D1–D6, the exception register, and a named company, when a load is about to be prepared.** Migration design is not migration approval.

## D. Implementation readiness

### Safe without a new business policy

Maintenance of the existing employee, episode, login, logout, and department and designation behavior, inside the rules already in force. Documentation that records finished work or points at unsigned papers, which is what section 5 of the gates file now does. No new permission, schema, or module is part of that safety.

The control center still says return links on the create and edit screens were identified and are not implemented. This review did not re-check every screen. If that gap is still present, closing it on the existing screens would be technical work inside current rules. It is not authorized by this file.

### Blocked by an owner decision

A new role or permission. A scope narrower than the company. Branch writes. Employee assignment. Platform or product-administration screens. Attendance correction, approval workflow, leave pay, overtime, payroll calculation, and period close. Each waits on the gate named in section B.

### Deferred until later gates

Employee migration and reconciliation. PF/GF and the rest of SCOPE-01. Live catalog comparison. Production cutover. Reopen of a closed period, which is BD-D15.

An implemented screen is not evidence that its access rule or its business rule was signed. The department and designation masters are the example: they exist, and the permission note is still a working direction.

## E. Owner action list

1. Read the access-model brief and decide whether the next product work waits for a signed role model. Leave SEC-CORE-003 and SEC-CORE-004 unsigned if the decision is not ready. Do not choose middleware or table names.
2. Read the BD-D16 and BD-D17 worksheet. The alternatives are already lettered. Pick none until you are ready to write a decision, approver, and date into Step 20R.
3. Leave BD-D07, BD-D13, BD-D14, and the other payroll gates for the sitting that specifies payroll. They are not required to answer items 1 and 2.
4. Leave D1–D6 for the sitting that authorizes a named company load.

## F. Source conflicts and missing evidence

| Item | What this review found |
| --- | --- |
| Parent `docs\UNIVERSAL_HRMS_INCREMENTAL_DELIVERY_AND_DECISION_GATES.md` section 5 | Still says the next task is to correct the rejoin validation message. The `next-hrms\docs` copy of that section no longer says that. Use the `next-hrms\docs` copy |
| Parent `docs\UNIVERSAL_HRMS_PROJECT_CONTROL_CENTER.md` | Does not contain the 2026-10-16 correction. The access brief, the BD worksheet, and AUD-07 are not in the parent folder |
| `next-hrms\docs` control center, section 4 | One row still says the incremental-delivery plan was not rewritten by the 2026-10-16 correction. Section 5 of that plan was rewritten afterward. The row describes the earlier correction and was not edited by this review |
| Architecture assessment section 26 | Still says development was not started. The control center already treats the matching line in requirements section 46 as older than the foundation |
| Return links | The control center says they are not implemented. This review did not re-inspect the screens |
| Status `20`, the external punch writer, and whether `vtlnms`, `sfls`, `palstorew`, or `vsl` are company ids 24, 20, or 10 | Still unresolved in AUD-04 and AUD-07. Not required to hold the owner review |
| Signed-in browser UAT | Not verified |

## G. Sources read for this package

- `next-hrms\docs\UNIVERSAL_HRMS_PROJECT_CONTROL_CENTER.md`
- `next-hrms\docs\UNIVERSAL_HRMS_INCREMENTAL_DELIVERY_AND_DECISION_GATES.md`
- `next-hrms\docs\UNIVERSAL_HRMS_PRODUCT_SURFACE_AND_ACCESS_MODEL_DECISION.md`
- `next-hrms\docs\UNIVERSAL_HRMS_BD_D16_BD_D17_OWNER_DECISION_WORKSHEET.md`
- `next-hrms\docs\UNIVERSAL_HRMS_AUD07_APPROVAL_CALLERS_AND_PAYROLL_READS.md`
- Step 20R's existing BD-D16 and BD-D17 entry, and the D1–D6 gate statements already cited by the control center
- The parent `docs` control center opening and the parent gates file section 5, only to confirm they are older

AUD-01 through AUD-06 were not repeated. No source document was rewritten.

## H. Statement

No application code, test, schema, configuration, database, or Git state was changed. No decision or gate was signed.

REVIEW FOR THE OWNER

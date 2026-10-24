# Universal HRMS — LEAVE-OPENING Owner Decision Worksheet

Date: 2026-10-18

Status: **UNSIGNED / FOR OWNER REVIEW.** This worksheet places the evidence and the four LEAVE-OPENING questions in front of the product owner. It does not select an opening figure, a leave period, a reconciliation formula, or a confirmer. It does not fill Step 20R. It does not sign LEAVE-OPENING, LEAVE-CORE-008, BD-D06, BD-D17, FY-BOUNDARY, or any other gate. D1–D6 stay unsigned. No company is migration-approved. This worksheet does not authorize an implementation milestone.

LO-01, LO-02, LO-03, and LO-04 are temporary worksheet labels only. They are not official requirement IDs and they do not replace LEAVE-OPENING.

Step 20R records LEAVE-OPENING as unsigned and states that the 2026-10-17 dumps do not answer it. No formula is proposed in that register. This worksheet does not adopt a formula and does not replace that register entry.

## 1. What is already decided or already recorded

These stay in force as written. The owner is not being asked to reopen the approved architecture. Requirements that are recorded but unsigned as policy values stay recorded.

| Already decided or recorded | Source | Limit |
| --- | --- | --- |
| One product. Company policy is data | ARCH-APPROVED. AP-02 | A stored company value is not a product default |
| Leave is inside the first-release scope boundary | Step 20R section 6D | That boundary is not implementation authorization. Hourly-leave module 30 stays on the roadmap and outside that boundary |
| A proposed leave-balance position is one company, one employee, one leave type, and one leave period. It holds the proposed amount, the period start and end or the period label, the source evidence, and the stated basis | LEAVE-CORE-005. First-release design boundary, leave-position row | The shape of a proposed position may be recorded. It is not an approved balance |
| Each movement and each reversal is its own entry. A reversal leaves the original movement readable | LEAVE-CORE-006 | No earning, carry-forward, expiry, or encashment formula is implied |
| A proposed position records reconciliation result, variance, exception status, and authorized confirmation. Without confirmation it is not available as an approved balance for leave approval. States are proposed, excepted, and confirmed. Variance is a recorded difference. An unreconciled variance stays empty. No numerical tolerance is set | LEAVE-CORE-007 | Who confirms, and what figure is confirmed, stay unsigned |
| The company configures its leave period separately from the payroll month and from the financial year | LEAVE-CONF-003. FY-CONF-001 | Stored legacy labels are evidence of what was stored. They are not the signed rule. FY-BOUNDARY stays unsigned |
| A legacy leave amount may load only as a proposed position with its source evidence. It shall not be marked confirmed where the opening figure, the reconciliation, or the confirmation is unresolved. A zero balance is not created to fill a gap | MIG-CORE-011 | No company is migration-approved. Reconciliation of employees and episodes is a separate approved contract and is not executed |
| For a real load, source count = loaded + approved exceptions | RECON-APPROVED | Not executed. Quarantine is not the loaded side |
| Leave types are company-defined | LEAVE-CORE-001 | Which types are paid is BD-D06 and stays unsigned |
| No SEC-CORE-004 category includes leave. Company membership does not grant leave confirmation | Step 20R section 6C | The two membership flags stay. The role-matrix build stays excluded. No new permission boolean is added by this worksheet |
| LEAVE-CORE-008 is proposed: if encashment is later included in pay, the payroll result would reference the encashment record and keep the paid amount distinct from the ledger day count | LEAVE-CORE-008. PAY-CORE-007 | **PROPOSED. Not approved.** It chooses no formula, eligibility, or approval role |

What stays open, and is the subject of this worksheet, is the LEAVE-OPENING set already named in Step 20R:

- which opening figure is accepted
- which leave period is operational
- how adjustments and encashment are reconciled
- who confirms a balance before approval

Employee-foundation UAT is outside this worksheet. UAT-01, UAT-03, UAT-04, UAT-05, UAT-06, and UAT-07 are **PASS** for their defined approved checks. UAT-02 is **defined flag checks PASS**. Owner UAT remains **PARTIAL**. Those results are not leave-balance evidence and do not authorize implementation.

Proedge, Viyellatex, and Atlas are evidence sources. They are not templates.

## 2. Proposed design constraints for owner review

These constraints are **proposed**. They are not a claim that the features exist, and they are not an approval to build them.

- No company-specific value is hard-coded in application logic.
- Company policies, leave periods, and effective dates are represented as configurable data.
- An opening position is traceable to its source and to the applicable period.
- Reconciliation discrepancies and exceptions stay visible. They are not silently overwritten.
- Leave-unit accounting stays separate from payroll monetary calculations.
- Balance confirmation and later corrections are auditable.
- No new permission boolean is added. Company membership is not confirmation authority.
- No implementation proceeds until the applicable requirements, dependencies, and delivery gates are approved.

## 3. LO-01 — Authoritative opening figure

Temporary label only. The official decision id remains LEAVE-OPENING.

### Observed legacy behavior and evidence

The dumps and source traces do not record an accepted opening formula, a reconciliation method, or a named confirmer for Proedge, Viyellatex, or Atlas. F-32.

Potential stored sources that already appear in the evidence, and that must not be treated as a chosen universal figure:

| Potential source | What the evidence shows | Why it is not a decision |
| --- | --- | --- |
| `earned` on the annual-leave ledger | Present as a stored amount on earned-leave rows | Gates section 4 prohibits choosing `earned` until LEAVE-OPENING is signed |
| `prev_bal` | Proedge: zero on 484 earned-leave rows. Viyellatex: non-zero on 5 of 2,411. Atlas: non-zero on 240 of 355 | Same prohibition on choosing `prev_bal` or `prev_bal` plus `earned` |
| `encashment` on the annual-leave ledger | Proedge and Viyellatex: zero. Atlas: non-zero on 184 earned-leave rows | A stored day count, not a confirmed opening and not a payroll payment. F-32, F-33, F-37 |
| A generator, form, or upload result | Stored rows identify no writer and carry no confirmation. Historical use of a generator, form, or upload remains unknown | F-34. Copying the stored amount would promote an unresolved figure |
| An `ENC` application token | Atlas: one application row contains the token. Proedge and Viyellatex: none found | F-37. Not reconstructed as an opening rule |
| `earn_leave_encashment` | A separate payroll table that can store a net payable. It does not write the annual-leave ledger or `employee_salary`. Atlas dump has no rows. Proedge and Viyellatex dumps have no rows | F-36, F-37. Money, not a leave-unit opening |
| Atlas salary head 398 / `additional_module4` / `employee_salary` | Stored module and salary rows exist. Neither table stores an annual-leave encashment id or an ENC application id. F-38 does not link them to the encashment ledger | A monetary store. Not an opening leave-unit figure |
| Hourly-leave balance | AUD-01 / F-02 describe a separate hourly store and July–June proration | Hourly-leave module 30 is outside the signed first-release boundary. It is not the annual-leave opening |

Atlas also has 15 other-code balance rows labeled `2020-2021` (12) or `2022-2023` (3). Their meaning and validation are unresolved. F-33.

### Recommended universal HRMS design, proposed

For owner review only. Not an approval to implement.

- Record each candidate figure as a proposed position under LEAVE-CORE-005, with company, employee, leave type, period, source evidence, and stated basis.
- Do not copy one company’s stored field into product logic.
- Do not treat an empty confirmation as an approved balance. MIG-CORE-011 and LEAVE-CORE-007.

Supporting records that a later confirmation would need, when they exist:

- the source store and the source row identity
- company, employee, leave type, and period
- the stated basis (which field, which external record, or which owner-supplied figure)
- the actor or writer, if one is known
- related movements that already affect the period
- any exception request and its recorded authorization

Where a writer is unknown, that absence stays visible. F-34.

### How conflicts should be presented

Do not invent a precedence rule, a tolerance, or a balance. When two or more of the sources above disagree, or when a stored current figure disagrees with reconstructed movements, the presentation for the owner is:

| Fact to show | Why |
| --- | --- |
| Each candidate amount, its source, and its period | LEAVE-CORE-005 |
| The recorded difference (variance) | LEAVE-CORE-007. No numerical tolerance is set. An unreconciled variance stays empty |
| Exception status | proposed, excepted, or confirmed. A conflict is not silently replaced |
| What is missing (writer, confirmer, related movement, payroll link) | F-32, F-34, F-37, F-38 |

### Who can authorize exceptions

This worksheet does not name a role, a sequence, or a permission mapping.

LEAVE-CORE-007 requires an authorized confirmation before a position is an approved balance. MIG-CORE-011 allows only an explicit exception and a recorded sign-off as the path from proposed to confirmed.

No SEC-CORE-004 category includes leave. Company membership is not that authorization. `can_manage_employees` and `can_manage_organization_masters` are not leave-confirmation grants. The owner must say who may authorize an exception. Until that is written into Step 20R, the exception path stays unsigned.

### Owner questions for LO-01

Blank answers stay unsigned.

| Question | Why it is open |
| --- | --- |
| For a named company and leave type, which figure may become the proposed opening: a named stored field, an owner-supplied verified amount, or a hold until evidence exists? | F-32. Gates section 4 forbids choosing `earned` or `prev_bal` plus `earned` from the dumps |
| May different companies, or different leave types, use different sources? | ARCH-APPROVED says company policy is data. This worksheet does not choose one source |
| When `earned`, `prev_bal`, ledger `encashment`, and any later reconstructed movements disagree, what is shown to the owner, and what may be confirmed? | No precedence or tolerance is approved |
| Who may authorize an exception that holds or overrides a candidate figure? | No confirmer is recorded. No leave category exists |

No alternative is selected.

| Alternative, not selected | What would be true if later signed | Risk if chosen without the related gates |
| --- | --- | --- |
| A. A named stored field, for a named company and period, is the proposed opening and still requires confirmation | The source is explicit. The amount remains proposed until LO-04 | Copying Atlas or Viyellatex field use into code would freeze one legacy practice |
| B. An independently verified amount, supplied with evidence, is the proposed opening | The dump field is not assumed correct | The verifier and the evidence standard are still LO-04 |
| C. The row is held as excepted until a source is evidenced | MIG-CORE-011 already refuses a silent confirmed load | An empty opening is not filled with zero to close the gap |

## 4. LO-02 — Operational leave period

Temporary label only.

### Observed legacy behavior and evidence

| Fact | Evidence |
| --- | --- |
| Proedge and Viyellatex dumps, local company 5, store leave-year start 7 and end 6. Balance-row labels are `2015-2016` through `2026-2027`. Every balance row in those dumps uses a July–June label | F-31 |
| Atlas, local company 26, stores start 1 and end 12. Labels are `2021` through `2026`. All 355 Atlas earned-leave rows use a calendar-year label | F-31 |
| Atlas has 15 other-code rows labeled `2020-2021` or `2022-2023` beside calendar-year earned-leave rows | F-33 |
| Hourly leave uses a July–June proration in the studied source | F-02. Separate store. Module 30 is outside the first-release boundary |
| Payroll month, financial year, and leave year are already specified as separate ideas | FY-CONF-001. LEAVE-CONF-003. F-28 |

July–June and January–December are stored evidence. They are not product defaults. F-31. Design boundary, leave-period slot.

### Recommended universal HRMS design, proposed

- The leave period is a company setting with a start, an end, and an effective date, stored as data.
- It is not the payroll month and not the financial year.
- FY-BOUNDARY remains the unsigned decision for financial-year months. Signing a leave period does not sign FY-BOUNDARY.
- A legacy label may be recorded as source evidence on a proposed position. It does not become the operational period by being copied.

### What must be decided when legacy and target periods differ

| Question | Why it is open |
| --- | --- |
| For each company, what are the operational leave-period start month, end month, and first effective date? | LEAVE-CONF-003. Months are empty in the functional design |
| If a stored legacy label does not match the signed target period, is the row mapped, split, held, or rejected? | F-31 and F-33 show more than one label style. No mapping rule is signed |
| Does a mid-year change of leave period create a new period for new positions, or rewrite the open period? | Effective dates are required by the proposed constraint. No change rule is signed |
| Are hourly-leave periods in or out of this decision? | Module 30 stays outside the first-release boundary. Do not import that period as the annual rule |

No alternative is selected. Calendar year and July–June are not offered as a universal choice.

## 5. LO-03 — Adjustment and encashment reconciliation

Temporary label only.

### Observed legacy behavior and evidence

Day-leave balance deducts only when status becomes 3. AUD-02. F-03. That is a usage write in the legacy day-leave path. It is not an opening rule and not a universal approval code. LEAVE-CONF-001 says legacy status 3 is not the universal code.

Encashment stores are separate and are not a complete payment trail:

| Finding | Evidence |
| --- | --- |
| Encashment upload forces status 3, replaces an `ENC` application for the same employee, year, and start date, and overwrites `leave_emp_annual_leave.encashment` with the file’s day count. It does not write `employee_salary`. No transaction, month lock, or history | F-35 |
| `earn_leave_encashment` upload replaces by employee code and year and can store a net payable. It does not write the annual-leave ledger or `employee_salary` | F-36 |
| The studied salary generate view does not read either encashment store | F-37 |
| Atlas head 398 and `additional_module4` are not linked to the encashment ledger or to an ENC application id | F-38 |
| Proedge and Viyellatex dumps show no ENC token, no `earn_leave_encashment` rows, and annual `encashment` zero | F-37 |

The incomplete link between encashment records and payroll payment evidence is the F-35 through F-38 set. Those findings are not a payment result and not a finalized month.

### Need to distinguish opening positions from later movements

LEAVE-CORE-005 is the opening position. LEAVE-CORE-006 is each later movement or reversal. Mixing them in one overwritten field is the legacy pattern in F-35 and is not the recommended design.

Evidence needed before a later reconciliation can be judged, when it exists:

- leave usage tied to a request or to a recorded adjustment
- adjustments and their reason
- reversals that leave the original movement readable
- encashment as a leave-unit movement, separate from any later monetary pay
- payroll payment evidence only when a signed rule says encashment is included in pay

### Proposed reconciliation structure, for review only

**Proposed. Not approved.** This structure chooses no accrual, carry-forward, expiry, encashment eligibility, or payroll formula.

For one company, employee, leave type, and period:

1. Record the proposed opening (LO-01) as its own position.
2. Record each later movement as its own entry: usage, adjustment, reversal, encashment day count.
3. A reversal does not erase the original entry.
4. Show opening plus movements as a reconstructed leave-unit total.
5. Compare that total with any stored current figure. Record the variance. Do not overwrite either side.
6. If a monetary encashment or salary amount is later in scope, compare it as a separate money column. Do not add it to the leave-unit total. LEAVE-CORE-008 remains proposed and is not activated.
7. Omissions and possible double counts stay visible: a day count without a payment, a payment without a day count, two stores holding the same employee-year, or a generate path that reads neither store.

Questions about avoiding omissions and double counting, unsigned:

| Question | Why it is open |
| --- | --- |
| Which movement kinds are in the first confirmed opening reconciliation: usage, adjustment, reversal, encashment day count, or a smaller set? | LEAVE-CORE-006 names the mechanism. It does not name the first set |
| How is a stored ledger `encashment` treated if no `employee_salary` row and no `earn_leave_encashment` row exists? | F-35, F-37 |
| How is a salary or module amount treated if no encashment ledger id exists? | F-38 |
| May an opening already include a net-of-encashment figure, or must encashment always be a later movement? | Choosing either is a formula. None is approved |

LEAVE-CORE-008, BD-D06, and payroll calculation stay unsigned or proposed. This section does not approve them.

## 6. LO-04 — Confirmation responsibility

Temporary label only.

### Observed legacy behavior and evidence

No named balance confirmer is recorded for Proedge, Viyellatex, or Atlas. Stored balance rows identify no writer and carry no confirmation. F-32. F-34.

Functional design section 3.7: who confirms is unsigned, so the confirmed state has no actor rule. No approved category includes leave.

### Recommended universal HRMS design, proposed

- A position is not an approved balance until confirmation is recorded. LEAVE-CORE-007.
- Confirmation records the confirmer, the decision, and the time. A later correction of a confirmed position is a new auditable entry, not a silent overwrite. LEAVE-CORE-006. AUDIT-CORE-001.
- Exception handling uses the same record: the exception status, the reason, and the person who authorized the exception.
- Company membership is not confirmation authority. Neither existing membership flag is that authority. No new permission boolean is introduced here.

This worksheet does not invent an approver role, an approval sequence, or a mapping from SEC-CORE-004 categories. BD-D17 remains the unsigned approval-step decision and is not filled by a leave-balance confirmer.

### Evidence needed to support confirmation

- the proposed position (LO-01)
- the operational period (LO-02)
- the reconciliation presentation and any variance or exception (LO-03)
- the confirmer’s identity as a user account that can be audited
- the decision text and the timestamp

### Owner questions for LO-04

| Question | Constraint already recorded |
| --- | --- |
| Who confirms a proposed opening for a company? | The actor must be recordable. No role name is supplied here |
| Is confirmation one person, or more than one recorded actor? | Do not invent a sequence. BD-D17 stays unsigned and is about request approval, not this confirmation |
| What decision text is stored: confirm, except, or return to proposed? | LEAVE-CORE-007 already names proposed, excepted, and confirmed |
| Does confirmation of an opening also confirm later movements in that period, or only the opening amount? | LEAVE-CORE-006 keeps movements separate |

No alternative is selected. Naming a job title in conversation is not a Step 20R signature.

## 7. Capture block for a later register entry

Copy into Step 20R only after the owner decides. Every decision field below is blank on purpose.

### LEAVE-OPENING

| Field | Content |
| --- | --- |
| Management's actual decision | |
| Approver name/role | |
| Approval date | |
| Conditions or exceptions | |
| Responsible owner | HR operations, as already named in the gates |
| Due date | |

Optional notes the owner may later attach. Not a signature:

| Temporary label | Owner answer, if later written |
| --- | --- |
| LO-01 Authoritative opening figure | |
| LO-02 Operational leave period | |
| LO-03 Adjustment and encashment reconciliation | |
| LO-04 Confirmation responsibility | |

## 8. What remains blocked, unsigned, or deferred after this worksheet

LEAVE-OPENING stays **unsigned**. An approved leave balance and a load that would mark a legacy balance confirmed stay blocked. Recording a proposed position remains the permitted limit already stated in the gates.

This worksheet does not authorize a leave-balance screen, a schema, a migration, a seeder, or a data load.

Remain unsigned or proposed, and are not decided here:

- LEAVE-CORE-008 (proposed encashment-in-pay sentence)
- BD-D06 (which leave types are paid)
- BD-D17 (approval step list)
- FY-BOUNDARY (financial-year months)
- BD-D07 (absence and joiner or leaver pay)
- SEC-CORE-003 (narrower-than-company scope)
- Role-matrix implementation (SEC-CORE-004 list stays approved; the build stays excluded)

Remain deferred:

- BD-D15
- SCOPE-01, including hourly-leave module 30 as a first-release build
- Employee migration and any company load approval

The first-release scope boundary in Step 20R section 6D is unchanged. The next implementation milestone is not authorized.

## 9. Supporting paths

- `next-hrms\docs\UNIVERSAL_HRMS_DOCUMENTATION_AUTHORITY.md`
- `next-hrms\docs\UNIVERSAL_HRMS_PROJECT_CONTROL_CENTER.md`
- `next-hrms\docs\UNIVERSAL_HRMS_INCREMENTAL_DELIVERY_AND_DECISION_GATES.md` — LEAVE-OPENING row and section 5
- `next-hrms\docs\UNIVERSAL_HRMS_STEP20R_MANAGEMENT_DECISION_REGISTER.md` — LEAVE-OPENING remains unsigned. Not modified
- `next-hrms\docs\UNIVERSAL_HRMS_CONSOLIDATED_EVIDENCE_REGISTER.md` — F-31 through F-38
- `next-hrms\docs\UNIVERSAL_HRMS_REQUIREMENTS.md` — LEAVE-CORE-005 through LEAVE-CORE-008, LEAVE-CONF-003, MIG-CORE-011, FY-CONF-001, PAY-CORE-007
- `next-hrms\docs\UNIVERSAL_HRMS_FIRST_RELEASE_DESIGN_BOUNDARY.md` — leave-position and leave-period slots
- `next-hrms\docs\UNIVERSAL_HRMS_FIRST_RELEASE_FUNCTIONAL_DESIGN.md` — section 3.7
- `next-hrms\docs\UNIVERSAL_HRMS_AUD02_LEAVE_AND_ATTENDANCE_APPROVAL_AUDIT.md` — status-3 deduction. Not repeated

## 10. Statement

No application code, schema, test, configuration, database, migration, seeder, or Git state was changed. No leave-balance row was loaded. Step 20R blank fields were not filled. LEAVE-OPENING remains unsigned. No implementation milestone was authorized.

UNSIGNED / FOR OWNER REVIEW

# Universal HRMS — BD-D16 and BD-D17 Owner Decision Worksheet

Date: 2026-10-16

Status: **UNSIGNED / FOR OWNER REVIEW.** This worksheet places the evidence and the choices in front of the product owner. It does not select an alternative. It does not fill Step 20R. It does not sign BD-D16, BD-D17, or any other gate. D1–D6 stay unsigned. No company is migration-approved.

Step 20R already holds one proposed recommendation for these two items: do not seed a field list or a boss step. That recommendation remains **PROPOSED — NOT APPROVED.** This worksheet does not adopt it and does not replace it.

## 1. What is already decided

These stay in force. The owner is not being asked to reopen them.

| Already decided | Source |
| --- | --- |
| A correction is not a raw punch and not the calculated day | CORR-CORE-001. ADR-008 |
| The correction record keeps original, requested, approved, and final values, with actor, time, and reason | CORR-CORE-002, CORR-CORE-003. PUNCH-APPROVED in Step 20R |
| An approved correction may produce a new day-result version. It does not edit the raw punch. Earlier values stay available | ADR-008. Architecture assessment section 11 |
| Approval is an ordered workflow. The actor is a user. A step may be a role, a reporting-line manager, an organizational approver, or a named fallback | ADR-009. APP-CORE-001 through APP-CORE-003 |
| The server checks that this user may take this step of this request | SEC-CORE-006. ADR-013 |
| Boss and grand-boss columns are not the authorization model | ADR-009. Step 20 domain decisions |
| An organization node is an approval scope. It is not a department, a branch, or `employees.boss` | Step 20 final domain decisions |
| A finalized payroll snapshot does not follow a later attendance recalculation. Draft payroll may be recalculated | AP-05. ADR-011 |
| Company policy is data. An alias branch is not migrated as code | AP-02. ADR-001 |

What stays open, and is the subject of this worksheet:

- BD-D16: which of those values may change the calculated day, and under which documented conditions.
- BD-D17: the ordered step list, including whether a later step may skip an earlier one.

The access-model brief remains unsigned. A role that may open attendance is not, by itself, the actor for a correction step. SEC-CORE-004's role list is still unsigned, so a step that says "a role" cannot be built until that list exists.

## 2. BD-D16 — Which approved values may affect the calculated day

### Confirmed legacy behavior

These points come from AUD-02, AUD-07, and the attendance-to-payroll trace. The findings document in Downloads states the same separation and says it is not a development specification.

| Fact | Evidence |
| --- | --- |
| Raw punches are stored in `all_att_log`. The external writer of closed-month rows is unknown. The approval actions that were read do not update that table | AUD-04. AUD-02 section 3 |
| A correction application is a row in `attendance_approval_file`. The calculated day is a row in `attendance_detail`. They are separate records | AUD-07 section 4 |
| Reprocessing deletes the current day row for that employee and date and rebuilds it from punches. It reads a correction only when `attendance_approval_file.status = 1` | Dependency trace, recorded in AUD-07. Status 3 and 4 are not that read |
| For session names `proedge` and `vtl`, `actionApproveatt` changes application status only: 2 becomes 3, 3 becomes 4, 98 becomes 3. It does not set status `1` and does not rewrite the day | AUD-02 section 3 |
| The normal Proedge and Viyellatex buttons call `approveatt` and `rejectatt`. They do not send `s`. No normal screen for those session names was found that sets status `1` | AUD-07 section 3 |
| Screens that do send status `1` call `actionAttendanceapprovernms` with aliases `sfls` or `vtlnms`. Those aliases are not company ids 24, 20, or 10, and `vtlnms` is not the `vtl` session. That action updates the application only | AUD-07 sections 2 and 3 |
| Atlas, and CEVA when the session employee is the grand-boss, can set status `1` and can rewrite an existing day. If the day row is missing, it stays missing. Several branches write the shift start or shift end rather than the requested time | AUD-02 section 3 |
| A Proedge August approval stored requested times while the day stayed Absent with null punches. A Viyellatex sample replaced the displayed punch with a shift boundary and lost the original out | Architecture assessment section 11. Those months were not re-read for this worksheet |
| `actionAttendanceapprover` can write any client `s` and has no located screen caller. It does not check boss, grand-boss, or a month lock, and it does not update the day | AUD-07 section 2 |
| For company ids 24, 20, and 10, the studied salary generation and the default payslip do not read overtime, late, or absence from the day row. Company 20's salary register displays those figures beside amounts already stored in `employee_salary`. It does not write pay | AUD-07 section 5 |
| Attendance month lock and salary month lock are separate. The timecard and `actionAttendanceapprover` do not check the attendance lock. A Yes flag is not an approved cutoff | Dependency trace. BD-D13 unsigned. ADR-012 |

### The choice the owner must make

ADR-008 already says an approved correction may recalculate a new day-result version. BD-D16 asks which approved values are allowed to influence that version, and which conditions must be true before they do.

The owner is asked to answer these questions. Blank answers stay unsigned.

| Question | Why it is open |
| --- | --- |
| Which values may move from the approved correction into the new day result? Examples that exist in the legacy screens include in-time, out-time, and a present or absent result. This list is not a proposal | The gates permit the four-value mechanism and prohibit a field list until it is signed |
| Does an approved correction affect a day that does not yet exist, or only a day row that already exists? | Atlas and CEVA grand-boss approval update a day only when the row exists. Reprocessing creates a day from punches and then applies status `1`. Those are different behaviors |
| Which recorded state means "apply this correction on recalculation"? | Status `1` is that state for the studied rebuild. Proedge and Viyellatex normal approval does not produce status `1`. Other aliases use 1, 2, 3, 4, 10, and 15 for different buttons |
| May recalculation replace a requested time with a shift boundary? | Several legacy branches do that. The findings document records it as a risk, not as a rule |
| After a day is recalculated, what happens to a payroll amount already stored? | AP-05 already says a finalized snapshot does not follow later attendance. Draft recalculation, absence pay, and overtime pay are BD-D07, BD-D12, and BD-D14, and they stay unsigned |

### Neutral alternatives

No alternative is selected.

| Alternative | What would be true if the owner later chose it | Risk if chosen without the related gates |
| --- | --- | --- |
| A. Approved values in a signed field list become inputs to a new day-result version when the company's signed conditions hold | The day can change. Original punch and earlier day values remain. The field list is explicit | A condition copied from status `1`, or from "only if the row exists," would recreate one alias and leave the others unexplained |
| B. Approved values are stored on the correction and do not change the calculated day until a separate apply step | Request and result stay unlinked until that step. This matches the Proedge August observation that a stored approval left the day Absent | Reports that read only the current day would not show the approval. The apply step is still a design, and it is not authorized by this worksheet |
| C. Approved values never change the calculated day. The day remains the punch calculation | Corrections are a record only. Recalculation cannot be overridden | Companies that today rewrite a day, including Atlas and CEVA grand-boss approval, would lose that effect unless a later signed exception adds it |

Whichever alternative is later signed, the raw punch stays unedited. That is already decided.

### Dependencies that this decision does not close

| Dependency | Current status | Effect on BD-D16 |
| --- | --- | --- |
| Attendance cutoff | BD-D13 unsigned. A Yes flag is not a cutoff | The owner can say whether a correction is refused after a cutoff only by signing BD-D13. This worksheet does not |
| Payroll close | BD-D14 unsigned beyond the approved snapshot rule | A day recalculation must not rewrite a finalized snapshot. Whether draft payroll is regenerated is still open |
| Reopen | BD-D15 deferred | No reopen actor is named |
| Audit history | CORR-CORE-002 through CORR-CORE-004 are the mechanism. Legacy history is incomplete: some originals were overwritten | A signed field list still has to keep original, requested, approved, and final values |
| Absence, late, and overtime pay | BD-D07 and BD-D09 through BD-D12 unsigned | Changing a day does not, by itself, define a pay amount. The studied generate path for ids 24, 20, and 10 does not read those day fields |
| Month lock on the approval action | The studied approval writers do not check `AttendanceMonthLock` | Signing a field list does not create a lock |

## 3. BD-D17 — Who may approve, and how that authority is configured

### Confirmed legacy behavior

Approval authority in the legacy application follows the session database name. It is not one sequence.

| Session or alias | Who the code allows | What status becomes | Day row |
| --- | --- | --- | --- |
| `atlas` | Session employee equals `boss_id` or `gboss_id` | 1 | Rewritten if the row exists |
| `ceva` grand-boss | Session employee equals `gboss_id` | 1 | Rewritten if the row exists |
| `ceva` boss | Session employee equals `boss_id` | 2 to 3, 3 to 4, 98 to 3 | Not rewritten |
| `proedge`, `vtl`, and other names in that branch | Session employee equals `boss_id` or `gboss_id` | Same status-only map | Not rewritten |
| `isml` and `vtl`, from the approval list | The list action itself | 99, described as a lock of the application | Not rewritten |
| `sfls`, `vtlnms`, `palstorew`, `vsl` through `attendanceapprovernms` | A session user level. The module check is commented out. Boss and grand-boss are not compared | Client `s`: 1, 2, 3, 4, 10, or 15 depending on the screen | Not rewritten |

AUD-02 also records that approver identity on stored rows can be a name string, that an approver date can be a string, and that some fully approved rows have a null boss. The day-leave approver is a different action and is not this decision. AUD-02 says day-leave balance changes at status 3. That is not an attendance-day rule.

`actionAttendanceapprover` and `actionAttendanceapprovernms` accept a client status and an id. The lines read do not check that the session employee owns the application or is the assigned approver.

### Distinctions the owner is asked to keep

| Idea | Meaning already in the architecture | What it is not |
| --- | --- | --- |
| A user account | The actor who takes a step. APP-CORE-003 | A boss id copied from `employees.boss` |
| A role | A grant of actions, once SEC-CORE-004 is signed | Proof that this user may approve this request |
| Company membership | The tenant boundary. The active company is the implemented foundation | Approval authority |
| An organizational scope | Optional branch, department, or employee limit under SEC-CORE-003, still unsigned. An organization node is a separate approval scope | A department row or a fixed grand-boss column |
| A workflow step | One position in an ordered, configurable list. The server checks this user for this step. SEC-CORE-006 | A module flag, a hidden button, or status `1` |

The access-model brief says the same thing and is itself unsigned: opening a module, holding `can_manage_employees`, or holding `can_manage_organization_masters` does not make a person the approver of a correction.

Architecture assessment section 12 says an "Employee, then manager, then HR, then payroll" list can be a legal configuration. It also says that list is not the architecture. This worksheet does not adopt that list.

### The choices the owner must make before a workflow can be specified

| Question | Constraint that is already accepted |
| --- | --- |
| How many steps does a company use, and what does each step decide? | The list is configurable. APP-CONF-001. A fixed boss then grand-boss sequence is the prohibited copy |
| For each step, how is the actor found: a role, a reporting-line manager, an organization node, or a named user? | Those four kinds are the accepted step kinds. APP-CORE-002. This worksheet does not say which kind a company uses |
| May a later step act while an earlier step is unfinished? | That skip is part of BD-D17. It is not decided |
| Does every company share one step list, or may the list differ by company? | AP-02 says company policy is data. It does not choose one list |
| May one company use different lists for different request kinds? | Not specified. A request kind is not selected here |
| Which stored outcome means approved, rejected, or returned? | Legacy codes differ by alias. No code is adopted |
| Must the actor be a user account that can be audited? | APP-CORE-003 says the actor is a user. Legacy name strings are not that model |

### Neutral alternatives

No alternative is selected.

| Alternative | What would be true if the owner later chose it | Risk if chosen without the related gates |
| --- | --- | --- |
| A. Each company stores its own ordered step list. Each step names one of the four accepted actor kinds | Companies can differ. The server checks the actor for that step | A role-typed step cannot be built while SEC-CORE-004 is unsigned. An organization-node step is not a department |
| B. One product step list exists. A company turns steps on or off and does not reorder them | Less variation. Easier to explain | It can repeat the legacy problem if the single list is the boss and grand-boss pair |
| C. A company may store more than one list, chosen by a later signed condition such as request kind | Different corrections can follow different paths | The condition is another unsigned rule. Inventing it now would be a policy |

A signed choice still does not authorize attendance tables, routes, or screens.

## 4. Capture block for a later register entry

Copy into Step 20R only after the owner decides. Every field below is blank on purpose.

### BD-D16

| Field | Content |
| --- | --- |
| Management's actual decision | |
| Approver name/role | |
| Approval date | |
| Conditions or exceptions | |
| Responsible owner | HR operations, as already named in the gates |
| Due date | |

### BD-D17

| Field | Content |
| --- | --- |
| Management's actual decision | |
| Approver name/role | |
| Approval date | |
| Conditions or exceptions | |
| Responsible owner | HR and Management, as already named in the gates |
| Due date | |

## 5. What remains blocked after this worksheet

Attendance correction editing stays blocked until BD-D16 is signed. The approval workflow stays blocked until BD-D17 is signed. Attendance, leave, overtime, and payroll stay blocked on the control-center list, including BD-D02, BD-D03, BD-D07, BD-D09, BD-D11, BD-D12, BD-D13, BD-D14, and BD-D18. BD-D15 stays deferred. SEC-CORE-003 and SEC-CORE-004 stay unsigned. Employee migration stays blocked on D1–D6.

## 6. Supporting paths

- `next-hrms\docs\UNIVERSAL_HRMS_PROJECT_CONTROL_CENTER.md`
- `next-hrms\docs\UNIVERSAL_HRMS_INCREMENTAL_DELIVERY_AND_DECISION_GATES.md`
- `next-hrms\docs\UNIVERSAL_HRMS_STEP20R_MANAGEMENT_DECISION_REGISTER.md` — BD-D16 and BD-D17 entry. Not modified
- `next-hrms\docs\UNIVERSAL_HRMS_AUD02_LEAVE_AND_ATTENDANCE_APPROVAL_AUDIT.md`
- `next-hrms\docs\UNIVERSAL_HRMS_AUD07_APPROVAL_CALLERS_AND_PAYROLL_READS.md`
- `next-hrms\docs\UNIVERSAL_HRMS_ARCHITECTURE_ASSESSMENT.md` — sections 11 and 12, ADR-008 and ADR-009
- `next-hrms\docs\UNIVERSAL_HRMS_PRODUCT_SURFACE_AND_ACCESS_MODEL_DECISION.md` — unsigned
- `next-hrms\docs\UNIVERSAL_HRMS_STEP20_FINAL_DOMAIN_DECISIONS.md`
- `C:\Users\ASUS\Downloads\HRMS_Attendance_Approval_Functional_Audit_Major_Findings.docx` — supporting evidence. Not a specification. Not re-searched for this worksheet

## 7. Statement

No application code, schema, test, configuration, database, or Git state was changed. No file other than this worksheet was written. No decision or gate was changed.

UNSIGNED / FOR OWNER REVIEW

# Universal HRMS — Stage 3 Decision Meeting Package

Meeting worksheet. It does not record a meeting that has occurred. It does not approve a policy or a migration.

Canonical questions are copied from `docs/UNIVERSAL_HRMS_STEP20R_MANAGEMENT_DECISION_REGISTER.md` and, where that register has no separate subsection, from requirements section 43. Decision, rationale, signature, and date columns are blank.

No company is approved for employee migration. Reconciliation has not been executed.

## 1. Meeting purpose and scope

Purpose: capture real management decisions into the blank fields of Step 20R after the meeting, without rewriting historical audits or proposed text.

In scope: D1–D6, episode checks, BD-D01–BD-D18, FY-BOUNDARY, and the punch-withdrawal procedure.

Out of scope: choosing a company to load, running a dry run, building attendance or payroll tables, and treating January–December, June–May, or March–February as a stored financial year.

## 2. Current decision-status summary

Counts are rows in the current Step 20R and requirements section 43. They are not a count of signed forms.

| Status | Count | IDs |
| --- | --- | --- |
| In force in Step 20R section 2 | 8 | ID-APPROVED, CODE-APPROVED, NOMERGE-APPROVED, D7-APPROVED, SEED-DONE, RECON-APPROVED (not executed), ARCH-APPROVED, PUNCH-APPROVED |
| Proposed, decision fields blank | 13 register subsections | D1, D2, D3, D4 group hold, D6, BD-D02, BD-D03, BD-D09/11/12 as one subsection, BD-D16/17 as one subsection, BD-D13/14/15 as one subsection, PAY-SNAPSHOT for BD-D18, PUNCH-WITHDRAW, FY-BOUNDARY. BD-D15 inside that period subsection stays deferred |
| Open in requirements section 43, no own Step 20R subsection | 7 | BD-D01, BD-D04, BD-D05, BD-D06, BD-D07, BD-D08, BD-D10 |
| Unresolved, no proposal | 4 | D5, LIFE-OVERLAP, LIFE-ORDER, LIFE-INACTIVE-PAY |
| Deferred | 2 | BD-D15, PER-CONF-003 |
| Blocked migration | — | D1, D2, D3, D4 dispositions, D6, unsigned exception register, reconciliation not run, no company approval |
| Blocked attendance and payroll behavior | — | BD-D02, BD-D03, BD-D07, BD-D09, BD-D11, BD-D12, BD-D13, BD-D14, BD-D16, BD-D17, BD-D18 |

The 13 proposed subsections are headings in Step 20R with a proposed recommendation and a blank decision field. BD-D15 is inside the period heading and is deferred, not a fourteenth proposal.

### Draft not adopted

Step 20R and Step 20R-1 capture fields are blank. A draft supplied for this stage, and not found in those files, says:

| ID | Draft label | Approver text | Draft date | Conditions | Status | Migration |
| --- | --- | --- | --- | --- | --- | --- |
| D1 | `obtain_verified_name` | Anisur Rahman | 2026-10-12 | Not recorded | UNSIGNED | NONE |
| D3 | `verify_individually` | Anisur Rahman | 2026-10-12 | Not recorded | UNSIGNED | NONE |
| D4 | `case_by_case` | Anisur Rahman | 2026-10-12 | Not recorded | UNSIGNED | NONE |

A name in that draft is not a signature. The labels are not the option letters in Step 20R. They were not copied into Management's actual decision, Approver, or Approval date.

## 3. Decision worksheet

Fill the blank columns only after an authorized person confirms the decision. Leave them empty if the meeting does not decide.

| ID | Exact question | Why needed | Evidence already available | Options in existing material | Dependencies | Owner if documented | Decision selected | Rationale / conditions | Approver confirmation | Actual decision date | Status |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| D1 | How do `emp_first_name`, `emp_mid_name`, and `emp_last_name` become `display_name`, and what happens when every part is empty? | `display_name` is required. VTL id 437, code `PAL1234`, has all three parts empty | Steps 20G, 20H, 20I, 20K | A. Drop empty parts, join with one space, hold a row with nothing left. B. First name only. C. Reviewer-supplied name on every row. D. Join all three even when a part is empty. Register proposal is A, not approved. The draft label `obtain_verified_name` is not one of these letters | Employee load | HR operations | | | | | UNSIGNED |
| D2 | How is MySQL `0000-00-00` treated? | Joining-date zeros: Proedge 1, Atlas 3, VTL 61. Leaving-date zeros: Proedge 1, Atlas 0, VTL 81 | Steps 20C, 20G, 20H, 20I, 20K | A. Missing, hold the episode. B. Hold employee and episode. C. Distinct zero-date exception, not `NULL`. Proposal is the distinct exception, not approved | Episode dates | HR operations | | | | | UNSIGNED |
| D3 | What episode status is stored when `active` and `disconrinue` are both 1? | Dump counts: Proedge 19, Atlas 74, VTL 1,186. Atlas id 7175, code `EB9091` | Steps 20E, 20G, 20H, 20I, 20K | A. Quarantine the status. B. `separated` only with a real leaving date, quarantine the rest. C. A disposition on each row. `inactive` is not an option. Proposal is A, not approved. Draft label `verify_individually` is not one of these letters | Episode status. Do not infer from `discontinue_date` alone | HR operations | | | | | UNSIGNED |
| D4 | What happens to the seven VTL codes that each appear on two rows? | 14 rows: `10024`, `15575`, `132058`, `6119`, `10023`, `10027`, `10034` | Steps 20E, 20G, 20H, 20I, 20K | No merge and no silent recode are already approved. Open hold: quarantine the group until each row is authorized. Merge and recode are not options. Draft label `case_by_case` is not a signed disposition of the 14 rows | NOMERGE-APPROVED | HR operations | | | | | UNSIGNED |
| D5 | What does `employees.dis` mean? | Integer. Values seen: `NULL`, `0`, and `1` on two Proedge rows. Not the earn-leave ledger | Steps 20G, 20H, 20I | Options require discussion. No definition was found | Keep out of identity and status | HR operations | | | | | UNRESOLVED |
| D6 | When may a current legacy employee row create an employment episode? | No episode table was found. A department, designation, or location change is not an episode | Steps 20B, 20E, 20F, 20H, 20I | A. At most one episode with an approved flag pair and a real joining date. Do not parse text. B. Identities only, hold every episode. C. Parse text. No approved parser. Proposal is A, not approved | Does not decide overlap, date order, or inactive pay | HR operations | | | | | UNSIGNED |
| LIFE-OVERLAP | May two episodes for one employee have overlapping dates? | The table allows one Open episode. It does not forbid other overlap | Step 20B | Leave the check undecided, or approve a check before any second episode | Blocks a second episode | HR operations | | | | | UNRESOLVED |
| LIFE-ORDER | Must `joining_date` be on or before `last_working_date`? | Reversed dates were not given a rule | Step 20B | Options require discussion. No recommendation | Blocks a date-order check | HR operations | | | | | UNRESOLVED |
| LIFE-INACTIVE-PAY | What pay result does episode status `inactive` have? | No pay column on the episode. Do not infer `inactive` from both flags | Steps 20B, 20H | Decide inside D06/D07 before payroll use. Do not add a column in the employee load | Blocks payroll use of `inactive` | HR operations and Finance | | | | | UNRESOLVED |
| BD-D01 | How long is grace? | Companies differed. AUD-05: Atlas action uses 31 minutes. Proedge and CEVA actions use 16. Those minutes are not approved | Requirements section 43. AUD-05 | Options require discussion. Must not freeze 16 minutes or zero | BD-D03 | Attendance/Admin, HR | | | | | UNSIGNED |
| BD-D02 | What is the outcome when an in or out punch is missing? | Proedge and Atlas stored Present plus text. Viyellatex stored a missing out as Absent | Architecture, third-company audit, workbook | No approved outcome. Detect the incomplete day. Do not copy either company | BD-D03 | HR operations | | | | | UNSIGNED |
| BD-D03 | When the employee default and the roster differ, which shift wins? | Proedge August used the employee shift. Viyellatex September used the calendar | Architecture, workbook | No approved winner. Both candidates may be stored | — | HR operations | | | | | UNSIGNED |
| BD-D04 | From when are late minutes counted? | Seconds ran from shift start in the studied months | Requirements section 43 | Options require discussion. Must not freeze a hidden second threshold | BD-D01, BD-D03 | Attendance/Admin | | | | | UNSIGNED |
| BD-D05 | What is early out, and does it affect pay? | Both months showed early time and did not reduce basic | Requirements section 43 | Options require discussion. Must not freeze a deduction formula | BD-D03 | Attendance/Admin, Payroll | | | | | UNSIGNED |
| BD-D06 | Which leave types are paid? | Small samples left basic unchanged. That does not prove every leave type is paid | Requirements section 43 | Options require discussion. Must not freeze “all leave is paid” | BD-D02, payable days | HR, Payroll, Finance | | | | | UNSIGNED |
| BD-D07 | Does absence reduce pay? | Proedge: no deduction. Viyellatex Absenteeism sometimes near gross/30 times A, often not. AUD-06: joiner proration and leaver omission are source formulas, not a signed rule | Requirements section 43. AUD-06 | Options require discussion. Must not freeze gross/30 | BD-D02, payable days | Payroll, Finance, HR | | | | | UNSIGNED |
| BD-D08 | Who may earn OT? | Legacy flag did not match either studied month | Requirements section 43 | Options require discussion. Must not freeze `eligible_for_ot` | BD-D09 | HR, Payroll | | | | | UNSIGNED |
| BD-D09 | How is OT calculated? | Seconds after shift end, or whole hours on correction days. They are not one formula | Requirements section 43. Register groups this with BD-D11 and BD-D12 | Options require discussion. Must not freeze either formula | BD-D03 | Attendance/Admin, Payroll | | | | | UNSIGNED |
| BD-D10 | How does OT round? | Viyellatex code uses a 0.75 fraction. Proedge kept seconds | Requirements section 43 | Options require discussion. Must not freeze that fraction | BD-D09 | Payroll | | | | | UNSIGNED |
| BD-D11 | Must OT be approved on its own? | Neither studied month had an OT request type | Requirements section 43 | Options require discussion. Must not treat punch approval as OT approval | BD-D09, BD-D17 | HR, Attendance/Admin | | | | | UNSIGNED |
| BD-D12 | What is paid, and at what rate? | Proedge paid 6,158.25, not equal to the seconds. Viyellatex paid 0. A print showed basic/208×2 and did not multiply | Requirements section 43 | Options require discussion. Must not freeze basic/208×2. Rate, minimum, and maximum are unset | BD-D08 through BD-D11 | Payroll, Finance, HR | | | | | UNSIGNED |
| BD-D13 | Is attendance frozen? | Lock tables did not mark the studied months closed | Requirements section 43. Register groups BD-D13, BD-D14, BD-D15 | A Yes flag is not a cutoff. Options for the cutoff date require discussion | BD-D02, BD-D16 | HR operations | | | | | UNSIGNED |
| BD-D14 | Is payroll frozen after generation? | Payslip reads stored salary. Regeneration was not blocked | Requirements section 43 | Options require discussion. The approved snapshot rule already says a finalized snapshot does not follow later attendance | BD-D18 | Finance | | | | | UNSIGNED |
| BD-D15 | Who may reopen? | Reopen was never observed | Requirements section 43. Register: deferred | Options require discussion. No reopen role exists | BD-D13, BD-D14 | Management, Payroll | | | | | DEFERRED |
| BD-D16 | What may a correction change? | Atlas and CEVA grand-boss paths can rewrite a day toward the shift. `proedge` and `vtl` in that action change application status only | AUD-02. Architecture sections 11 and 12 | Mechanism is approved: original, requested, approved, and final values. The raw punch is not edited. The field list is empty | BD-D02, BD-D04, BD-D05 | HR operations | | | | | UNSIGNED |
| BD-D17 | What is the step list? | Boss and grand-boss are columns. The leave approver does not compare the session employee to those columns | AUD-02. Requirements section 43 | Options require discussion. Must not freeze a fixed boss and grand-boss sequence | BD-D16, BD-D11 | HR, Management | | | | | UNSIGNED |
| BD-D18 | Which optional facts are snapshotted? | Studied salary rows have amounts and no attendance snapshot | Architecture AP-05. Requirements section 43 | Snapshot separation is approved. Optional lines are not. Do not invent them | BD-D06, BD-D07, BD-D12 | Finance | | | | | UNSIGNED |
| FY-BOUNDARY | Which month starts and ends each company’s financial year? | Calendar year, company financial year, payroll period, and leave year are different | Stage 2. FY-CONF-001 | January–December, June–May, and March–February are examples, not stored settings. Chosen months require discussion | FY-CONF-001. Does not by itself block employee migration | Finance | | | | | UNSIGNED |
| PUNCH-WITHDRAW | Who may withdraw a raw punch, and what audit record is required? | Immutability is approved. No withdrawal authority was found | Architecture sections 9 and 11 | Options require discussion. Do not delete punches | PUNCH-APPROVED | HR operations | | | | | UNSIGNED |

## 4. Employee migration blockers

A company may be loaded only after all of these exist:

- D1, D2, D3, D4 row dispositions, and D6 signed in Step 20R.
- The exception register signed. It is not built. The 14 duplicate rows, VTL id 437, both-flags rows, and zero dates have no signed disposition.
- Reconciliation executed: source count = loaded + approved exceptions, employees and episodes counted separately.
- A named company approval. Catalog 24, 20, and 10 have tenant rows only.

The draft labels for D1, D3, and D4 do not satisfy this list. Identities must not be merged. Codes must not be silently recoded. Do not assign `inactive` because both flags are 1.

## 5. Attendance and payroll implementation blockers

Behavior must not be built while these stay unsigned:

- BD-D02 incomplete punch outcome and pay effect.
- BD-D03 shift winner.
- BD-D07 absence pay and joiner or leaver pay.
- BD-D09 overtime formula, BD-D11 separate overtime approval, BD-D12 rate, minimum, and maximum.
- BD-D13 attendance cutoff and BD-D14 payroll finalization behavior.
- BD-D16 correction fields and BD-D17 approval steps.
- BD-D18 optional snapshot lines.

BD-D15 stays deferred and blocks a reopen action. Grace minutes of 16 and 31 stay unsigned inside BD-D01 and BD-D05. FY-BOUNDARY blocks financial-year reports, not the payroll-month design.

## 6. Deferred items and evidence gaps

- BD-D15 and PER-CONF-003 stay deferred.
- D5, overlap, date order, and inactive pay have no proposal.
- DB-01 is open until MySQL is already running. Do not start it. Do not mark it complete.
- External punch writer, `area`, and `balid` on non-transfer rows remain uninterpreted.
- No operated joiner or leaver month was counted.
- No CEVA operated month was found.
- Step 20R-1 still says Atlas and CEVA months were not traced. AUD-05 later compared the source actions. CEVA still has no operated month. That agenda line is stale and was not rewritten as history.
- BD-D01, BD-D04, BD-D05, BD-D06, BD-D07, BD-D08, and BD-D10 are open in requirements section 43 and do not have their own Step 20R subsections. The questions above are the section 43 questions.

## 7. Decision capture instructions

After a real meeting:

1. Confirm the register copy being signed.
2. Write the selected option into that ID’s blank “Management's actual decision” field only.
3. Write the approver name and role, the approval date, and any condition into the blank fields for that ID.
4. Leave the proposed-recommendation sentence unchanged.
5. Do not copy `Anisur Rahman` or `2026-10-12` from the unsigned draft unless that person confirms the decision in the meeting and the date is the actual decision date.
6. Do not overwrite AUD-01 through AUD-06, the closed-month audits, or Stage 2.
7. A discussion, a draft label, or a name without a filled approval date is still unsigned.

## 8. Post-meeting action checklist

- [ ] Each decided ID has a selected option, an approver, a date, and any condition in Step 20R.
- [ ] Undecided IDs remain blank.
- [ ] D2 and D6 were not dropped.
- [ ] BD-D07 remains unsigned unless the meeting signed it.
- [ ] BD-D15 remains deferred unless the meeting explicitly owns a reopen rule.
- [ ] FY-BOUNDARY remains unsigned unless evidenced months were signed.
- [ ] No company was marked migration-approved unless a separate company approval was signed.
- [ ] Reconciliation remains unexecuted until a later load step runs it.
- [ ] Matching copies under `legacy-reference\hrms\docs` are updated and hashes compared.

## 9. Statement

This package prepares a meeting. It does not claim the meeting occurred. D1, D3, and D4 remain unsigned. No approval date or signature was written. No database, schema, application code, or migration data was changed.

STAGE 3 PACKAGE PREPARED

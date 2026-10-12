# Universal HRMS — Step 20R Management Decision Register

Formal register for decisions that still require a named approver. A proposal in this file is not an approval. Blank decision, approver, and date fields are unsigned.

No company is approved for employee migration. The three local company records for catalog 24, 20, and 10 are tenant rows only.

Stage 3 meeting worksheet: `docs/UNIVERSAL_HRMS_STAGE3_DECISION_MEETING_PACKAGE.md`. A draft outside this register names D1 `obtain_verified_name`, D3 `verify_individually`, and D4 `case_by_case`, with the text `Anisur Rahman` and the date `2026-10-12`. Conditions were not recorded. That draft is **UNSIGNED**. Those words are not written into the blank decision, approver, or date fields below. They do not authorize an employee load.

Employee counts cited here are offline SQL-dump findings from Step 20G, not a live production census.

## 1. Documents reviewed

- `docs/UNIVERSAL_HRMS_MANAGEMENT_FINDINGS_PRE_DEVELOPMENT_REPORT.md`
- `docs/UNIVERSAL_HRMS_MANAGEMENT_PRESENTATION_OUTLINE.md`
- `docs/UNIVERSAL_HRMS_STEP20H_EMPLOYEE_MIGRATION_BLOCKER_AND_DECISION_REGISTER.md`
- `docs/UNIVERSAL_HRMS_STEP20I_EMPLOYEE_MIGRATION_DECISIONS_AND_EVIDENCE_GAPS.md`
- `docs/UNIVERSAL_HRMS_STEP20J_EMPLOYEE_MIGRATION_DRY_RUN_DESIGN.md`
- `docs/UNIVERSAL_HRMS_STEP20K_EMPLOYEE_MIGRATION_APPROVAL_AND_SOURCE_MANIFEST.md`
- `docs/UNIVERSAL_HRMS_STEP20N_COMPANY_SEED_IMPLEMENTATION_REPORT.md`
- `docs/UNIVERSAL_HRMS_STEP20O_COMPANY_SEED_INTEGRITY_REVIEW.md`
- `docs/UNIVERSAL_HRMS_STEP20_FINAL_EMPLOYEE_CODE_DECISION.md`, as cited by Steps 20I and 20E
- `docs/UNIVERSAL_HRMS_TARGET_TECHNOLOGY_PHYSICAL_IMPLEMENTATION_DECISION.md` section 16, for D01–D18
- `docs/UNIVERSAL_HRMS_ARCHITECTURE_ASSESSMENT.md`, for the approved product shape

No listed file was missing.

## 2. Decisions already in force

These stay approved. This register does not reopen them.

| ID | Decision in force | Source |
| --- | --- | --- |
| ID-APPROVED | The employee primary key is a new ULID. The employee code is not the identity | Employee-code decision. Steps 20C and 20E |
| CODE-APPROVED | Inside one company the employee code is unique on every row, including after separation. Separation does not release the code | Employee-code decision |
| NOMERGE-APPROVED | Do not merge employee identities. Do not silently recode a duplicate employee code | Employee-code decision and Step 20E. This is the approved part of D4 |
| D7-APPROVED | The tenant is catalog 24, 20, or 10. Dump-local ids 5 and 26, and `previous_db_id`, are not the tenant | Workbook and Step 20E. Catalog rows read in Step 20I |
| SEED-DONE | Local `companies` rows exist for those three catalog ids, status `Active`, `company_code` and `source_system` null | Steps 20N and 20O. This is not employee-migration approval |
| RECON-APPROVED | For a real load, source count = loaded + approved exceptions, employees and episodes counted separately. Quarantine is not the right-hand side | Step 20E. Not executed |
| ARCH-APPROVED | One product. Company policy is data. Punch, day result, correction, calculated overtime, payable overtime, paid overtime, and the finalized payroll snapshot stay separate. A finalized snapshot does not follow a later attendance recalculation | Architecture assessment AP-01 through AP-05 |
| PUNCH-APPROVED | A correction does not edit the raw punch. Recalculation writes a new day-result version | Architecture assessment sections 9 and 11 |

## 3. How to read each open item

| Field | Meaning until signed |
| --- | --- |
| Proposed recommendation | **PROPOSED — NOT APPROVED** |
| Management's actual decision | Blank. Discussion is not a decision |
| Approver name/role | Blank |
| Approval date | Blank |
| Conditions or exceptions | Blank |
| Responsible owner | The function named in Step 20Q. Not a named person |
| Due date | Blank |
| Impact | What stays blocked while the item is unsigned |

## 4. Employee migration decisions

### D1 — Display-name composition

| Field | Content |
| --- | --- |
| Question | How do `emp_first_name`, `emp_mid_name`, and `emp_last_name` become `display_name`, and what happens when every part is empty? |
| Business impact | `display_name` is required. VTL employee id 437, code `PAL1234`, has all three parts empty |
| Risk if unresolved | A load invents a name, or stores a blank that only looks non-empty |
| Options supported by the register | A. Drop empty parts, join the rest with one space, hold a row with nothing left. B. Use only the first name. C. Require a reviewer-supplied name on every row. D. Join all three with spaces even when a part is empty |
| Proposed recommendation | **PROPOSED — NOT APPROVED.** Option A, from Step 20I |
| Management's actual decision | |
| Approver name/role | |
| Approval date | |
| Conditions or exceptions | |
| Responsible owner | HR operations |
| Due date | |
| Impact | Development blocked for any employee load. Migration blocked |
| Evidence | Steps 20G, 20H, 20I, and 20K |

### D2 — Legacy zero dates

| Field | Content |
| --- | --- |
| Question | How is MySQL `0000-00-00` treated? |
| Business impact | Joining-date zeros in the dumps: Proedge 1, Atlas 3, VTL 61. Leaving-date zeros: Proedge 1, Atlas 0, VTL 81. The target joining date cannot store that token |
| Risk if unresolved | The zero date is hidden as an ordinary empty date, or a calendar date is invented |
| Options supported by the register | A. Treat it as missing and hold the episode. B. Hold the employee and the episode. C. Keep a distinct zero-date exception, separate from `NULL` |
| Proposed recommendation | **PROPOSED — NOT APPROVED.** Distinct exception. Not a target date. Not ordinary `NULL`. A required episode date stays quarantined. Step 20I |
| Management's actual decision | |
| Approver name/role | |
| Approval date | |
| Conditions or exceptions | |
| Responsible owner | HR operations |
| Due date | |
| Impact | Migration blocked where a zero date would be written. Design of the employee table is not blocked |
| Evidence | Steps 20C, 20G, 20H, 20I, and 20K |

### D3 — Both active and discontinued

| Field | Content |
| --- | --- |
| Question | What episode status is stored when `active` and `disconrinue` are both 1? |
| Business impact | Dump counts: Proedge 19, Atlas 74, VTL 1,186. Atlas id 7175, code `EB9091`, discontinue date 2026-08-25, is in the Atlas set |
| Risk if unresolved | Most of the VTL file receives a guessed status |
| Options supported by the register | A. Quarantine the status. B. Authorize `separated` only where the leaving date is a real calendar date, and quarantine the rest. C. Require a disposition on each row. Assigning `inactive` is not an option |
| Proposed recommendation | **PROPOSED — NOT APPROVED.** Option A. Do not infer `Open`, `inactive`, or `separated`, and do not infer a status from `discontinue_date` alone. Step 20I |
| Management's actual decision | |
| Approver name/role | |
| Approval date | |
| Conditions or exceptions | |
| Responsible owner | HR operations |
| Due date | |
| Impact | Development blocked for episode status on these rows. Migration blocked while any remain quarantined |
| Evidence | Steps 20E, 20G, 20H, 20I, and 20K. September attendance for `EB9091` stays documented only in the third-company audit |

### D4 — Duplicate VTL employee codes

| Field | Content |
| --- | --- |
| Question | What happens to the seven VTL codes that each appear on two rows? |
| Business impact | 14 source rows. Codes `10024`, `15575`, `132058`, `6119`, `10023`, `10027`, `10034`. Each row stays in the source count |
| Risk if unresolved | Attendance or pay is attached to one of the two people by dropping or renumbering the other |
| Options supported by the register | The approved restriction stands: do not merge identities and do not silently recode. The open choice is the hold: quarantine the whole group until each row is explicitly authorized. No document offers merge or recode as an available option |
| Proposed recommendation | **PROPOSED — NOT APPROVED.** Group quarantine until each of the 14 rows has its own authorization. Step 20I. The no-merge and no-recode restriction is already **APPROVED** and is not part of this proposal |
| Management's actual decision | |
| Approver name/role | |
| Approval date | |
| Conditions or exceptions | |
| Responsible owner | HR operations |
| Due date | |
| Impact | Migration blocked for those 14 rows. The prohibition on merge and recode does not block design |
| Evidence | Steps 20E, 20G, 20H, 20I, 20K, and the employee-code decision |

### D6 — Evidence required for an employment episode

| Field | Content |
| --- | --- |
| Question | When may a current legacy employee row create an employment episode? |
| Business impact | No employment-episode table was found. `employment_history` is free text. A department, designation, or location change is not an episode |
| Risk if unresolved | Earlier jobs are invented, or every person is loaded with no employment record |
| Options supported by the register | A. At most one episode when an approved flag pair and a real joining date exist. Do not parse the text. B. Load identities only and hold every episode. C. Parse the text or the service book. C has no approved parser |
| Proposed recommendation | **PROPOSED — NOT APPROVED.** Option A, as recorded in Step 20I. This proposal does not decide overlap, date order, or inactive pay |
| Management's actual decision | |
| Approver name/role | |
| Approval date | |
| Conditions or exceptions | |
| Responsible owner | HR operations |
| Due date | |
| Impact | Migration blocked for episodes. Identity design is not blocked |
| Evidence | Steps 20B, 20E, 20F, 20H, and 20I |

## 5. Matters that stay unresolved

These are not proposals. No recommendation is added here.

### D5 — Meaning of `dis`

| Field | Content |
| --- | --- |
| Question | What does `employees.dis` mean? |
| Business impact | The column is an integer. Stored values in the dumps are `NULL`, `0`, and, on two Proedge rows, `1`. The screen label changes with the company alias. It is not the earn-leave ledger |
| Risk if unresolved | The column is mapped into identity or status without a definition |
| Options supported by the register | None. Step 20I did not find a definition |
| Proposed recommendation | None. **UNRESOLVED.** Keep it out of identity and status |
| Management's actual decision | |
| Approver name/role | |
| Approval date | |
| Conditions or exceptions | |
| Responsible owner | Not assigned. A definition is needed only if a later domain uses the column |
| Due date | |
| Impact | Informational for the employee and episode load |
| Evidence | Steps 20G, 20H, and 20I |

### LIFE-OVERLAP — Episode date-range overlap

| Field | Content |
| --- | --- |
| Question | May two episodes for one employee overlap in calendar dates? |
| Business impact | The implemented table enforces one `Open` episode. It does not enforce non-overlap of other episodes |
| Risk if unresolved | A later second episode can overlap the first |
| Options supported by the register | Leave the check undecided, or approve a check before any load that writes a second episode. Step 20B |
| Proposed recommendation | None. **UNRESOLVED.** Out of the current single-span proposal |
| Management's actual decision | |
| Approver name/role | |
| Approval date | |
| Conditions or exceptions | |
| Responsible owner | HR operations, before any second episode is designed |
| Due date | |
| Impact | Informational for one episode per current row. Development blocked for a second episode |
| Evidence | Steps 20B, 20C, and 20I |

### LIFE-ORDER — Leaving date on or after joining date

| Field | Content |
| --- | --- |
| Question | Must `last_working_date` fall on or after `joining_date`? |
| Business impact | The implemented table does not check date order |
| Risk if unresolved | An episode can end before it starts |
| Options supported by the register | Leave the check undecided, or approve it before a load that needs it. Step 20B |
| Proposed recommendation | None. **UNRESOLVED** |
| Management's actual decision | |
| Approver name/role | |
| Approval date | |
| Conditions or exceptions | |
| Responsible owner | HR operations |
| Due date | |
| Impact | Informational for the current proposal. Development blocked for a rule that rejects reversed dates |
| Evidence | Step 20B |

### LIFE-INACTIVE-PAY — Pay effect of `inactive`

| Field | Content |
| --- | --- |
| Question | What pay result does episode status `inactive` have? |
| Business impact | No pay column exists on the episode. The pipeline must not infer `inactive` from an unclear flag pair |
| Risk if unresolved | `inactive` is used as a pay instruction |
| Options supported by the register | Decide inside D06/D07 before any payroll use. Do not add a column in the employee load. Step 20B |
| Proposed recommendation | None. **UNRESOLVED** |
| Management's actual decision | |
| Approver name/role | |
| Approval date | |
| Conditions or exceptions | |
| Responsible owner | HR operations and Finance |
| Due date | |
| Impact | Informational for identity. Development blocked for payroll interpretation of `inactive` |
| Evidence | Steps 20B and 20H |

## 6. Attendance and payroll decisions

D01–D18 are open in the technology decision. BD-D15 stays deferred. No value is selected. Legacy differences are evidence, not a menu of approved options.

The product shape is already approved: one engine, company policy as data, punches immutable, and the payroll snapshot separate from a later attendance change. The open items are the policy values and the operating rules.

### BD-D02 — Incomplete or missing punch

| Field | Content |
| --- | --- |
| Question | What is the outcome when an in or out punch is missing? |
| Business impact | Proedge and Atlas stored Present plus missing-punch text. Viyellatex stored a missing out as Absent |
| Risk if unresolved | One company's outcome becomes the product rule |
| Options supported by the register | No approved option list. The architecture requires the condition to stay separate from the status. The outcome is configuration |
| Proposed recommendation | **PROPOSED — NOT APPROVED.** Detect the incomplete day. Do not copy either company's outcome as the universal result. The outcome value remains empty until signed |
| Management's actual decision | |
| Approver name/role | |
| Approval date | |
| Conditions or exceptions | |
| Responsible owner | HR operations |
| Due date | |
| Impact | Development blocked for attendance status rules. Migration of historical tokens can still preserve the stored text |
| Evidence | Architecture assessment. Third-company audit. Workbook. Technology decision section 16 |

### BD-D03 — Shift assignment authority

| Field | Content |
| --- | --- |
| Question | When the employee default and the roster differ, which shift wins? |
| Business impact | Proedge August used the employee shift. Viyellatex September used the calendar. The workbook records days where those sources differ |
| Risk if unresolved | Migration picks a winner and writes it as history |
| Options supported by the register | No approved winner. Both candidates may be stored. The winner value stays empty |
| Proposed recommendation | **PROPOSED — NOT APPROVED.** Keep both candidates. Do not decide the winner in a migration |
| Management's actual decision | |
| Approver name/role | |
| Approval date | |
| Conditions or exceptions | |
| Responsible owner | HR operations |
| Due date | |
| Impact | Development blocked for the go-forward winner rule. Historical citation of the shift stored on the day is already allowed |
| Evidence | Architecture assessment. Workbook. Technology decision BD-D03 |

### BD-D09, BD-D11, and BD-D12 — Overtime calculation, approval, and payable amount

| Field | Content |
| --- | --- |
| Question | How is overtime calculated, when is it approved, and what amount is payable? |
| Business impact | Proedge August calculated seconds on 50 rows and paid 6,158.25 across 6 employees. Those are not the same figure. Viyellatex stored hours on 173 correction days while a code path blanks overtime. Atlas September has no calculated overtime and no overtime pay head |
| Risk if unresolved | Hours, an attendance correction, and money are treated as one number |
| Options supported by the register | No formula, rate, or approval rule is approved. The three figures stay in separate stores. `basic/208×2` was a print path for other companies, not a mapped rate |
| Proposed recommendation | **PROPOSED — NOT APPROVED.** Keep calculated, payable, and paid overtime apart. Do not derive a formula from the studied months |
| Management's actual decision | |
| Approver name/role | |
| Approval date | |
| Conditions or exceptions | |
| Responsible owner | HR operations and Finance |
| Due date | |
| Impact | Development blocked for overtime calculation and payment. Loading historical Proedge money as stored money does not require this decision |
| Evidence | Workbook section 13. Architecture assessment. Technology decision BD-D08 through BD-D12. Eligibility and rounding, BD-D08 and BD-D10, stay open with this group |

### BD-D16 and BD-D17 — Correction fields and approval workflow

| Field | Content |
| --- | --- |
| Question | Which attendance fields may a correction change, and what is the approval sequence? |
| Business impact | Studied corrections changed displayed in or out toward the shift boundary. Approvers are names or codes, not user accounts. Boss and grand-boss are not a workflow |
| Risk if unresolved | A correction rewrites status, shift, or pay inputs, and the actor cannot be audited |
| Options supported by the register | The mechanism is approved: original, requested, approved, and final values, and the raw punch is not edited. The field list and the step list are empty |
| Proposed recommendation | **PROPOSED — NOT APPROVED.** Do not seed a field list or a boss step. Wait for a signed list and sequence |
| Management's actual decision | |
| Approver name/role | |
| Approval date | |
| Conditions or exceptions | |
| Responsible owner | HR operations |
| Due date | |
| Impact | Development blocked for the correction form and the approval route. The history mechanism is not blocked |
| Evidence | Architecture assessment sections 11 and 12. Workbook section 12. Technology decision BD-D16 and BD-D17 |

### BD-D13, BD-D14, and BD-D15 — Recalculation, close, and reopen

| Field | Content |
| --- | --- |
| Question | When does attendance stop changing, when does payroll close, and who may reopen? |
| Business impact | Lock tables did not mark the studied months closed. Proedge locks were empty. Viyellatex's last Yes flags were years earlier. Atlas's attendance Yes was January 2025, not September 2026 |
| Risk if unresolved | A month that managers treat as closed can still change, or a finalized snapshot is rewritten |
| Options supported by the register | A Yes flag is not a cutoff or a close. The snapshot rule is already approved: a finalized snapshot does not follow later attendance recalculation. Who may reopen is deferred. No reopen role exists |
| Proposed recommendation | **PROPOSED — NOT APPROVED.** Do not treat legacy lock rows as close events. Do not assign a reopen authority in this register |
| Management's actual decision | |
| Approver name/role | |
| Approval date | |
| Conditions or exceptions | |
| Responsible owner | HR operations for attendance cutoff. Finance for payroll close. Management for reopen authority |
| Due date | |
| Impact | Development blocked for cutoff and reopen behavior. The approved snapshot separation is not blocked |
| Evidence | Architecture assessment. Workbook section 14. Technology decision BD-D13, BD-D14, and BD-D15 |

### PAY-SNAPSHOT — Payroll snapshot and finalization

| Field | Content |
| --- | --- |
| Question | What must a finalized payroll result preserve? |
| Business impact | Studied `employee_salary` rows have amounts and no attendance snapshot. Historical pay cannot be recalculated from a new attendance rule |
| Risk if unresolved | A later attendance change alters pay that was already stored |
| Options supported by the register | The separation is approved architecture. Which optional extra lines appear on the snapshot is BD-D18 and is open |
| Proposed recommendation | **PROPOSED — NOT APPROVED.** For BD-D18 only: do not invent optional snapshot lines. The snapshot separation itself is already **APPROVED** |
| Management's actual decision | |
| Approver name/role | |
| Approval date | |
| Conditions or exceptions | |
| Responsible owner | Finance |
| Due date | |
| Impact | Informational for the approved separation. Development blocked for optional snapshot lines |
| Evidence | Architecture assessment AP-05. Workbook section 14. Technology decision BD-D18 |

### PUNCH-WITHDRAW — Controlled withdrawal of a raw punch

| Field | Content |
| --- | --- |
| Question | Who may withdraw a raw punch, and what audit record is required? |
| Business impact | The punch must remain readable after a correction or a recalculation. A migrated punch is marked accepted. No operating rule names who may later withdraw one |
| Risk if unresolved | Punches are deleted, or withdrawal has no actor and time |
| Options supported by the register | Immutability is approved. No approved withdrawal authority was found |
| Proposed recommendation | **PROPOSED — NOT APPROVED.** Do not delete punches. A withdrawal procedure needs its own signed rule before any screen can withdraw one |
| Management's actual decision | |
| Approver name/role | |
| Approval date | |
| Conditions or exceptions | |
| Responsible owner | HR operations |
| Due date | |
| Impact | Informational for the approved immutability rule. Development blocked for a withdrawal action |
| Evidence | Architecture assessment sections 9 and 11. Workbook punch mapping |

### POLICY-SHAPE — Company policy versus universal rules

| Field | Content |
| --- | --- |
| Question | Which differences are one product rule, and which are company configuration? |
| Business impact | The legacy alias selects the engine, the status words, the shift source, and the payslip |
| Risk if unresolved | Each company receives its own program |
| Options supported by the register | The shape is approved: one product, policy as data, extensions off until enabled. The values inside that policy are the open D01–D18 items |
| Proposed recommendation | None beyond the approved shape. Do not reopen it here |
| Management's actual decision | Already **APPROVED** as architecture. Policy values remain unsigned |
| Approver name/role | Recorded in the architecture assessment, not re-signed here |
| Approval date | Not re-dated here |
| Conditions or exceptions | Client extensions stay off until that company enables them |
| Responsible owner | Product owner for any request to split the engine |
| Due date | |
| Impact | Informational. It does not block design of one engine. It does not fill the open policy values |
| Evidence | Architecture assessment AP-01, AP-02, and AP-03 |

## 6A. FY-BOUNDARY — Company financial-year months

Added in Stage 2. This is not BD-D19. D01–D18 are unchanged.

| Field | Content |
| --- | --- |
| Question | Which month starts and which month ends each company’s financial year? |
| Evidence | AUD-06. Payroll and attendance use the requested calendar month. `leave_financial_year.start_month` is a leave-year field. Hourly leave used a July–June proration in source. January–December, June–May, and March–February are examples. No company setting was read |
| Proposed recommendation | **PROPOSED — NOT APPROVED.** Store a company start month and end month. Do not copy a leave year or a calendar year into that field until an owner signs the months |
| Management's actual decision | |
| Approver name/role | |
| Approval date | |
| Blocks | Financial-year reports. Not the payroll-period design. Not employee migration by itself |

## 7. Management meeting checklist

Use this in the meeting. Write the result into the blank fields above. A verbal agreement is not an approval until the approver name and the date are filled.

1. Confirm this register is the copy being signed, and that Step 20Q is the findings paper.
2. State that Proedge, Viyellatex, and Atlas are not approved for employee migration.
3. State that the three local company records do not approve an employee load.
4. Confirm the already-approved restrictions: no identity merge, no silent recode, employee code is not the identity.
5. Take D1, D3, and D4 first. They block an employee load.
6. Take BD-D02, BD-D03, BD-D09, BD-D11, BD-D16, and BD-D14 next. They block attendance and payroll build.
7. For each item, write the actual decision, the approver, the date, and any exception. Leave the proposed text unchanged.
8. Do not choose `inactive` for both-flags rows.
9. Do not choose a legacy company as the universal attendance standard.
10. Leave D5, overlap, date order, inactive pay, and BD-D15 unsigned unless the meeting has evidence and is willing to own them.
10A. Do not treat January–December, June–May, or March–February as a company’s financial year. FY-BOUNDARY stays blank until months are evidenced and signed.
11. After the meeting, a later step may update only the signed fields. This step does not.

## 8. Decision summary

### Approved

- ULID identity and company-scoped employee code.
- No merge and no silent recode.
- Catalog tenants 24, 20, and 10, with local company rows already seeded.
- Reconciliation contract, not yet executed.
- One product, policy as data.
- Immutable punches, versioned day results, and a finalized payroll snapshot that does not follow later attendance changes.

### Awaiting approval

D1, D2, D3, the D4 group hold, D6, BD-D02, BD-D03, BD-D08 through BD-D14, BD-D16, BD-D17, BD-D18, FY-BOUNDARY, and the punch-withdrawal procedure.

D5, episode overlap, date order, inactive pay, and BD-D15 have no proposal to approve. They remain unresolved or deferred.

### Blocks design or development

- D1 and D3 block an employee load and episode-status development.
- BD-D02, BD-D03, BD-D09, BD-D11, BD-D12, BD-D16, and BD-D17 block attendance and overtime behavior.
- BD-D13 and BD-D14 block cutoff and close behavior.
- BD-D15 blocks a reopen action.
- LIFE-OVERLAP and LIFE-ORDER block a second episode or a date-order check.
- LIFE-INACTIVE-PAY blocks payroll use of `inactive`.
- PUNCH-WITHDRAW blocks a withdrawal action.
- BD-D18 blocks optional snapshot lines.
- BD-D07 blocks absence pay and joiner or leaver proration. AUD-06. It is unsigned even though this older list did not name it.
- FY-BOUNDARY blocks financial-year reports. It does not block the payroll month.

### Blocks migration

- D1, D2, D3, the D4 group hold, and D6.
- The 14 duplicate rows, the empty-name row, and the both-flags rows have no signed dispositions.
- Reconciliation has not been run.
- No company is approved for employee migration.

The approved snapshot rule and the approved no-merge rule do not, by themselves, approve a migration.

## 9. Statement

This register records proposals and blanks. It does not approve D1, D2, D3, D4's group hold, D6, or any attendance or payroll policy value. It does not connect to a database, load employees, or change schema, configuration, or application code.

DECISION REGISTER PREPARED

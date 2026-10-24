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
| Management's actual decision | Blank. Discussion is not a decision. A filled cell that says pending formal registration is still not an approval |
| Approver name/role | Blank, or `NOT PROVIDED` when the owner stated a direction and did not name a person |
| Approval date | Blank, or `NOT PROVIDED` when no date was given |
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

## 6B. AUTH-ARCH — Authorization architecture

One consolidated entry for Section 14 of `docs/UNIVERSAL_HRMS_AUTHORIZATION_PRE_SITTING_DECISION_PACKAGE.md`. It is not a separate entry for each draft or each role category.

Registration status: **APPROVED**, subject to all Section 14 conditions. This is not an approval of the role list. SEC-CORE-003 and SEC-CORE-004 are not satisfied.

| Field | Content |
| --- | --- |
| Question | Whether to accept Section 14 as the authorization architecture |
| Business impact | Membership, roles, permissions, one active company, record checks, a reserved narrower scope, workflow-state checks, audit, and revocation, as one architecture |
| Risk if unresolved | A stated direction is treated as permission to build roles, replace the two flags, or enforce a narrower scope |
| Options supported by the register | Accept Section 14 with every condition in that section, or leave the architecture unsigned |
| Proposed recommendation | **PROPOSED — NOT APPROVED.** Accept Section 14 as one architecture |
| Management's actual decision | **APPROVED**, subject to all Section 14 conditions. The approved text is Section 14, including AUTH-DRAFT-01, AUTH-DRAFT-02, AUTH-DRAFT-03, and AUTH-DRAFT-07 as one architecture |
| Approver name/role | Anisur Rahman, Owner — Universal HRMS Project |
| Approval date | 2026-10-17 |
| Conditions or exceptions | The condition list under this table. This approval waives none of them |
| Responsible owner | Not supplied with this approval |
| Due date | Not supplied with this approval |
| Impact | The architecture is approved. RBAC, permission screens, directory enforcement, flag replacement, narrower scope, attendance, payroll, and migration stay unauthorized. SEC-CORE-003 stays unsigned. The role list is decided in SEC-CORE-004, not in this entry |
| Evidence | Section 14 of the pre-sitting package. Approver name, role, and approval date supplied for this entry |

Conditions that remain in force:

- Stage 5D's original rule remains the implementation rule until the SEC-CORE-004 gate is updated through its own process. There is still no implemented company-administrator role. SEC-CORE-004 remains unimplemented, and this approval does not satisfy that gate.
- `can_manage_employees` and `can_manage_organization_masters` stay as they are. This approval authorizes no permission-to-role mapping.
- The enforced boundary remains the active company. A narrower organizational scope is not implemented and is not enforced.
- Attendance, payroll, migration, role-matrix implementation, permission screens, and directory enforcement stay excluded. This approval does not authorize them.
- No RBAC implementation is authorized until the SEC-CORE-004 gate is updated from this approval through the established process. This entry does not make that update and does not approve the role list. The later SEC-CORE-004 entry makes that gate update and states that the update does not authorize the build.
- SEC-CORE-003 stays unsigned. This approval does not sign it.
- Workflow-state orders, middleware, table names, and permission-screen layout are not chosen.
- BD-D16, BD-D17, PRB-04, DOC-07, DOC-08, and D1–D6 are not decided here.
- Operator name and job, whether another person may set a password, whether account locks exist, the exceptional-access event list, its maximum duration, the independent reviewer's name and job, any further no-assigner evidence, and the no-assigner approver's name and job were not supplied. Those procedures stay blocked. AUTH-REQ-007 is not separately signed.

This approval covers the Section 14 architecture and the conditions above. It does not itself satisfy SEC-CORE-003 or approve the role list. The role list is the separate SEC-CORE-004 entry.

## 6C. SEC-CORE-004 — Initial role list

The register had no earlier SEC-CORE-004 entry. This is that entry. It is not a second copy of AUTH-ARCH.

Registration status: **APPROVED**, subject to the conditions below. This approval does not authorize implementation.

| Field | Content |
| --- | --- |
| Question | What is the initial role list, and which permissions each category holds? |
| Business impact | Four company-scoped categories, with permissions separated from the category names |
| Risk if unresolved | Membership is treated as access to every employee, or one category is treated as a company administrator |
| Options supported by the register | The four-category split stated in the decision below, or a corrected list written by the owner |
| Proposed recommendation | **PROPOSED — NOT APPROVED.** The four categories and permission split prepared for owner review |
| Management's actual decision | **APPROVED**, subject to the conditions below. Accept the four categories and the permission split as the initial role list |
| Approver name/role | Anisur Rahman, Owner — Universal HRMS Project |
| Approval date | 2026-10-17 |
| Conditions or exceptions | The condition list under this table. This approval waives none of them |
| Responsible owner | Not supplied with this approval |
| Due date | Not supplied with this approval |
| Impact | The initial role list is decided. RBAC, permission screens, flag replacement, directory enforcement, narrower scope, attendance, payroll, leave, overtime, and migration stay unauthorized |
| Evidence | The approved AUTH-ARCH entry, Section 14, the SEC-CORE-004 gate, Stage 5D, and the authorization requirements. The owner accepted the four-category proposal and left its unresolved items open |

Approved initial list:

- A category is a named bundle on one company membership. A permission is one action inside that bundle. One membership may hold more than one category. One category does not grant another.
- Self-service employee holds P-ESS-01 only: read that user's own profile when a user-to-employee link already exists for the active company. It does not include another employee's record, either existing flag, membership or role administration, or a self-service write.
- Employee records manager holds P-EMP-01 through P-EMP-04: list and read another employee in the active company, create an employee and the optional first episode, change `display_name`, and create or correct episodes under the rules already in force. It does not include organization-master writes, membership or role administration, payroll, attendance, or a legacy load.
- Organization master manager holds P-ORG-02: create a department or designation, rename it, or set its status to `Active` or `withdrawn`. It does not include employee writes, membership or role administration, branch writes, or assigning an employee to a master.
- Membership and role administrator holds P-MEM-01, P-MEM-02, P-ROLE-01, and P-ROLE-02: add or remove a membership in the active company, set or clear the two existing flags while they remain, assign one confirmed category, and remove one confirmed category under the AUTH-ARCH revocation rule. It does not include employee writes, organization-master writes, creating a company, creating the first membership of a company that has none, payroll, or attendance. It is not a company-administrator role.
- Company membership does not grant P-EMP-01 or P-ESS-01. An authenticated member of the active company may read that company's departments and designations. That read remains P-ORG-01 and is not a grant from the organization-master category.
- No category includes payroll, attendance approval or correction, leave, overtime, period close, migration, subscription or company suspension, a platform-wide grant, a narrower organizational scope, branch writes, employee assignment, product-default administration, password recovery, or the file cabinet.

Conditions that remain in force:

- `can_manage_employees` and `can_manage_organization_masters` stay unchanged until a later tested permission-to-role mapping is separately approved.
- AUTH-ARCH and all of its conditions remain in force.
- SEC-CORE-003 is not approved. No narrower scope is enforced.
- Attendance, payroll, leave, overtime, migration, and approval workflows are not approved. A category is not an approval step.
- This decision does not authorize implementation. The gate records the list and still blocks a role-matrix build.
- These items stay open: the user-to-employee link; which profile fields, if any, an employee may change; the display-name gap between Stage 4B and the display-name slice; operator name and job; whether another person may set a password; whether account locks exist; the exceptional-access event list, duration, and reviewer; any further no-assigner evidence and the no-assigner approver; DOC-08; and a separate signature for AUTH-REQ-007.

## 6D. First-release scope boundary

The register had no earlier first-release scope entry. This is that entry. It is not a change to AUTH-ARCH or SEC-CORE-004.

Registration status: **APPROVED**. This approval establishes the first-release scope boundary only. It does not authorize implementation.

| Field | Content |
| --- | --- |
| Question | What is inside the first release, and what stays on the full roadmap? |
| Business impact | One signed boundary for the shared platform and five catalog modules. The rest of the catalog stays visible and is not removed |
| Risk if unresolved | A design note is treated as approval to build every partially analyzed module, or an unaudited module is treated as rejected |
| Options supported by the register | The six-point statement below, or a revised boundary written by the owner |
| Proposed recommendation | **PROPOSED — NOT APPROVED.** The six-point statement prepared for owner review. The approval is the decision line below |
| Management's actual decision | **APPROVED.** The six points below. SCOPE-01 stays deferred. The complete legacy catalog stays on the full product roadmap |
| Approver name/role | Anisur Rahman, Owner — Universal HRMS Project |
| Approval date | 2026-10-17 |
| Conditions or exceptions | The condition list under this table. This approval waives none of them |
| Responsible owner | Not supplied with this approval |
| Due date | Not supplied with this approval |
| Impact | The first-release scope boundary is decided. No implementation milestone is authorized. Business-rule, security, UAT, migration, and release gates are unchanged |
| Evidence | The owner approved the six-point statement. The approver name and role are the identity already recorded for this owner on sections 6B and 6C. The approval date is the date of this decision, 2026-10-17 |

Approved boundary:

1. The full product roadmap remains 25 modules, 101 submodules, and 416 controllers, together with the requirements text. This decision does not remove a module because it is inactive, client-named, or outside the first release.
2. This first-release boundary is a scope decision. It is not implementation authorization.
3. The boundary is the shared platform already described by the signed identity and company rules, plus catalog modules `organizationsettings`, `pims`, `payroll`, `leave`, and `attendance`. Inside payroll, only Payroll Basic, Overtime, and OT Management are in the boundary. Policy values stay empty until their own decisions are signed. Partial evidence is not a complete specification.
4. SCOPE-01 stays deferred, as listed in Step 21E. The 91 inventory-only submodules, module 23, submodules 36 and 71, and hourly-leave module 30 stay on the roadmap. They are not rejected. Requirements section 4 remains the capability vocabulary. This decision does not add ESS, notifications, the reports directory, client extensions, or migration execution to the first release.
5. Existing business-rule, security, UAT, migration, and implementation gates stay in force. LEAVE-OPENING, LEAVE-CORE-008, BD-D06, BD-D17, and the other unsigned decisions stay unsigned. The SEC-CORE-004 role list stays approved only as a list. The role-matrix build stays excluded. When this scope decision was recorded on 2026-10-17, all seven employee-foundation UAT scenarios were **NOT VERIFIED**. That sentence recorded the status at approval. It is not a later result, and it is not full UAT sign-off. Subsequent execution results are maintained in `UNIVERSAL_HRMS_CONSOLIDATED_EVIDENCE_REGISTER.md`: UAT-01 **PASS**, UAT-02 **defined flag checks PASS**, UAT-03 **PASS**, UAT-04 **PASS**, UAT-05 **PASS**, UAT-06 **PASS**, and UAT-07 **PASS**. UAT-02 does not score the informational role-list sentence and does not approve the role matrix. The UAT-04 display name, the UAT-05 joining date, and the UAT-06 department name are test inputs, not approved rules. Overlap, date order, and inactive pay were not scored. These results are not full UAT sign-off of the unsigned rules and do not authorize an implementation milestone. No company is migration-approved.
6. Accepting this text does not authorize an implementation milestone.

Conditions that remain in force:

- This approval does not authorize implementation and does not change any existing business-rule, security, UAT, migration, or release gate.
- No implementation milestone is authorized by this decision.
- Partial analysis of the five modules is not implementation readiness.
- Payroll Basic, Overtime, and OT Management do not bring the rest of catalog module 3 into the first release.
- AUTH-ARCH and SEC-CORE-004 remain as approved in sections 6B and 6C. This entry does not authorize a role matrix or any other build.

## 6E. G3 — Proposed-position writer

Registration status: **APPROVED**, subject to the conditions below. This approval does not authorize implementation.

| Field | Content |
| --- | --- |
| Question | Who may record or change a proposed or excepted leave position? |
| Management's actual decision | **A.** Authorized writers are Organization HR Managers and Organization Admin users. Scope is only the company and organizational scope each user is authorized to manage. Permitted actions: create and modify proposed or excepted leave-balance records. Prohibited actions: confirmation, approval, and operational use of unapproved balances. Authenticated-user attribution and auditability are required. Existing membership flags are not mapped to leave permissions. Role-matrix functionality and a new permission screen are not introduced. This decision does not authorize implementation. |
| Approver name/role | Anisur Rahman, Owner |
| Approval date | 2026-10-18 |
| Conditions or exceptions | Until SEC-CORE-003 is signed, scope is the active company only. Do not map `can_manage_employees` or `can_manage_organization_masters` to leave permissions. Do not add a membership boolean. Do not build a role matrix or a permission screen. Owner-named writers are stored as company-scoped `leave_position_writers` rows. Active membership is session context, not a leave grant. |

## 6F. G4 — Leave-type catalog

Registration status: **APPROVED**, subject to the conditions below. This approval does not authorize implementation.

| Field | Content |
| --- | --- |
| Question | Whether a later leave-position milestone may include an empty company-scoped type list |
| Management's actual decision | **A.** A later leave-position milestone, if one is ever authorized under G7, should include an empty company-scoped type list (name and status only). This option does not authorize that milestone. |
| Approver name/role | Anisur Rahman, Owner |
| Approval date | 2026-10-18 |
| Conditions or exceptions | No leave types or paid/unpaid defaults are invented or seeded. BD-D06 stays unsigned. |

## 6G. G5 — Confirmation boundary

Registration status: **APPROVED**, subject to the conditions below. This approval does not authorize confirmation.

| Field | Content |
| --- | --- |
| Question | Whether confirmation fields are omitted or stored unused |
| Management's actual decision | **B.** Store confirmation-related fields unused: confirmation empty; confirmed state refused; never readable as an approved balance. |
| Approver name/role | Anisur Rahman, Owner |
| Approval date | 2026-10-18 |
| Conditions or exceptions | No user may confirm a balance. No confirmation workflow is introduced. LEAVE-OPENING stays unsigned. |

## 6H. G6 — Functional design §2 item 6

Registration status: **APPROVED**, subject to the conditions below. This approval does not authorize an engine.

| Field | Content |
| --- | --- |
| Question | Whether “inactive until its own gate is signed” prohibits any engine build, or only operational use |
| Management's actual decision | **A.** Operational use only: approved balance, approval workflow, and load stay blocked until the named section 4 gate. A recording engine still needs a later complete G7 and section 5 milestone. This option does not authorize that engine. |
| Approver name/role | Anisur Rahman, Owner |
| Approval date | 2026-10-18 |
| Conditions or exceptions | LEAVE-OPENING stays unsigned. Approved-balance use, leave booking, and operational leave calculation stay blocked. |

## 6I. G7 — Leave-Position Recording Engine

Registration status: **APPROVED**. This is the named implementation milestone for the bounded L2 recording engine only.

| Field | Content |
| --- | --- |
| Named milestone | Leave-Position Recording Engine |
| Layer | L2 |
| Bounded scope | Record proposed or excepted leave positions and generic movements/reversals only. Include an empty company-scoped leave-type list (name and status only). Store proposed amount, source evidence, stated basis, and period evidence as evidence only. Writer grants are owner-named `leave_position_writers` rows per company. Confirmation columns exist unused and cannot be set. A stored row is never an approved balance. |
| Explicit exclusions | Approved-balance use; confirmation; confirmed load; entitlement, accrual, carry-forward, expiry, or encashment calculations; leave booking or approval workflow; LEAVE-CORE-008; BD-D06; BD-D17; payroll; attendance; assignment; role matrix; flag mapping; new membership boolean; permission screens; company defaults; production migration or release; SEC-CORE-003 narrower scope; hourly-leave module 30. |
| Required dependencies | G1; G2; G3 Option A; G4 Option A; G5 Option B; G6 Option A; existing login and active-company isolation |
| Gates that remain deferred | LEAVE-OPENING; LEAVE-CORE-008; BD-D06; BD-D17; FY-BOUNDARY; SEC-CORE-003; D1–D6; SCOPE-01; role-matrix build |
| Management's actual decision | **Authorize** the named L2 Leave-Position Recording Engine only, within the bounded scope and exclusions above. |
| Approver name/role | Anisur Rahman, Owner |
| Approval date | 2026-10-18 |
| Conditions or exceptions | This G7 authorizes only the bounded L2 engine. Empty slots stay empty. Period evidence is not an operational leave year. Writer identification uses the owner-named company-scoped list, not membership flags. |

Acceptance criteria recorded with this entry: company isolation; writer-list enforcement without using the two membership flags; audit of actor and time; proposed/excepted status only; confirmation refused; no approved-balance read path; regression of existing authentication and membership behavior.

## 6J. G7-UI — Leave-Position Recording Operator UI

Registration status: **APPROVED**. This is the named implementation milestone for the bounded L2 Operator UI only. It does not overwrite section 6I.

| Field | Content |
| --- | --- |
| Named milestone | Leave-Position Recording Operator UI |
| Layer | L2 |
| Bounded scope | Company-scoped screens for the already-built L2 recording engine only: leave-type list/create/rename/status (name and status only); proposed/excepted leave-position list/create/update; movement list/create, including reversals that add a new row. Reuse existing login, active-company context, `leave_position_writers` grant, JSON APIs, confirmation refusal, `usable_as_approved_balance=false`, and audit. Follow the existing `/session` RealSession pattern used for department and designation masters. No new schema, flags, or permission model. |
| Explicit exclusions | Approved-balance use or display as a usable balance; confirmation controls; confirmed load; booking/approval workflow; accrual, carry-forward, expiry, encashment; payroll; attendance; assignment; role matrix; flag mapping; new membership boolean; permission screens; writer-grant UI; company-specific defaults; migration or production release; preview/fixture `/session` data paths; Blade rebuild of the engine. |
| Required dependencies | Signed G1–G6; signed G7 Leave-Position Recording Engine (Step 20R §6I); existing authentication, company context, and writer grant. |
| Gates that remain deferred | LEAVE-OPENING; LEAVE-CORE-008; BD-D06; BD-D17; FY-BOUNDARY; SEC-CORE-003; D1–D6; SCOPE-01; role-matrix build. |
| Management's actual decision | Authorize the named L2 Operator UI only. |
| Approver name/role | Anisur Rahman / Product Owner/CTO |
| Approval date | 2026-10-18 |
| Conditions or exceptions | This G7-UI authorizes only the named Operator UI. It does not change the signed G7 engine milestone in section 6I. Writer grants remain CLI-only. |

## 6K. Immutable Punch Recording Engine

Registration status: **APPROVED.** This is the named implementation milestone for the bounded L2 Immutable Punch Recording Engine only. It does not overwrite G1–G6, section 6I, or section 6J. It is not implemented by this documentation change. PUNCH-APPROVED is the immutability rule named in the dependencies below. It is not this authorization. The leave-position approvals are not this authorization.

| Field | Content |
| --- | --- |
| Named milestone | Immutable Punch Recording Engine |
| Layer | L2 |
| Bounded scope | Record raw punch events for one employee inside the active company, through one engine with two ingestion paths. Path A is an authorized human operator: manual entry of one raw event, and upload of a raw punch file. Path B is device integration: a controlled submission of raw punch events from an attendance device or an integration service. Both paths store the same event. Each accepted event stores event time, ingestion time, punch identity, and a named source. The named source is not the submitting actor. A human who uploads a device export remains the submitting actor; the file’s source stays the named source. A device or integration service that submits directly is a different submitting actor from that human. The original event stays readable. Later processing must not rewrite it. The engine detects a duplicate event. It rejects a punch identity that is active for two employees in the same company at the same time unless a documented overlap is already recorded. The overlap list stays empty. Company comes from an explicit company context, not from an empty company field and not from legacy `area`. Writer authorization is an explicit server check. It is not a new membership boolean and not a role-matrix change. |
| Explicit exclusions | Daily attendance calculation or status. Grace, lateness, shift selection, cutoff, and worked-day calculation. A day-result row. Punch correction, editing, withdrawal, or deletion. Leave booking, approved balances, accrual, and encashment. Payroll integration and payroll calculation. Employee assignment changes. Production migration or release. A new role matrix, a new membership flag, permission screens, and flag replacement. Naming a vendor, a device protocol, a source-name catalog, a duplicate key, or an overlap for cards `126384` and `132058`. Copying a legacy file layout, `area`, or `balid` into the product rule. |
| Required dependencies | Signed G1 and G2, as documentation rules only. They do not authorize this engine. Existing login and server-selected active company. Employee identity inside that company. PUNCH-CORE-001 through PUNCH-CORE-009, PUNCH-CORE-006, PUNCH-CORE-007, DI-CORE-005, and PUNCH-APPROVED, as the shape and the immutability rule only. SEC-CORE-001 for a state-changing action. AUDIT-CORE-001 for the submitting actor and time on an accepted event. NFR-CORE-006 and NFR-CORE-007 for a failed import and a replaceable external feed. |
| Unresolved prerequisites | The human writer is not named. The device or integration credential is not named. Functional design §3.6 names no attendance user and no SEC-CORE-004 category for attendance. `can_manage_employees` and `can_manage_organization_masters` do not grant this engine. The duplicate match fields are not defined. PUNCH-CONF-002, how a detected duplicate is treated, is unsigned. PUNCH-CONF-001, which sources a company accepts, is unsigned. PUNCH-CONF-004, whether one employee may hold multiple active punch identities, is unsigned. The overlap list is empty. No device protocol is identified. EXT-CLIENT-004 stays inactive until a protocol is identified. AUD-04: the external writer is UNKNOWN, `area` is UNKNOWN, and no in-repository device API was found. |
| Gates that remain deferred | BD-D01, BD-D02, BD-D03, BD-D04, BD-D05, BD-D13, BD-D16, PUNCH-WITHDRAW, SEC-CORE-003, FY-BOUNDARY, SCOPE-01, D1–D6, LEAVE-OPENING, and the role-matrix build. ATT-CORE-001 stays outside this milestone because it is day calculation. |
| Management's actual decision | Authorize the bounded Immutable Punch Recording Engine (L2) milestone only. Preserve all unresolved dependencies and exclusions. |
| Approver name/role | Anisur Rahman / Product Owner/CTO |
| Approval date | 2026-10-18 |
| Conditions or exceptions | This entry authorizes only the named L2 engine. It does not change G1–G6, Step 20R §6I, or Step 20R §6J. It does not sign PUNCH-WITHDRAW or any other unsigned attendance policy. The unresolved prerequisites above stay unresolved. |

**Ingestion paths.** Path A and path B are the two ingestion paths accepted with this milestone. They are not built.

| Path | What the documents support | What stays a dependency |
| --- | --- | --- |
| A. Authorized human operator | An ingestion boundary before calculation (PUNCH-CORE-001). A person may enter one event or submit a file of raw events. The signed-in user is the submitting actor. The named source is a separate stored fact (PUNCH-CORE-003). | Who that person is. The file layout. Which sources the company accepts (PUNCH-CONF-001). The legacy bracket-delimited upload is evidence from AUD-04. It is not the product format. |
| B. Device or integration service | An external feed stays replaceable behind that same boundary (NFR-CORE-007). A named protocol may be enabled only after it is identified (EXT-CLIENT-004). The device or service is the submitting actor. The named source remains distinct from that actor. | The protocol, the vendor, the credential, and how that caller is bound to one company. AUD-04 did not find a device client in the repository. `area` is not a source and is not a company. |

Both paths store event time and ingestion time as different facts (PUNCH-CORE-006). Neither path may assign the company from a blank company field (PUNCH-CORE-007).

**Identity and duplicates.**

- A punch identity maps to an employee inside one company. The identity may differ from the employee code (PUNCH-CORE-008).
- The same punch identity must not be active for two employees in that company at the same time unless a documented overlap is already recorded (PUNCH-CORE-009, DI-CORE-005). No overlap is recorded. Cards `126384` and `132058` are not decided here.
- A duplicate event must be detected (PUNCH-CORE-005). The fields that make two events the same event are not specified. PUNCH-CONF-002 leaves the treatment configurable and unsigned. This milestone does not choose a key, and it does not choose reject, quarantine, or skip. A failed import must not complete as a partial silent write (NFR-CORE-006).
- This milestone does not choose how many active identities one employee may hold (PUNCH-CONF-004).

**Immutability.** PUNCH-APPROVED and PUNCH-CORE-004 keep an accepted event. A later day result, if a later milestone is signed, must be a separate record and must not rewrite the event (ATT-CORE-002 read with functional design §3.6). This milestone does not create that day result. ATT-CORE-001 is calculation and stays out.

PUNCH-WITHDRAW is unsigned. This milestone includes no withdrawal, deletion, or edit. BD-D16’s correction mechanism does not include the raw punch as an editable field. The field list itself stays empty.

**Writer prerequisite.** No writer is named by this approval. Before implementation of a path, the owner must name both of the following, or must name one path and defer the other:

1. The human operator allowed to enter or upload raw punches for one company.
2. The device or integration credential allowed to submit raw punches for one company.

Those two actors are not the same fact as the named source. Naming either one is not a new role, not a membership boolean, and not a mapping of the two existing flags. The leave-position writer list is a different milestone. It is not reused here.

This entry does not sign BD-D01 through BD-D05, BD-D13, BD-D16, PUNCH-WITHDRAW, SEC-CORE-003, FY-BOUNDARY, SCOPE-01, D1–D6, or LEAVE-OPENING. It does not reopen AUD-01 through AUD-06. It does not start MySQL. No company is migration-approved.

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
12. AUTH-ARCH is approved subject to all Section 14 conditions. SEC-CORE-004 is approved as the initial role list, subject to its own conditions. Do not build a role matrix, replace the two flags, or enforce a narrower scope from either entry. SEC-CORE-003 is not satisfied.
13. The first-release scope boundary in section 6D is approved. Do not treat that approval as an implementation milestone. SCOPE-01 stays deferred.

## 8. Decision summary

### Approved

- ULID identity and company-scoped employee code.
- No merge and no silent recode.
- Catalog tenants 24, 20, and 10, with local company rows already seeded.
- Reconciliation contract, not yet executed.
- One product, policy as data.
- Immutable punches, versioned day results, and a finalized payroll snapshot that does not follow later attendance changes.
- AUTH-ARCH, the Section 14 authorization architecture, approved 2026-10-17 by Anisur Rahman, Owner — Universal HRMS Project, subject to all Section 14 conditions. AUTH-ARCH does not itself approve the role list or satisfy SEC-CORE-003.
- SEC-CORE-004, the initial four-category role list and permission split, approved 2026-10-17 by Anisur Rahman, Owner — Universal HRMS Project, subject to the conditions in section 6C. This does not authorize implementation. SEC-CORE-003 is not satisfied.
- The first-release scope boundary in section 6D, approved 2026-10-17 by Anisur Rahman, Owner — Universal HRMS Project. The shared platform plus `organizationsettings`, `pims`, `payroll`, `leave`, and `attendance`, with only Payroll Basic, Overtime, and OT Management inside payroll. SCOPE-01 stays deferred. The full catalog stays on the roadmap. This does not authorize implementation.
- G3 Option A, G4 Option A, G5 Option B, and G6 Option A, approved 2026-10-18 by Anisur Rahman, Owner. Those entries do not authorize implementation.
- G7 Leave-Position Recording Engine (L2), approved 2026-10-18 by Anisur Rahman, Owner. This is the named bounded recording milestone only. It does not sign LEAVE-OPENING or authorize approved-balance use, booking, payroll, attendance, migration, or release.
- G7-UI Leave-Position Recording Operator UI (L2), approved 2026-10-18 by Anisur Rahman / Product Owner/CTO. This authorizes only the named Operator UI. It does not overwrite section 6I and does not sign LEAVE-OPENING.
- Immutable Punch Recording Engine (L2), approved 2026-10-18 by Anisur Rahman / Product Owner/CTO. This authorizes only the bounded engine in section 6K. It does not overwrite sections 6I or 6J. It does not sign PUNCH-WITHDRAW or any other unsigned attendance policy. The unresolved prerequisites in section 6K stay unresolved. It is not implemented by this documentation change.

### Awaiting approval

D1, D2, D3, the D4 group hold, D6, BD-D01, BD-D02, BD-D03, BD-D04, BD-D05, BD-D06, BD-D08 through BD-D14, BD-D16, BD-D17, BD-D18, FY-BOUNDARY, LEAVE-OPENING, and the punch-withdrawal procedure.

BD-D01, BD-D04, BD-D05, and BD-D06 stay unsigned. No grace value, late-minute starting point, early-out pay rule, or paid-leave list is selected.

LEAVE-OPENING asks which opening figure is accepted, which leave period is operational, how adjustments and encashment are reconciled, and who confirms a balance before approval. The 2026-10-17 dumps do not answer it. No formula is proposed here.

D5, episode overlap, date order, inactive pay, and BD-D15 have no proposal to approve. They remain unresolved or deferred.

### Blocks design or development

- D1 and D3 block an employee load and episode-status development.
- BD-D01 blocks attendance grace. No minute value is approved.
- BD-D04 blocks the late-minute starting point. No second threshold is approved.
- BD-D05 blocks early-out pay. No deduction is approved.
- BD-D06 blocks which leave types are paid. No paid, unpaid, or partial list is approved.
- BD-D02, BD-D03, BD-D09, BD-D11, BD-D12, BD-D16, and BD-D17 block attendance and overtime behavior.
- BD-D13 and BD-D14 block cutoff and close behavior.
- BD-D15 blocks a reopen action.
- LIFE-OVERLAP and LIFE-ORDER block a second episode or a date-order check.
- LIFE-INACTIVE-PAY blocks payroll use of `inactive`.
- PUNCH-WITHDRAW blocks a withdrawal action.
- BD-D18 blocks optional snapshot lines.
- BD-D07 blocks absence pay and joiner or leaver proration. AUD-06. It is unsigned even though this older list did not name it.
- FY-BOUNDARY blocks financial-year reports. It does not block the payroll month.
- LEAVE-OPENING blocks an approved leave balance and a load that would mark a legacy balance confirmed. It does not block recording a proposed position. It does not choose `earned` or `prev_bal`.
- AUTH-ARCH and SEC-CORE-004 are approved and still do not authorize RBAC, a role matrix, permission screens, flag replacement, directory enforcement, or a narrower scope. The role list is decided. The build is not.
- Section 6D approves the first-release scope boundary only. It does not itself authorize an implementation milestone. Section 6I authorizes only the bounded L2 Leave-Position Recording Engine. Section 6J authorizes only the bounded L2 Leave-Position Recording Operator UI. Section 6K authorizes only raw punch recording inside its bounded scope. It does not lift BD-D01, BD-D02, BD-D03, BD-D04, BD-D05, BD-D13, BD-D16, or PUNCH-WITHDRAW.

### Blocks migration

- D1, D2, D3, the D4 group hold, and D6.
- The 14 duplicate rows, the empty-name row, and the both-flags rows have no signed dispositions.
- Reconciliation has not been run.
- No company is approved for employee migration.
- A leave balance whose opening figure, reconciliation, or confirmer is unresolved. Copying the stored amount is not that approval.

The approved snapshot rule and the approved no-merge rule do not, by themselves, approve a migration.

## 9. Statement

This register records proposals and blanks except where a named approver and an approval date are written. It does not approve D1, D2, D3, D4's group hold, D6, LEAVE-OPENING, or any attendance or payroll policy value. LEAVE-OPENING has no approver and no approval date. AUTH-ARCH is approved subject to all Section 14 conditions. SEC-CORE-004 is approved as the initial role list, subject to the conditions in section 6C. Neither approval satisfies SEC-CORE-003. Section 6D approves the first-release scope boundary only. Section 6I authorizes only the bounded L2 Leave-Position Recording Engine. Section 6J authorizes only the bounded L2 Leave-Position Recording Operator UI. Section 6J does not authorize approved-balance use, booking, payroll, attendance, migration, or release. Section 6K authorizes only the bounded L2 Immutable Punch Recording Engine. It preserves the unresolved prerequisites and exclusions in that section. It does not sign PUNCH-WITHDRAW or any other unsigned attendance policy. It is not implemented by this documentation change.

DECISION REGISTER PREPARED

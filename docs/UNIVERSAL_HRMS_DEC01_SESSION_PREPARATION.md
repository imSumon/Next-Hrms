# Universal HRMS — DEC-01 Session Preparation

Preparation date: 2026-10-12

Status: **SESSION PAPER PREPARED**. The meeting has not occurred. No decision field was filled. No policy was signed. No company was approved for migration.

This paper organizes the existing capture forms. It does not replace them.

| Role | File |
| --- | --- |
| Official register | `docs/UNIVERSAL_HRMS_STEP20R_MANAGEMENT_DECISION_REGISTER.md` |
| Capture blocks | `docs/UNIVERSAL_HRMS_STEP20R_1_MANAGEMENT_REVIEW_AGENDA_AND_DECISION_CAPTURE.md` |
| Worksheet already prepared | `docs/UNIVERSAL_HRMS_STAGE3_DECISION_MEETING_PACKAGE.md` |
| Lifecycle labels | `docs/UNIVERSAL_HRMS_STEP20B_EMPLOYEE_LIFECYCLE_DESIGN_REVIEW.md` section 5 |
| This session paper | `docs/UNIVERSAL_HRMS_DEC01_SESSION_PREPARATION.md` |

Matching copies live under `legacy-reference\hrms\docs`. Step 20R, Step 20R-1, and the Stage 3 package were left unchanged. Their decision, approver, and date fields stay blank.

After the meeting, write a signed outcome only into the blank fields of Step 20R, and into the matching Step 20R-1 capture block. Leave the proposed-recommendation sentence unchanged. An item the meeting does not decide stays blank. A deferral is written only when the meeting explicitly defers that item.

The external draft that names D1 `obtain_verified_name`, D3 `verify_individually`, and D4 `case_by_case`, with the text `Anisur Rahman` and the date `2026-10-12`, stays unsigned. Those words are not a signature and are not copied into the register by this paper.

## 1. Meeting record

| Field | Entry |
| --- | --- |
| Meeting date | |
| Chairperson | |
| Attendees | |
| Minutes reference | |
| Register copy used | `docs/UNIVERSAL_HRMS_STEP20R_MANAGEMENT_DECISION_REGISTER.md` |

## 2. Previously approved — read, do not reopen

Step 20R section 2, still in force:

| ID | Decision in force |
| --- | --- |
| ID-APPROVED | Employee primary key is a new ULID. The employee code is not the identity |
| CODE-APPROVED | Inside one company the employee code is unique on every row, including after separation. Separation does not release the code |
| NOMERGE-APPROVED | Do not merge employee identities. Do not silently recode a duplicate employee code |
| D7-APPROVED | The tenant is catalog 24, 20, or 10. Dump-local ids 5 and 26 are not the tenant |
| SEED-DONE | Local company rows exist for those three catalog ids. This is not employee-migration approval |
| RECON-APPROVED | For a real load, source count = loaded + approved exceptions. Not executed |
| ARCH-APPROVED | One product. Company policy is data. Punch, day result, correction, calculated overtime, payable overtime, paid overtime, and the finalized payroll snapshot stay separate |
| PUNCH-APPROVED | A correction does not edit the raw punch. Recalculation writes a new day-result version |

Step 20B section 5, also in force, and repeated by LIFE-CORE-008:

| Rule | Label |
| --- | --- |
| Rejoin uses the same `employees.id` and the same `employee_code`, and inserts a new episode | **APPROVED** |
| The previous episode remains | **APPROVED** |
| Separation keeps the `employees` row and `employee_code` | **APPROVED** |
| One Open episode per employee, and an Open episode has an empty last working date | **APPROVED** |

Reopen this rejoin rule only if an authorized owner explicitly revises it in the meeting and the revision is written into the register with an approver and a date. This paper does not revise it.

LIFE-CORE-006 is an approved requirement that an inactive or suspended state stops attendance processing and pay according to company policy. The pay result of `inactive` remains the undecided item LIFE-INACTIVE-PAY. The requirement does not choose an amount.

## 3. Discrepancy recorded for later correction

`EmploymentEpisodeRegistry` refuses to set an existing non-Open episode back to `Open` and uses this message:

`This episode cannot be set back to Open. Whether a separated or inactive episode is reopened, or a new episode is created, is not approved.`

The edit screen says: `This episode cannot be set back to Open.`

The refusal to rewrite the old row matches Step 20B. The sentence that the choice is not approved does not. The Stage 4C report and the control center checkpoint before this paper repeated that unsigned framing. Code was not changed in this preparation. Correct the message in a later development step. Do not treat the message as a reversal of LIFE-CORE-008.

Step 21E section 2 still says the requirements file has 166 IDs and has no parent `docs` copy. The control center and the file on disk record 167 IDs and the parent copy. That audit-plan line was not rewritten.

## 4. Decisions that need an answer in this session

Use the Stage 3 worksheet row and the Step 20R-1 capture block for each ID below. Options are copied from those documents. A proposed recommendation is not selected.

### LIFE-OVERLAP

| Field | Content |
| --- | --- |
| Statement | May two episodes for one employee overlap in calendar dates? |
| Options in the register | Leave the check undecided, or approve a check before any load that writes a second episode. No recommendation |
| Impact while unsigned | Step 20R: informational for one episode per current row. Development blocked for a second episode |
| Owner if documented | HR operations, before any second episode is designed |
| Status now | UNRESOLVED. Capture fields blank |

### LIFE-ORDER

| Field | Content |
| --- | --- |
| Statement | Must `last_working_date` fall on or after `joining_date`? |
| Options in the register | Leave the check undecided, or approve it before a load that needs it. No recommendation |
| Impact while unsigned | Development blocked for a rule that rejects reversed dates |
| Owner if documented | HR operations |
| Status now | UNRESOLVED. Capture fields blank |

### LIFE-INACTIVE-PAY

| Field | Content |
| --- | --- |
| Statement | What pay result does episode status `inactive` have? |
| Options in the register | Decide inside D06/D07 before any payroll use. Do not add a column in the employee load. No recommendation |
| Impact while unsigned | Informational for identity. Development blocked for payroll interpretation of `inactive` |
| Owner if documented | HR operations and Finance |
| Status now | UNRESOLVED. Capture fields blank |

### Employee migration items already on the Stage 3 worksheet

| ID | Exact question in Step 20R | Options already written | Impact while unsigned | Status now |
| --- | --- | --- | --- | --- |
| D1 | How do `emp_first_name`, `emp_mid_name`, and `emp_last_name` become `display_name`, and what happens when every part is empty? | A drop empty parts and hold an empty result. B first name only. C reviewer-supplied name on every row. D join all three even when a part is empty. Proposal is A | Employee load and migration | UNSIGNED |
| D2 | How is MySQL `0000-00-00` treated? | A missing, hold the episode. B hold employee and episode. C distinct zero-date exception, not `NULL`. Proposal is the distinct exception | Episode dates on a load | UNSIGNED |
| D3 | What episode status is stored when `active` and `disconrinue` are both 1? | A quarantine the status. B `separated` only with a real leaving date, quarantine the rest. C a disposition on each row. `inactive` is not an option. Proposal is A | Episode status on a load | UNSIGNED |
| D4 | What happens to the seven VTL codes that each appear on two rows? | The approved rule is no merge and no silent recode. Open hold: quarantine the group until each row is authorized | Migration of those 14 rows | UNSIGNED |
| D6 | When may a current legacy employee row create an employment episode? | A at most one episode with an approved flag pair and a real joining date. B identities only, hold every episode. C parse text. Register proposal is A. A later session proposal is recorded below and is also unsigned | Creating episodes from a load. Overlap, date order, and inactive pay stay separate | UNSIGNED |

#### D6 session proposal

This text was supplied for the session. It is not option A, B, or C. Step 20R still shows Option A as the earlier recommendation. Management's actual decision, the approver, and the approval date in Step 20R and Step 20R-1 stay blank.

**Proposed decision:** HR must verify both the employee's joining date and employment status before a legacy employee record can be used to create an employment episode in the new HRMS.

Rules, in the words supplied:

1. The joining date must be verified using reliable evidence.
2. The employment status must be verified.
3. If either item is missing, unclear, or contradictory, the record must be held for HR review.
4. No employment episode should be created from an unverified record.
5. The review and any correction must be traceable.

**Migration treatment:** Hold records that do not satisfy these requirements until HR completes verification.

| Field | Entry |
| --- | --- |
| Approval status | PROPOSED — NOT APPROVED. Awaiting authorized management approval |
| Approver name and role | Not recorded |
| Decision date | Not recorded |
| Conditions or exceptions | Not recorded |
| Follow-up | The meeting writes this text into Step 20R only if an authorized owner adopts it |

The proposal leaves these meanings for the owner to state. They are not defined in Step 20R:

- What document or fact counts as reliable evidence of the joining date.
- Which status is verified. The legacy pair `active` and `disconrinue` is D3 and stays unsigned. Stored episode statuses remain `Open`, `inactive`, and `separated`.
- What record makes the review and any correction traceable.

Suggested order inside this group, from Step 20R-1: D1, D3, D4, then D2 and D6.

### Attendance and payroll items already on the Stage 3 worksheet

These need an answer before the behavior they name is built. The Stage 3 worksheet holds the exact question and states that the studied company figures are not an approved option. Step 20R gives a proposed recommendation only for the grouped subsections BD-D02, BD-D03, BD-D09/11/12, BD-D16/17, BD-D13/14/15, PAY-SNAPSHOT, PUNCH-WITHDRAW, and FY-BOUNDARY. BD-D01, BD-D04, BD-D05, BD-D06, BD-D07, BD-D08, and BD-D10 have questions in requirements section 43 and have no own Step 20R subsection.

| ID | What stays blocked |
| --- | --- |
| BD-D01 | A grace value. 16 minutes and 31 minutes stay evidence |
| BD-D02 | The outcome when an in or out punch is missing |
| BD-D03 | Which shift wins when the employee default and the roster differ |
| BD-D04 | From when late minutes are counted |
| BD-D05 | What early out is, and whether it affects pay |
| BD-D06 | Which leave types are paid |
| BD-D07 | Whether absence reduces pay, and joiner or leaver proration |
| BD-D08 | Who may earn overtime |
| BD-D09 | How overtime is calculated |
| BD-D10 | How overtime rounds |
| BD-D11 | Whether overtime needs its own approval |
| BD-D12 | What is paid, and at what rate, minimum, and maximum |
| BD-D13 | Whether attendance is frozen, and the cutoff |
| BD-D14 | Whether payroll is frozen after generation |
| BD-D16 | Which fields a correction may change |
| BD-D17 | The approval step list |
| BD-D18 | Which optional facts are snapshotted. Snapshot separation itself is already approved |
| FY-BOUNDARY | Which month starts and ends each company’s financial year. January–December, June–May, and March–February are examples |
| PUNCH-WITHDRAW | Who may withdraw a raw punch, and what audit record is required. Punch immutability is already approved |

## 5. Items the meeting may explicitly defer

Deferral is a meeting act. This paper does not defer anything new.

| Item | Current label | What a continued deferral leaves blocked |
| --- | --- | --- |
| BD-D15 | DEFERRED in the register and in requirements | A reopen action for a closed attendance or payroll period |
| PER-CONF-003 | DEFERRED | That configurable item stays out of the first-release build |
| D5 | UNRESOLVED. No definition was found | Stays out of identity and status until a definition exists |
| SCOPE-01 | Deferred in Step 21E until an owner adds a module | Recruitment, PMS, provident fund, gratuity, and the other inventory-only groups stay out of the first release |
| DB-01 | Open until MySQL is already running | Live catalog comparison. The meeting does not start MySQL |

## 6. Items that need clarification before a schema or a rule

These have no decision ID and no option list in Step 20R. Do not invent an ID, a column, or a default in the meeting notes unless the owner states the decision in words that can be copied into the register afterward.

### Inactive episode and last working date

Step 20B section 5: “Whether `inactive` may store `last_working_date`” is **UNDECIDED**. The empty-date rule is written for `Open` only. Section 7 repeats the question and says it is not a reason to add a column.

The Stage 3 worksheet has no row for this question. No options are written.

| Field | Entry |
| --- | --- |
| Decision text, in the owner’s words | |
| Conditions or exceptions | |
| Approver name and role | |
| Decision date | |
| Status after the meeting | |
| Follow-up and owner | |

Impact: an edit rule that requires or forbids `last_working_date` on `inactive` waits for this answer. The current screen stores the date when one is supplied and does not treat that as a pay rule.

### Employee assignments

Requirements, still without a table design:

| ID | Wording |
| --- | --- |
| EMP-CORE-004 | The system shall store employment attributes that affect processing as effective-dated facts. Notes name department, designation, group, location, and shift default |
| LIFE-CORE-002 | The system shall record a transfer of location as an effective-dated change |
| LIFE-CORE-003 | The system shall record a designation change as an effective-dated change |
| LIFE-CORE-004 | The system shall record a department change as an effective-dated change |
| LIFE-CORE-005 | The system shall record a shift change as an effective-dated change |

Step 20B places department, designation, group, branch, and location on `employment_assignments` and marks that table out of scope. Step 20C names `employment_assignments` as the next schema candidate when instructed. Neither document lists columns, nullability, or foreign keys. `location_nodes` and `organization_nodes` exist as foundation tables. They are not defined as the assignment itself.

Questions for the owner, with no prepared options:

1. Is an assignment table authorized now?
2. If it is, which facts does one row store, and which existing table does each fact reference?
3. What does “effective-dated” mean for the start and end of a row?

| Field | Entry |
| --- | --- |
| Decision text, in the owner’s words | |
| Conditions or exceptions | |
| Approver name and role | |
| Decision date | |
| Status after the meeting | |
| Follow-up and owner | |

Impact: assignment screens, assignment migrations, and any payroll or attendance use of department, location, or shift stay unbuilt until this text exists. A blank answer leaves Step 20C’s candidate uninstructed.

## 7. Suggested session order

1. Read section 2. Confirm the rejoin rule stays in force unless an owner revises it on the record.
2. LIFE-OVERLAP, LIFE-ORDER, and LIFE-INACTIVE-PAY.
3. The two clarification items in section 6.
4. D1, D3, D4, D2, and D6, using the Stage 3 worksheet.
5. The attendance and payroll IDs the chair chooses to take in this sitting. The rest stay blank.
6. State any explicit deferral. BD-D15 stays deferred unless the meeting owns a reopen rule.

## 8. After the meeting

1. Copy only signed values into Step 20R and the matching Step 20R-1 block.
2. Update both control-center copies with the actual outcomes.
3. Leave unsigned IDs blank.
4. Correct the Stage 4C validation message only in a later code change, after this paper’s rejoin statement is still the rule the owner left in force.
5. Build `employment_assignments` only if section 6 contains an authorized column list.
6. Do not import employees, start MySQL, or mark a company migration-approved from this paper.

## 9. Statement

No application code, migration, database, or environment file was changed. AUD-01 through AUD-06 were not repeated. The approved rejoin rule was not revised. No React template was selected.

DEC-01 SESSION PAPER PREPARED

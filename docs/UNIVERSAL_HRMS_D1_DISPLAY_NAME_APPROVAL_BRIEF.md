# Universal HRMS — D1 Display-Name Approval Brief

Preparation date: 2026-10-15

Status: **UNSIGNED**. This brief places the existing D1 proposal in front of the approver. It does not approve D1. It does not fill a decision, approver, date, or signature field. It does not change Step 20R, Step 20R-1, the classifier, the configuration, or the Step 20N rehearsal report.

Evidence and the component-class counts are in `docs/UNIVERSAL_HRMS_D1_DISPLAY_NAME_DECISION_PREPARATION.md`. That paper is also preparation only. The register remains `docs/UNIVERSAL_HRMS_STEP20R_MANAGEMENT_DECISION_REGISTER.md`. The capture block remains `docs/UNIVERSAL_HRMS_STEP20R_1_MANAGEMENT_REVIEW_AGENDA_AND_DECISION_CAPTURE.md`.

D2, D3, D4, and D6 remain unsigned. Duplicate employee-code groups remain unauthorized. No company is migration-approved. No load is authorized.

## 1. Current implementation behavior

While D1 is `proposed`, the classifier reads `emp_first_name`, `emp_mid_name`, and `emp_last_name`, records each as `null`, `empty`, or `present`, and leaves `display_name_candidate` empty with reason `name_rule_proposed`. `present` means the parsed value was not `NULL` and was not exactly `''`. A whitespace-only value is classed `present`. The rehearsal report does not retain the raw strings.

The Step 20N recovery report has 1,731 employee rows, 1,730 with at least one component classed `present`, and one all-empty row: Viyellatex source pk 437, code `PAL1234`. Every employee row is `proposed_not_loadable`. Section 1 of the preparation brief records the trace and the class counts.

## 2. Existing options and the unsigned proposal

These four options are copied from Step 20R. They are not changed here.

- A. Drop empty parts, join the rest with one space, hold a row with nothing left.
- B. Use only the first name.
- C. Require a reviewer-supplied name on every row.
- D. Join all three with spaces even when a part is empty.

The current proposal, in both Step 20R and the Step 20R-1 capture block, is:

**PROPOSED — NOT APPROVED.** Option A, from Step 20I.

Step 20I states that proposal in these words: trim whitespace from each nonempty legacy name component; join the remaining first, middle, and last name components with one space, in that order; do not invent a name; if every component is empty or `NULL`, quarantine the record for manual review.

That Step 20I sentence is still the unsigned proposal. It is not a fifth option, and it is not a signed rule. The register’s one-line Option A does not itself say “trim”, “whitespace-only”, or “keep the source components unchanged”. The approver has to confirm or amend that detail in the register’s conditions field. This brief does not make that confirmation.

The external draft that names D1 `obtain_verified_name`, with the text `Anisur Rahman` and the date `2026-10-12`, stays unsigned. It is not the proposal and it is not a signature.

## 3. Questions the approver must answer

Write the answers only in the blank fields of Step 20R and the matching Step 20R-1 capture block. Leave the proposed-recommendation line unchanged. An answer recorded only in this brief is not a decision.

1. Is the selected option A, B, C, or D? The proposal on the table is A.
2. Is the display-name order first, then middle, then last?
3. Does the rule trim leading and trailing whitespace on each component before composition?
4. Are components that are `NULL`, exactly empty, or whitespace-only after trim omitted from the joined name?
5. When nothing usable remains, is the row quarantined for manual review, or is another outcome required? Quarantine is not a load and is not an approved exception.
6. Do the three source components remain stored as source evidence, separate from `display_name`, with no overwrite of the source values?
7. Is a name drawn from the employee code, from another column, or from Yii `getName()` forbidden?
8. Does this decision dispose of Viyellatex source pk 437, code `PAL1234`, or does that row still need its own later signed exception?
9. Does approval of D1 also approve D2, D3, D4, D6, the seven duplicate-code groups, or an employee load? Those items stay unsigned unless their own fields are signed.

The rehearsal cannot answer questions 3 and 4 with a count. Whitespace-only strings are classed `present`, and the raw strings were not saved.

## 4. Approval evidence still outstanding

Step 20R-1 states that a decision exists only when the capture block has the selected option, the approver name and role, and the approval date filled after the meeting. Step 20R section 7 states that a verbal agreement is not an approval until the approver name and the date are filled. The proposed text stays unchanged.

For D1, these fields are blank in both documents:

| Field | Step 20R | Step 20R-1 capture |
| --- | --- | --- |
| Management's actual decision / selected option | Blank | Blank |
| Management rationale | Not a register field | Blank |
| Conditions or exceptions | Blank | Blank |
| Approver name and role | Blank | Blank |
| Approval date | Blank | Blank |
| Due date | Blank | Blank, with the owner |
| Supporting evidence or meeting reference | Steps 20G, 20H, 20I, and 20K are cited | Those steps are cited. No meeting reference is filled |

No signature, approval date, or meeting minutes exist for D1.

### Authority gap

The register does not name a person who may approve D1.

- `Approver name/role` is blank.
- `Responsible owner` is the function **HR operations**. Step 20R section 3 says the responsible owner is the function named in Step 20Q, not a named person.
- No file in `next-hrms\docs` is titled Step 20Q. The register calls Step 20Q the findings paper. The management findings paper records that display name is still awaiting management approval and does not name an approver.
- The unsigned external draft is not authority to treat any person as the approver.

Until a person writes their name, role, and approval date into the blank register and capture fields, there is no authorized signature to apply.

## 5. Actions that wait for approval

Only after the blank fields above are signed, and only under a separate instruction, may a later task:

- change the classifier so an approved D1 rule can fill `display_name_candidate`;
- run one new rehearsal to a new output file, without overwriting the Step 20N recovery report;
- quarantine a row that the signed rule holds, still without loading it.

Signing D1 would not sign D2, D3, D4, or D6, would not authorize the seven duplicate groups, and would not authorize an employee import, migration, or database write. The current configuration remains `gates_closed`, with `target_database_write` false and `signed_exceptions` empty.

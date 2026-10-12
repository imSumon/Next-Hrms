# Universal HRMS — Step 20R-1 Management Review Agenda and Decision Capture

Meeting paper. The official source is `docs/UNIVERSAL_HRMS_STEP20R_MANAGEMENT_DECISION_REGISTER.md`. This agenda does not add findings, change a status, or sign a decision.

No company is approved for employee migration. Preparing this paper does not authorize a migration, a dry-run classification, or attendance or payroll implementation.

Employee figures below are the offline dump findings already recorded in the register. They are not a live census.

## Meeting record

| Field | Entry |
| --- | --- |
| Meeting date | |
| Chairperson | |
| Attendees | |
| Minutes reference | |
| Register copy used | `docs/UNIVERSAL_HRMS_STEP20R_MANAGEMENT_DECISION_REGISTER.md` |

A discussion is not a decision. A decision exists only when the capture block for that ID has the selected option, the approver name and role, and the approval date filled after the meeting.

## Controls already in force

Read these at the start. They are not on the decision list.

- Do not merge employee identities.
- Do not silently recode a duplicate employee code. This part of D4 is **APPROVED**.
- The employee code is not the permanent identity. The target identity is a new ULID.
- Tenants are catalog 24 (Proedge), 20 (VTL), and 10 (Atlas). Dump-local ids 5 and 26 are not tenants.
- Three local company rows exist for those catalog ids. They do not authorize an employee load.
- The relationship between `active` and `disconrinue` is unconfirmed. Do not invent a status for rows where both are 1.
- One product. Company policy is data. A finalized payroll snapshot does not follow a later attendance change. A correction does not edit a raw punch.

## How to use a capture block

Options are the ones already written in Step 20R. They are listed neutrally. The line marked **PROPOSED — NOT APPROVED** is the existing recommendation. It is not selected.

| Field | Fill only after the meeting |
| --- | --- |
| Decision and selected option | |
| Management rationale | |
| Conditions or exceptions | |
| Approver name and role | |
| Approval date | |
| Responsible owner and due date | |
| Impact on design, development, testing, or migration | |
| Supporting evidence or meeting reference | |

## A. Employee identity and migration

Suggested order: D1, D3, D4, then D2 and D6. D5 and the episode checks have no proposal.

### D1 — Display-name composition

Question: how do the three name fields become `display_name`, and what happens when all are empty? VTL id 437, code `PAL1234`, has no usable name.

Options:

- A. Drop empty parts, join the rest with one space, and hold a row with nothing left.
- B. Use only the first name.
- C. Require a reviewer-supplied name on every row.
- D. Join all three with spaces even when a part is empty.

**PROPOSED — NOT APPROVED.** Option A.

| Field | Entry |
| --- | --- |
| Decision and selected option | |
| Management rationale | |
| Conditions or exceptions | |
| Approver name and role | |
| Approval date | |
| Responsible owner and due date | |
| Impact on design, development, testing, or migration | Register: development blocked and migration blocked while unsigned |
| Supporting evidence or meeting reference | Steps 20G, 20H, 20I, and 20K |

### D2 — Legacy zero dates

Question: how is `0000-00-00` treated? Dump joining-date zeros: Proedge 1, Atlas 3, VTL 61. Leaving-date zeros: Proedge 1, Atlas 0, VTL 81.

Options:

- A. Treat it as missing and hold the episode.
- B. Hold the employee and the episode.
- C. Keep a distinct zero-date exception, separate from an ordinary empty date.

**PROPOSED — NOT APPROVED.** Distinct exception. Not a target date. Not an ordinary empty date. A required episode date stays held.

| Field | Entry |
| --- | --- |
| Decision and selected option | |
| Management rationale | |
| Conditions or exceptions | |
| Approver name and role | |
| Approval date | |
| Responsible owner and due date | |
| Impact on design, development, testing, or migration | Register: migration blocked where a zero date would be written |
| Supporting evidence or meeting reference | Steps 20C, 20G, 20H, 20I, and 20K |

### D3 — Both `active` and `disconrinue` are 1

Question: what employment status is stored? Dump counts: Proedge 19, Atlas 74, VTL 1,186. Atlas id 7175, code `EB9091`, discontinue date 2026-08-25, is one of the Atlas rows.

Options:

- A. Hold the status. Do not choose working, inactive, or left.
- B. Allow `separated` only where the leaving date is a real calendar date, and hold the rest.
- C. Require a disposition on each row.

`inactive` is not an available option for this pair.

**PROPOSED — NOT APPROVED.** Option A. Do not infer a status from the leaving date alone.

| Field | Entry |
| --- | --- |
| Decision and selected option | |
| Management rationale | |
| Conditions or exceptions | |
| Approver name and role | |
| Approval date | |
| Responsible owner and due date | |
| Impact on design, development, testing, or migration | Register: development blocked for these statuses. Migration blocked while any remain held |
| Supporting evidence or meeting reference | Steps 20E, 20G, 20H, 20I, and 20K |

### D4 — Duplicate VTL employee-code groups

Question: what happens to seven codes on 14 rows (`10024`, `15575`, `132058`, `6119`, `10023`, `10027`, `10034`)?

The approved restriction is fixed: do not merge the people and do not silently change the codes. The open question is the hold, and then a disposition for each of the 14 rows. Those dispositions do not exist. Merge and recode are not options.

**PROPOSED — NOT APPROVED.** Hold each unresolved group until every member row has its own authorization.

| Field | Entry |
| --- | --- |
| Decision and selected option | |
| Management rationale | |
| Conditions or exceptions | |
| Approver name and role | |
| Approval date | |
| Responsible owner and due date | |
| Impact on design, development, testing, or migration | Register: migration blocked for the 14 rows. The no-merge rule is already approved |
| Supporting evidence or meeting reference | Steps 20E, 20G, 20H, 20I, 20K, and the employee-code decision |

Individual dispositions, one line per source row. Leave blank.

| Code | Source ids already recorded | Disposition | Approver | Date |
| --- | --- | --- | --- | --- |
| `10024` | 255 and 100497 | | | |
| `15575` | 270 and 398 | | | |
| `132058` | 100348 and 100427 | | | |
| `6119` | 100470 and 100472 | | | |
| `10023` | 100480 and 100637 | | | |
| `10027` | 100582 and 100638 | | | |
| `10034` | 100586 and 100639 | | | |

### D6 — Evidence required for an employment episode

Question: when may one current legacy employee row create an episode? No episode table was found. A department, designation, or location change is not an episode.

Options:

- A. At most one episode when an approved status pair and a real joining date exist. Do not parse free-text history.
- B. Load identities only and hold every episode.
- C. Parse the history text. No approved parser exists.

**PROPOSED — NOT APPROVED.** Option A. This does not decide overlap, date order, or inactive pay.

| Field | Entry |
| --- | --- |
| Decision and selected option | |
| Management rationale | |
| Conditions or exceptions | |
| Approver name and role | |
| Approval date | |
| Responsible owner and due date | |
| Impact on design, development, testing, or migration | Register: migration blocked for episodes |
| Supporting evidence or meeting reference | Steps 20B, 20E, 20F, 20H, and 20I |

### D5 — Meaning of `dis`

No proposal and no option list. The integer is not identity and not status. Its business meaning was not found.

| Field | Entry |
| --- | --- |
| Decision and selected option | |
| Management rationale | |
| Conditions or exceptions | |
| Approver name and role | |
| Approval date | |
| Responsible owner and due date | |
| Impact on design, development, testing, or migration | Register: informational for the employee load |
| Supporting evidence or meeting reference | Steps 20G, 20H, and 20I |

### LIFE-OVERLAP — Episode date overlap

No proposal. The table allows one open episode. It does not forbid other episodes from overlapping. Options already recorded: leave the check undecided, or approve a check before any second episode.

| Field | Entry |
| --- | --- |
| Decision and selected option | |
| Management rationale | |
| Conditions or exceptions | |
| Approver name and role | |
| Approval date | |
| Responsible owner and due date | |
| Impact on design, development, testing, or migration | Informational for one episode. Development blocked for a second episode |
| Supporting evidence or meeting reference | Steps 20B, 20C, and 20I |

### LIFE-ORDER — Leaving date against joining date

No proposal. The table does not require the leaving date to fall on or after the joining date. Options already recorded: leave the check undecided, or approve it before a load that needs it.

| Field | Entry |
| --- | --- |
| Decision and selected option | |
| Management rationale | |
| Conditions or exceptions | |
| Approver name and role | |
| Approval date | |
| Responsible owner and due date | |
| Impact on design, development, testing, or migration | Informational for the current episode proposal. Development blocked for a reversed-date rule |
| Supporting evidence or meeting reference | Step 20B |

### LIFE-INACTIVE-PAY — Pay effect of `inactive`

No proposal. Do not infer `inactive` from an unclear flag pair. No pay amount sits on the episode.

| Field | Entry |
| --- | --- |
| Decision and selected option | |
| Management rationale | |
| Conditions or exceptions | |
| Approver name and role | |
| Approval date | |
| Responsible owner and due date | |
| Impact on design, development, testing, or migration | Informational for identity. Development blocked for payroll use of `inactive` |
| Supporting evidence or meeting reference | Steps 20B and 20H |

## B. Attendance policy

Legacy differences are evidence. They are not a list of approved choices. No option list exists beyond what Step 20R already states.

### BD-D02 — Incomplete or missing punch

Proedge and Atlas stored Present plus missing-punch text. Viyellatex stored a missing out as Absent. The condition must stay separate from the status. The outcome value is empty.

**PROPOSED — NOT APPROVED.** Detect the incomplete day. Do not copy either company's outcome as the universal result.

| Field | Entry |
| --- | --- |
| Decision and selected option | |
| Management rationale | |
| Conditions or exceptions | |
| Approver name and role | |
| Approval date | |
| Responsible owner and due date | |
| Impact on design, development, testing, or migration | Development blocked for the status rule. Historical text can still be preserved |
| Supporting evidence or meeting reference | Architecture assessment. Third-company audit. Workbook. Technology decision |

### BD-D03 — Shift assignment when sources conflict

Proedge August followed the employee shift. Viyellatex September followed the roster. No winner is approved.

**PROPOSED — NOT APPROVED.** Keep both candidates. Do not pick a winner during migration.

| Field | Entry |
| --- | --- |
| Decision and selected option | |
| Management rationale | |
| Conditions or exceptions | |
| Approver name and role | |
| Approval date | |
| Responsible owner and due date | |
| Impact on design, development, testing, or migration | Development blocked for the go-forward winner. Citing the shift already stored on a historical day is allowed |
| Supporting evidence or meeting reference | Architecture assessment. Workbook. Technology decision BD-D03 |

### BD-D16 and BD-D17 — Correction fields and approval workflow

The history mechanism is approved: original, requested, approved, and final values. The raw punch is not edited. The list of fields a correction may change, and the approval steps, are empty. Boss and grand-boss are not a workflow.

**PROPOSED — NOT APPROVED.** Do not seed a field list or a boss step.

| Field | Entry |
| --- | --- |
| Decision and selected option | |
| Management rationale | |
| Conditions or exceptions | |
| Approver name and role | |
| Approval date | |
| Responsible owner and due date | |
| Impact on design, development, testing, or migration | Development blocked for the correction form and the approval route |
| Supporting evidence or meeting reference | Architecture assessment sections 11 and 12. Workbook section 12 |

### BD-D13 and BD-D14 — Recalculation, attendance cutoff, and payroll close

Lock tables did not close the studied months. A Yes flag is not a cutoff. The snapshot rule is already approved: finalized pay does not change because attendance is later recalculated.

**PROPOSED — NOT APPROVED.** Do not treat legacy lock rows as close events.

| Field | Entry |
| --- | --- |
| Decision and selected option | |
| Management rationale | |
| Conditions or exceptions | |
| Approver name and role | |
| Approval date | |
| Responsible owner and due date | |
| Impact on design, development, testing, or migration | Development blocked for cutoff and close behavior. The snapshot separation is already approved |
| Supporting evidence or meeting reference | Architecture assessment. Workbook section 14 |

### BD-D15 — Who may reopen a closed period

Deferred. No reopen role exists. No proposal is added here.

| Field | Entry |
| --- | --- |
| Decision and selected option | |
| Management rationale | |
| Conditions or exceptions | |
| Approver name and role | |
| Approval date | |
| Responsible owner and due date | |
| Impact on design, development, testing, or migration | Development blocked for a reopen action |
| Supporting evidence or meeting reference | Technology decision BD-D15 |

### PUNCH-WITHDRAW — Raw punch immutability and withdrawal

Immutability is approved. No rule names who may withdraw a punch.

**PROPOSED — NOT APPROVED.** Do not delete punches. A withdrawal action needs its own signed rule.

| Field | Entry |
| --- | --- |
| Decision and selected option | |
| Management rationale | |
| Conditions or exceptions | |
| Approver name and role | |
| Approval date | |
| Responsible owner and due date | |
| Impact on design, development, testing, or migration | Informational for immutability. Development blocked for a withdrawal action |
| Supporting evidence or meeting reference | Architecture assessment sections 9 and 11 |

## C. Overtime and payroll

### BD-D09 and BD-D10 — Calculation formula and rounding

No formula and no rounding rule are approved. Proedge seconds, Viyellatex hours beside corrections, and Atlas's empty September figure are evidence, not a formula.

**PROPOSED — NOT APPROVED.** Do not derive a formula from the studied months.

| Field | Entry |
| --- | --- |
| Decision and selected option | |
| Management rationale | |
| Conditions or exceptions | |
| Approver name and role | |
| Approval date | |
| Responsible owner and due date | |
| Impact on design, development, testing, or migration | Development blocked for overtime calculation |
| Supporting evidence or meeting reference | Workbook section 13. Technology decision BD-D09 and BD-D10 |

### BD-D08, BD-D11, and BD-D12 — Eligibility, approval, and payable amount

The eligible flag does not match who was paid. An attendance correction is not an overtime approval. Paid money is a snapshot amount. No rate is approved.

**PROPOSED — NOT APPROVED.** Keep calculated, payable, and paid overtime apart.

| Field | Entry |
| --- | --- |
| Decision and selected option | |
| Management rationale | |
| Conditions or exceptions | |
| Approver name and role | |
| Approval date | |
| Responsible owner and due date | |
| Impact on design, development, testing, or migration | Development blocked for overtime approval and payment. Stored historical money can still be loaded as money |
| Supporting evidence or meeting reference | Workbook section 13. Technology decision BD-D08, BD-D11, and BD-D12 |

### PAY-SNAPSHOT — Finalization and what the snapshot keeps

The separation is approved. Historical salary rows have amounts and no attendance copy. Which extra lines belong on the snapshot is BD-D18 and is open.

**PROPOSED — NOT APPROVED.** For BD-D18 only: do not invent optional lines.

| Field | Entry |
| --- | --- |
| Decision and selected option | |
| Management rationale | |
| Conditions or exceptions | |
| Approver name and role | |
| Approval date | |
| Responsible owner and due date | |
| Impact on design, development, testing, or migration | Informational for the approved separation. Development blocked for optional lines |
| Supporting evidence or meeting reference | Architecture assessment AP-05. Workbook section 14. BD-D18 |

### Reopening finalized payroll

Use BD-D14 for whether payroll closes, and BD-D15 for who may reopen. Do not treat a stored salary row as a close policy. Capture those two IDs in section B. No additional reopen rule is proposed here.

## D. Universal rules, company policy, and extensions

### POLICY-SHAPE — Already approved

One product. Company differences are configuration. An extension stays off until that company enables it. This agenda does not reopen that shape. The open work is the policy values in sections B and C.

| Field | Entry |
| --- | --- |
| Decision and selected option | Already approved as architecture. No new selection |
| Management rationale | |
| Conditions or exceptions | Extensions stay off until enabled |
| Approver name and role | Not re-signed in this meeting |
| Approval date | |
| Responsible owner and due date | Product owner if someone asks for a separate engine |
| Impact on design, development, testing, or migration | Does not block one engine. Does not fill the open policy values |
| Supporting evidence or meeting reference | Architecture assessment AP-01, AP-02, and AP-03 |

### Further evidence, not a new finding

These remain open because the register says so. No option list is added.

| ID | Subject | Why it is still open |
| --- | --- | --- |
| BD-D01 | Grace length | No approved minutes. Companies differed in the studied months |
| BD-D04 | When late measurement starts | Not chosen |
| BD-D05 | Early-out pay | Not chosen |
| BD-D06 | Leave pay | Unchanged basic pay in small samples does not prove every leave type is paid |
| BD-D07 | Absence pay | Not chosen |
| D5 | Meaning of `dis` | No definition found |
| Atlas and CEVA engines | Operated months | Source has separate process actions. Those months were not traced |
| Punch vendor | Who writes the shared log | Unknown. About 13.9 million rows is the architecture assessment's audited-log figure, not a new count |
| `EB9091` September | Attendance after the discontinue date | Recorded in the closed-month audit. Not re-counted later |

Capture for any of these only if the meeting is willing to own it. Otherwise leave them unsigned.

| ID | Decision and selected option | Approver | Date |
| --- | --- | --- | --- |
| BD-D01 | | | |
| BD-D04 | | | |
| BD-D05 | | | |
| BD-D06 | | | |
| BD-D07 | | | |

## Meeting summary

Status before this meeting. Nothing in this table was changed by writing the agenda.

| Status | Items |
| --- | --- |
| Approved | ULID identity. Company-scoped employee code. No merge and no silent recode. Catalog tenants 24, 20, and 10, with local company rows seeded. Reconciliation contract, not executed. One product with policy as data. Immutable punches. Versioned day results. Finalized payroll snapshot does not follow later attendance changes |
| Awaiting management decision | D1, D2, D3, D4 group hold, D6, BD-D02, BD-D03, BD-D08 through BD-D14, BD-D16, BD-D17, BD-D18, and punch withdrawal |
| Further evidence required | D5, LIFE-OVERLAP, LIFE-ORDER, LIFE-INACTIVE-PAY, BD-D15, BD-D01, BD-D04, BD-D05, BD-D06, BD-D07, Atlas and CEVA operated months, punch vendor, and a fresh count of September facts for `EB9091` |
| Design/development blocked | D1 and D3 for an employee load. BD-D02, BD-D03, and the overtime and correction items for attendance build. BD-D13 and BD-D14 for close behavior. BD-D15 for reopen. Overlap and date order for a second episode. Inactive pay for payroll use of `inactive`. Punch withdrawal for a withdrawal action. BD-D18 for optional snapshot lines |
| Migration blocked | D1, D2, D3, the D4 group hold, and D6. The 14 duplicate rows, the empty-name row, and the both-flags rows have no signed dispositions. Reconciliation has not been run. No company is approved for employee migration |

## Close of the meeting

1. Read back every ID whose capture block was filled.
2. Leave every other block blank.
3. Do not mark Proedge, Viyellatex, or Atlas migration-approved.
4. A later step may copy signed blocks into the Step 20R register. This paper does not do that.

AGENDA PREPARED

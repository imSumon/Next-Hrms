# Universal HRMS — D1 Display-Name Decision Preparation

Preparation date: 2026-10-15

Status: **PREPARATION ONLY**. D1 is not approved. This brief does not fill a Step 20R decision, approver, or date field. It does not change `tools/employee-classification/config/classification.php`. It does not authorize a rehearsal, a classifier change, or an employee load.

The official register remains `docs/UNIVERSAL_HRMS_STEP20R_MANAGEMENT_DECISION_REGISTER.md`. The existing proposed recommendation there is Option A, from Step 20I, and that recommendation stays **PROPOSED — NOT APPROVED**. An external draft that names D1 `obtain_verified_name`, with the text `Anisur Rahman` and the date `2026-10-12`, stays unsigned and is not adopted here.

D2, D3, D4, and D6 remain unsigned. No company is migration-approved. Duplicate employee-code groups remain unauthorized. No business-policy default is inferred from the rehearsal.

## 1. Current behavior and evidence

### Source columns

The parser keeps nine `employees` columns. The name columns are `emp_first_name`, `emp_mid_name`, and `emp_last_name`, in that source order (`DumpParser::KEPT_COLUMNS`). A tuple is accepted only when its field count matches that dump’s column list. Those three fields are read by column name. Other columns are discarded while the tuple is read. Missing any kept column fails the parse.

### What the classifier does while D1 is proposed

Configuration `mode` is `gates_closed`. The closed gate set records D1, D2, D3, D4, and D6 as `proposed`. `target_database_write` is `false`. `signed_exceptions` is empty.

For each source row the classifier:

1. Sets `display_name_candidate` to an empty string.
2. Adds reason `name_rule_proposed`.
3. Records `name_component_classes` for the three columns. The class is `null` when the parsed value is `NULL`, `empty` when it is exactly `''`, and `present` for every other string.
4. Refuses to publish a report if any `display_name_candidate` is nonempty while D1 is `proposed`.

Trim and joining of the three parts exist in the classifier only on the branch where D1 is `approved`. That branch is not used by the current configuration. It trims each component, drops a component that is empty after trim, joins the rest with one space, and would quarantine a result that is still empty with reason `name_empty`. This brief does not turn that branch into an approved rule.

The report copies the employee classification as produced. It stores the component classes and the empty candidate. It does not store the raw component strings. `name_component_classes` are not part of the classification SHA-256.

Step 20J section 5 says `display_name_candidate` is filled only when D1 is `approved` and at least one component remains after the D1 rule. Step 20J section 6 says that, while D1 is proposed, the three classes are recorded and the candidate stays empty.

### Rehearsal evidence

Artifact: `C:\Users\ASUS\AppData\Local\UniversalHrmsClassification\employee-classification-20n-recovery.json`

Run id `230d087865d34b62`, classified at `2026-10-14T19:37:54+00:00`. Mode `gates_closed`. This preparation did not rerun the classifier.

| Evidence | Result in that report |
| --- | --- |
| Employee source rows | 1,731 (Proedge catalog 24: 160; Atlas catalog 10: 151; Viyellatex catalog 20: 1,420) |
| Rows with at least one component classed `present` | 1,730 |
| Rows with no component classed `present` | 1 |
| `display_name_candidate` | Empty on all 1,731 rows |
| Employee reason `name_rule_proposed` | Present on all 1,731 rows |
| Employee mapping status | `proposed_not_loadable` on all 1,731 rows |
| Employee proposed load | 0 for each company |

The one row with classes `empty` / `empty` / `empty` is Viyellatex `source_pk` 437, employee code `PAL1234`. That is the empty-name candidate already named in Steps 20G, 20I, and 20J. Its display candidate length is 0.

Component-class counts from the same report, in order first / middle / last. These are classes, not names:

| Catalog | First | Middle | Last | Rows |
| ---: | --- | --- | --- | ---: |
| 24 | present | empty | present | 46 |
| 24 | present | present | empty | 2 |
| 24 | present | present | present | 112 |
| 10 | empty | present | empty | 1 |
| 10 | present | empty | empty | 78 |
| 10 | present | empty | present | 36 |
| 10 | present | present | empty | 2 |
| 10 | present | present | present | 34 |
| 20 | empty | empty | empty | 1 |
| 20 | present | empty | empty | 374 |
| 20 | present | empty | present | 575 |
| 20 | present | present | empty | 87 |
| 20 | present | present | present | 383 |

No row in this report has a component classed `null`. `present` means the parsed string was not `NULL` and was not exactly `''`. A whitespace-only string is classed `present`. The report does not retain the raw strings, so this brief does not estimate how many candidates would change after trimming.

## 2. Proposed rule options and trade-offs

These are choices for a later signature. None is approved by this brief. The register already lists four options. The composition points below are the questions those options still have to answer.

### Register options, still unsigned

| Option | Rule as already written in Step 20R | Advantage | Risk |
| --- | --- | --- | --- |
| A | Drop empty parts, join the rest with one space, hold a row with nothing left. Step 20I text: trim each nonempty component, keep first-middle-last order, quarantine when every component is empty or `NULL` | Uses the three source fields and does not invent a name. Matches the empty-name hold already described for Viyellatex id 437 | Whitespace-only parts are not counted in the current report, so the size of the hold after trim is unknown. A later signature must say whether whitespace-only is “empty” |
| B | Use only the first name | Avoids a disputed middle or last name | Drops source name evidence. The Atlas row classed `empty` / `present` / `empty` would have no first name. Whether that row is then held is not defined by option B as written |
| C | Require a reviewer-supplied name on every row | Avoids an automated composition | Blocks every legacy row, including the 1,730 rows that have a present component, until a person supplies a name |
| D | Join all three with spaces even when a part is empty | Keeps a fixed three-part shape | Can store a name that is only spaces, or a name with doubled spaces where a part was empty. Step 20R already names the risk of a blank that only looks nonempty |

Option A is the existing proposed recommendation. It is not a decision.

### Composition points a signature has to settle

| Point | Proposal to decide, not a default | Advantage | Risk if left unspoken |
| --- | --- | --- | --- |
| Order | Keep source order: first, then middle, then last | The parser and Step 20I already use that order | Another order would disagree with the source columns and with the unsigned Step 20I text |
| Trim | Trim leading and trailing whitespace on each component before composition | Stops a space-only part from looking like a name | Internal double spaces, and characters other than ordinary space, are not evidenced here. A trim rule that is wider than the ends of the string needs its own text |
| Omit empty parts | Decide whether a component that is `NULL`, `''`, or whitespace-only after trim is left out of the joined name | Prevents empty slots from becoming extra spaces | Option D conflicts with omission. The brief does not choose between them |
| All three empty | Define the outcome when nothing remains. The unsigned Step 20I text quarantines that row for manual review | Preserves the row in the source count and does not invent a name from the employee code | Quarantine is not an approved exception and is not a load. A reviewer-supplied replacement still needs its own signed exception |
| Source values | Keep the three source components available as source evidence. Write the composed value only to the target display name | A later correction can be compared with the dump | Overwriting the source fields, or treating the composed string as the only record, removes the evidence for the composition |
| No invention | Do not create a name from `emp_id_no`, from another column, or from a screen helper such as Yii `getName()` | Step 20I and Step 20J already forbid an invented name | Using the employee code as a stand-in would hide the empty-name case |

## 3. Edge cases and unresolved decisions

Confirmed from the rehearsal report:

- All 1,731 employee rows are classified and none is loadable.
- The only all-empty class pattern is Viyellatex source pk 437, code `PAL1234`.
- Every employee row carries `name_rule_proposed` because D1 is proposed, including rows whose components are classed `present`.
- Seven Viyellatex duplicate-code groups, 14 rows, have empty `authorization`. A future name rule does not authorize those groups. D4 remains unsigned. NOMERGE-APPROVED still forbids merge and silent recode.

Not established by this report:

- How many `present` values are whitespace-only, or would become empty after trim.
- Whether any component contains internal runs of spaces, punctuation only, or a placeholder other than `''`.
- Whether any source value was `NULL` in the dump. The published classes contain no `null`, which means the parser did not return `NULL` for these three columns on these 1,731 rows. That is a class result, not a second reading of the dumps.
- Which person supplies a replacement name for a quarantined empty row, and whether that replacement is an approved exception or a correction of the source extract.

Other unsigned gates still change the same rows. D2 covers zero dates, D3 covers both flags true, and D6 covers whether a legacy row may create an episode. Those outcomes are independent of the display name. This brief does not propose defaults for them.

## 4. Questions for a named approver

The approver, the approval date, and the conditions are blank until they are written in Step 20R. This list is the decision request. It does not record an answer.

1. Which register option, or which written variant, becomes D1: A, B, C, or D?
2. Does the rule trim leading and trailing whitespace on each of `emp_first_name`, `emp_mid_name`, and `emp_last_name`?
3. Are `NULL`, exact empty, and whitespace-only components omitted from the joined display name?
4. When nothing remains, is the employee row quarantined, held for a reviewer-supplied name, or blocked with the whole company load?
5. For Viyellatex source pk 437, code `PAL1234`, who may supply a display name, and in which signed exception record?
6. Must the three source components be retained separately from `display_name`, with no overwrite of the source values?
7. Is composition from any field other than those three columns forbidden, including the employee code?
8. Does approval of D1 alone authorize an employee load? The constraints in section 5 say no, unless the approver writes a different condition into the register.

## 5. Preconditions for a separately authorized future rehearsal

A later rehearsal that applies an approved D1 rule is a new execution. The Step 20N recovery report stays the gates-closed evidence. Its rows are not loadable records.

Before any such rehearsal, all of the following are required:

- A named approver and an approval date written in the blank D1 fields of Step 20R, including the trim rule, the omission rule, the component order, the all-empty outcome, and the prohibition on invented names.
- A statement that the three source components remain source evidence and are not replaced by the composed display name.
- A signed disposition for every row the approved rule quarantines, including the known empty-name row if that rule quarantines it. An unsigned exception id is not an approved exception.
- D2, D3, D4, and D6 still treated as unsigned unless their own register fields are signed in the same or an earlier record. Signing D1 does not sign them and does not authorize the seven duplicate groups.
- A separate instruction to change the classifier or its configuration. The current runner refuses a mode other than `gates_closed` and refuses a nonempty `signed_exceptions` list. This brief does not make that change.
- A new output path and an explicit authorization to run the classifier once. The existing recovery JSON is not overwritten.
- No database connection, no employee import, and no migration load inside that rehearsal. `company_row_is_migration_approval` stays false. Proposed classifications from either rehearsal are not a load approval.

Until those conditions exist, D1 remains unsigned and `display_name_candidate` stays empty under the current rule.

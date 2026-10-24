# Universal HRMS — Documentation Authority

Date: 2026-10-16

Status: **AUTHORITY NOTE.** This file tells a later session which documents to follow. It does not approve a policy, sign a decision, fill Step 20R, or authorize implementation. No file was copied, moved, or overwritten to produce it. No company is migration-approved.

## 1. What to read first

When resuming work, read in this order:

1. This file.
2. `next-hrms\docs\UNIVERSAL_HRMS_PROJECT_CONTROL_CENTER.md`
3. `next-hrms\docs\UNIVERSAL_HRMS_INCREMENTAL_DELIVERY_AND_DECISION_GATES.md`, including section 5
4. The unsigned review named by that section, if the task touches access or attendance approval:
   - `next-hrms\docs\UNIVERSAL_HRMS_PRODUCT_SURFACE_AND_ACCESS_MODEL_DECISION.md`
   - `next-hrms\docs\UNIVERSAL_HRMS_BD_D16_BD_D17_OWNER_DECISION_WORKSHEET.md`
5. `next-hrms\docs\UNIVERSAL_HRMS_DECISION_READINESS_REVIEW.md` when the task is an owner review of open decisions

Do not start from `docs\UNIVERSAL_HRMS_INCREMENTAL_DELIVERY_AND_DECISION_GATES.md` or from `docs\UNIVERSAL_HRMS_PROJECT_CONTROL_CENTER.md`.

## 2. Classification of the documents inspected

| Document | Classification | Evidence |
| --- | --- | --- |
| `next-hrms\docs\UNIVERSAL_HRMS_PROJECT_CONTROL_CENTER.md` | **Active checkpoint** | It calls itself the continuity record. It records a documentation correction on 2026-10-16. It states that the parent `docs` folder and `legacy-reference\hrms\docs` were the earlier copies, that `next-hrms\docs` holds the documentation set, and that the status correction did not change the parent folder |
| `next-hrms\docs\UNIVERSAL_HRMS_INCREMENTAL_DELIVERY_AND_DECISION_GATES.md` | **Active gate record.** Section 5 is the current next-task statement | Section 5 is titled "Current checkpoint." It says the rejoin-message correction is complete and is not the next task, and that the next implementation milestone is not authorized. The decision table above that section was not rewritten by the section 5 wording change |
| `next-hrms\docs\UNIVERSAL_HRMS_DECISION_READINESS_REVIEW.md` | **Active review aid.** Not a decision and not the checkpoint | Dated 2026-10-16. Status is review for the owner. It organizes unsigned decisions and selects none |
| `next-hrms\docs\UNIVERSAL_HRMS_PRODUCT_SURFACE_AND_ACCESS_MODEL_DECISION.md` | **Active unsigned brief** | Dated 2026-10-16. Status is unsigned. It is not an approval and not a working direction for implementation. It is not in the parent `docs` folder |
| `next-hrms\docs\UNIVERSAL_HRMS_BD_D16_BD_D17_OWNER_DECISION_WORKSHEET.md` | **Active unsigned worksheet** | Dated 2026-10-16. Status is unsigned / for owner review. It selects no alternative and does not fill Step 20R. It is not in the parent `docs` folder |
| `next-hrms\docs\UNIVERSAL_HRMS_AUD07_APPROVAL_CALLERS_AND_PAYROLL_READS.md` | **Active evidence record** | Status is source trace complete. It adds caller and payroll-read evidence. It does not replace AUD-02 and does not sign a gate. It is not in the parent `docs` folder |
| `docs\UNIVERSAL_HRMS_PROJECT_CONTROL_CENTER.md` | **Older snapshot.** Not the checkpoint | Its opening is the 2026-10-12 text and does not contain the 2026-10-16 correction. It still says `next-hrms\docs` holds Step 20E only and does not receive the control center. The active control center contradicts that sentence |
| `docs\UNIVERSAL_HRMS_INCREMENTAL_DELIVERY_AND_DECISION_GATES.md` | **Older snapshot for the next-task instruction** | Section 5 is still titled "Next independent development task" and still says to correct the episode validation message. That instruction is obsolete. The active file's section 5 replaced it. This task did not compare every other paragraph of the two gates files |

A newer file time was not the reason for these labels. The labels follow what each file says about itself, and whether the later correction is present in the text.

## 3. Checkpoint rules that stay in force

- The rejoin-message correction is complete. It is not the next implementation task.
- The next product implementation milestone is not authorized.
- The access-model brief and the BD-D16 / BD-D17 worksheet remain unsigned.
- SEC-CORE-004 is approved as the initial role list in Step 20R section 6C, dated 2026-10-17, subject to the conditions in that section. The role-matrix build is not authorized. SEC-CORE-003 remains unsigned. D1–D6 and the attendance and payroll decisions listed in the active control center remain unsigned. BD-D15 remains deferred. SCOPE-01 remains deferred. The first-release scope boundary is approved in Step 20R section 6D, dated 2026-10-17. That approval does not authorize implementation.
- A new module, permission model, schema, migration, or attendance or payroll workflow may be implemented only after a named implementation milestone is authorized in Step 20R, with Approver name/role and Approval date, and is recorded in section 5 of `next-hrms\docs\UNIVERSAL_HRMS_INCREMENTAL_DELIVERY_AND_DECISION_GATES.md`. This authority note does not authorize that milestone. First-release scope, AUTH-ARCH, the SEC-CORE-004 list, design documents, and UAT results do not authorize it. G1 Option A, recorded 2026-10-18 by Owner, Owner, is the documentation rule for this sentence.
- The implemented employee foundation, including login, active company, logout, directory, profile, display-name history, episodes, and the approved rejoin rule, is not blocked by those unsigned decisions. Department and designation masters exist under the Stage 5D working direction. That direction is not an owner signature. Automated tests are not signed-in browser UAT.
- AUD-01 through AUD-07 are not to be repeated in order to decide which folder is authoritative.
- No company is migration-approved. The three company rows are tenant records.

## 4. How to handle a conflict

If an instruction in the parent `docs` folder, or in `legacy-reference\hrms\docs`, disagrees with the active checkpoint:

- Follow `next-hrms\docs\UNIVERSAL_HRMS_PROJECT_CONTROL_CENTER.md` for what is done, what is blocked, and what is not authorized.
- Follow section 5 of `next-hrms\docs\UNIVERSAL_HRMS_INCREMENTAL_DELIVERY_AND_DECISION_GATES.md` for the current next-task statement.
- Follow section 4 of that same active gates file for gate status when the task is about a decision. Do not revive a gate from the parent copy.
- Do not treat the parent gates section 5 as the current next task. Check the active section 5 first.
- Do not treat an unsigned brief or worksheet as a signed decision. Step 20R remains the register, and its blank fields stay blank until the owner writes them.
- If two active files in `next-hrms\docs` disagree about whether new work may start: follow Step 20R for signed decisions; follow gates section 5 for whether a milestone is authorized; follow gates section 4 for what a named unsigned decision blocks. A conflict this note does not classify stays **UNRESOLVED**.
- Do not silently copy one file over the other. Do not "synchronize" the folders. A conflict that this note does not already classify is **UNRESOLVED** until it is read and recorded. It is not resolved by overwriting.

The parent section 5 instruction to correct the rejoin message is a known obsolete instruction. Completing it again is not required.

## 5. What must not be overwritten

- The parent `docs` copies inspected here. They were left in place on purpose. The active control center says the status correction did not change the parent folder.
- `legacy-reference\hrms\docs`. The active control center names that folder as an earlier copy. This task did not re-read it. See section 6.
- AUD-02 and AUD-07. Later evidence is added beside them, not by rewriting them.
- Step 20R blank decision, approver, and date fields.
- The unsigned status lines of the access brief and the BD worksheet.
- Application code, tests, schema, and databases. This note does not change them.

## 6. Unresolved

| Question | Why it is unresolved | What would resolve it |
| --- | --- | --- |
| Whether every other file in the parent `docs` folder is an older snapshot or still the copy a session should edit | This task compared the control center and the gates file only. The active control center says the parent folder was the earlier copy and was not changed by the status correction. It does not say that every parent file is identical to `next-hrms\docs` | A named file pair, compared for the instruction in dispute. Until then, do not overwrite either copy |
| Whether the gate tables in the two incremental-delivery files differ anywhere other than section 5 | Only the opening of section 5 and the employee-migration paragraph directly above it were compared. That paragraph matches. The rest of the tables were not compared line by line | A later read of a named row. Do not assume the tables were synchronized |
| The control center inside `legacy-reference\hrms\docs` | The parent control center calls it a matching copy of the older text. The active control center calls that folder an earlier copy. This task did not open the legacy-reference file | Read it before relying on it. Do not copy it onto the active checkpoint |
| One row in the active control center, section 4 | That row still says the incremental-delivery plan was not rewritten by the 2026-10-16 correction. Section 5 of the active plan was rewritten later. The row describes the earlier correction and was not edited by this task | A future checkpoint edit may correct that row. This authority note does not |

## 7. How a later checkpoint should be written

Change the active files in `next-hrms\docs`. When the next-task statement changes, update the active control center and section 5 of the active gates file in the same documentation change, so they do not give two instructions.

State that the parent `docs` folder was not updated, unless the owner has explicitly asked for that folder to be updated. Do not describe the folders as synchronized when they were not.

Leave decision status unchanged unless the owner has signed that decision in Step 20R. A new review document does not change a gate.

## 8. Statement

Only this file was created. No existing document was edited, copied, moved, renamed, or deleted. No application code, test, schema, configuration, database, or Git state was changed. No decision was signed.

AUTHORITY NOTE

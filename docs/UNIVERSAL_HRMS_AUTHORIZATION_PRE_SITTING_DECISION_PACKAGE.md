# Universal HRMS â€” Authorization Pre-Sitting Decision Package

Date: 2026-10-16

Status: **SITTING PACKAGE, WITH PROPOSED OWNER INTENT.** The intent lines record a later recommendation for preparation of the record. They are not a signature, not an approver, and not a decision date. This file does not edit Step 20R, change a gate, or authorize implementation. SEC-CORE-003 and SEC-CORE-004 stay unsigned. The two membership flags stay as they are. Stage 5D's original rule stays in force. No company is migration-approved. No item below is DEFER.

Sources, unchanged by this file: `UNIVERSAL_HRMS_INITIAL_ROLE_PERMISSION_MATRIX_PROPOSAL.md`, `UNIVERSAL_HRMS_AUTHORIZATION_DECISION_RECORD_DELTA.md`, and `UNIVERSAL_HRMS_AUTHORIZATION_DECISION_READINESS_CHECKLIST.md`. Suggested role labels remain proposals.

The recommended option is the package's earlier recommendation. The intent line is the later proposed intent. Where those two agree, the intent line says ACCEPT. Where they differ, it says MODIFY. ACCEPT and MODIFY here are not Step 20R.

## 1. Four role categories

Owner decision: whether to accept, in principle, the four proposed categories: self-service employee, employee records manager, organization master manager, and membership and role administrator.

Recommended option: accept the four categories in principle.

Rationale: the recorded intent is company-scoped roles with specific permissions, and a membership-and-role responsibility separate from employee management and from department and designation management. One category does not grant another.

Evidence before implementation: Step 20R with an approver, a date, and the conditions. The fourth category also needs that writing before it can be built, because Stage 5D still says there is no company-administrator role and that SEC-CORE-004 is unimplemented.

Policy: the owner accepts or rejects the four categories. Safeguard: holding one category does not grant another, and none of them grants payroll, attendance, migration, or a platform-wide action.

Proposed owner intent: ACCEPT. Not a formal signature.

## 2. Employee directory

Owner decision: whether another employee's record requires an explicit directory permission, separate from the employee's own profile.

Recommended option: yes. Own-profile read is the self-service category. Listing or opening another employee requires the employee-records category. Membership alone grants neither.

Rationale: the requirements note proposes that membership is not permission to read every row. The matrix keeps the two reads apart. The gates still describe company-wide access as the boundary already implemented, so this choice does not describe today's screens.

Evidence before implementation: the owner's confirmation, then a test that a member without the directory permission cannot open another employee's id, and that the own-profile permission cannot be used for that id. Today's directory is not that test.

Policy: who may see other employees. Safeguard: the server binds the employee id. A posted id does not choose the profile.

Proposed owner intent: ACCEPT. Membership alone grants neither a directory-wide read nor unrestricted access to employee records. Own-profile access stays separate. Not a formal signature.

This intent is not the current gate. SEC-CORE-003 still permits company membership as implemented and prohibits a narrower organizational scope until that model is signed. SEC-CORE-004 still says the role list is unsigned and blocks a role matrix. Neither row was changed. The screens running today are not claimed to enforce this directory rule.

Before implementation, the owner records this rule in Step 20R, with an approver, a date, and the conditions, and the SEC-CORE-004 gate is updated from that writing so a role list is no longer unsigned for this permission. This correction does not make that gate update.

## 3. The two existing flags

Owner decision: leave `can_manage_employees` and `can_manage_organization_masters` in place at this sitting.

Recommended option: leave both flags unchanged. Treat any replacement as a later approval.

Rationale: the recorded intent keeps the flags until an approved and tested mapping. Stage 5D and Stage 4B already separate them. The gates forbid copying the employee flag onto payroll, attendance correction, or period close.

Evidence before a replacement, and not before this sitting: the mapping written in the matrix, tests in that section passed, and a separate Step 20R entry for the replacement. This package does not approve that mapping.

Policy: the flags remain the grants on the current screens. Safeguard: no third boolean, and no copy of either flag onto payroll or attendance.

Proposed owner intent: ACCEPT. Replacement stays a separate approval after a documented mapping and passing tests. Not a formal signature.

## 4. Company creation

Owner decision: who may create a company, which fields they may set, who is accountable, and which audit fields are stored.

Recommended option: a controlled operational action outside the four company categories. The action creates the company and does not grant employees, pay, or punches.

Rationale: the recorded intent rejects an unrestricted all-company administrator and still requires company setup to be accountable and audited. The four categories exist inside a company, so they cannot create the first company.

Evidence before implementation: the named actor, the field list, and the audit fields in the owner's confirmation. A later test shows the action does not return another company's employees.

Policy: the actor, the fields, and the audit contents. Safeguard: the server assigns the company, and the audit row does not store a password.

Proposed owner intent: ACCEPT the controlled procedure and the rule that it does not grant employee records, pay, or punches. The actor, the field list, and the audit fields are still unwritten. Not a formal signature.

## 5. First membership and initial grants

Owner decision: who attaches the first user, whether that path is the same as membership recovery, and which grants the first membership receives.

Recommended option: the company-creation path also creates the first membership. That membership receives only the membership-and-role category for that company. Employee-records and organization-master grants are added afterward, by assignment.

Rationale: the recorded intent separates role administration from employee management and from master management. The employee flag is not the assigner role. Ordinary membership administration does not cover a company that has no membership yet.

Evidence before implementation: the owner's confirmation of the path and of that initial grant. A later test shows the first membership cannot create an employee or a department until those grants are assigned. Stage 5D still blocks building the membership-and-role category before Step 20R.

Policy: the path and the initial grants. Safeguard: membership alone does not grant employee writes or master writes.

Proposed owner intent: ACCEPT. The controlled setup procedure creates the first membership and grants only the membership-and-role administration responsibility. Not a formal signature. That setup procedure does not recover a company that already exists and has no role assigner. Recovery is item 8A.

## 6. Account recovery

Owner decision: whether the account holder may recover the account, who else may set a password, and who may clear a lock.

Recommended option: the account holder may start recovery. Another person setting the password is not one of the four company categories. Recovery does not add a role or a membership.

Rationale: account recovery was not included in the four categories. The requirements note proposes that a password is not stored as the text the person typed. That proposal is unsigned.

Evidence before implementation: the owner's confirmation of who may recover or reset. Confirmation of the password rule before a reset is built. A later test shows the audit row contains no password and that recovery did not add a grant.

Policy: who may recover, reset, or clear a lock. Safeguard: the stored secret is not the typed password, and the audit row does not contain it.

Proposed owner intent: ACCEPT that the account holder may initiate recovery, and that recovery does not grant a company membership or a role. Who else may set a password, and who may clear a lock, are still unwritten. Not a formal signature.

## 7. Exceptional access

Owner decision: which event qualifies, how long access lasts, which records it may touch, and who reviews it afterward.

Recommended option: each use is one recorded exception with an end time, a record list, and a named reviewer. It is not a standing role and it is not access to every company.

Rationale: the recorded intent asks for exceptional access to be controlled, accountable, and audited, and rejects an unrestricted all-company administrator.

Evidence before implementation: at least one qualifying event, the duration, the record list, and the reviewer, written by the owner. A later test shows the access fails after the end time and does not open another company.

Policy: the event, the duration, the records, and the reviewer. Safeguard: the grant expires, the next request checks it, and it does not include payroll or attendance unless a later signed grant says so.

Proposed owner intent: ACCEPT time-limited exceptional access with a recorded reviewer. The qualifying event, the length of the limit, and the record list are still unwritten. Not a formal signature.

## 8. Role revocation

Owner decision: how a revoked grant stops, and when the last role assigner may be removed.

Consistency proposal, not a signature:

- Every request checks authorization. That check is mandatory.
- When the system can reliably invalidate the affected open session, it does so immediately. Reliable means the session cannot complete another action after the revocation.
- When the system cannot reliably invalidate that session, the session may remain, and the revoked permission is rejected on the next request. That is the fallback. There is no undefined "when practical" case.
- Removal of the last role assigner in a company is allowed only after a replacement assigner for that company is stored and the membership-and-role grant on that replacement is present. If that grant is not present, the removal does not proceed.

The earlier recommended option in this item was next-request denial alone, and a flat refusal to remove the last assigner. The consistency proposal replaces that wording. It is still proposed owner intent, marked MODIFY. It is not Step 20R.

Rationale: a check on every request is the recorded direction. Immediate invalidation is additional only where the system can actually end the session. The fallback keeps the revoked grant from surviving when invalidation is unavailable. Requiring a verified replacement stops ordinary revocation from leaving the company with no assigner.

Evidence before implementation: the owner's confirmation of this proposal in Step 20R. A test shows a following request denies the revoked grant. A test shows that, where reliable invalidation exists, the open session completes no further action. A test shows removal of the last assigner does not proceed unless the replacement grant is already stored. A test shows the removal does not delete the user or the company and does not open another company.

Policy: the session rule and the last-assigner condition. Safeguard: the mandatory check, the defined fallback, company scope, and no deletion of the user or the company.

Proposed owner intent: MODIFY, as this consistency proposal. Not a formal signature.

## 8A. Company with no role assigner

This is not item 5. Item 5 creates the first membership while the company is being set up. Item 8A is a later recovery when the company already exists and has no role assigner. Existing documents do not make item 5 that recovery.

Consistency proposal, not a signature: a controlled recovery appoints one membership-and-role assigner for that one company. It does not grant employee records, pay, or punches. It does not grant another company's records. It is not an unrestricted cross-company role.

Owner decisions still open, because no existing document names them:

- The accountable operator.
- The evidence required before the appointment.
- Who approves the recovery.
- The audit fields stored for it.

Evidence before implementation: those four owner decisions written in Step 20R, then a test that the recovery adds only the membership-and-role grant in that company.

Policy: the operator, the evidence, the approval, and the audit fields. Safeguard: one company, one grant, and no employee, pay, or punch access.

## 9. Consistency points

These are proposals. They do not sign a decision.

**Stage 5D.** The original rule remains: there is no company-administrator role, and SEC-CORE-004 remains unimplemented. The membership-and-role category in item 1 is proposed owner intent. It stays unimplemented until the owner records that decision in Step 20R with an approver, a date, and the conditions. This correction does not edit the Stage 5D file.

**Employee directory.** Item 2 is proposed intent. The active gates still permit company membership as the implemented boundary and still mark the role list unsigned. Current screens are not claimed to enforce the directory permission. The formal step before any build is a Step 20R decision for item 2 and a following update of the SEC-CORE-004 gate from that decision. This correction does not update the gate.

**Items 1 through 7.** They remain proposed owner intent awaiting formal confirmation. None is DEFER. None is a signature. Details still unwritten:

- Company creation: the fields that may be set, and the audit fields.
- Account recovery: who, besides the account holder, may set a password, and who may clear a lock.
- Exceptional access: the qualifying event, the duration, the reviewer, and the record scope.

**Matrix checklist.** `UNIVERSAL_HRMS_INITIAL_ROLE_PERMISSION_MATRIX_PROPOSAL.md` still presents immediate invalidation and next-request denial as alternatives, and it still points restoration of an assigner at the first-membership path. This correction does not rewrite that file. Item 8 and item 8A are the later proposals.

## 10. What this sitting does not decide

Flag replacement. Payroll, attendance, correction, and period close. Migration. Narrower organizational scope. Subscription and company suspension. BD-D16, BD-D17, D1â€“D6, DOC-07, DOC-08, and PRB-04. Implementation of roles, permission screens, and the directory rule.

The parent `docs` folder was not updated. Section 5 of the active gates file was not changed. Step 20R was not edited. Stage 5D's original rule was not edited.

## 11. Step 20R entry draft

This section prepares entries. It does not copy them into `UNIVERSAL_HRMS_STEP20R_MANAGEMENT_DECISION_REGISTER.md`. The register's rule stands: a decision exists only when the actual decision, the approver name and role, and the approval date are filled. Those fields are `PENDING OWNER`. No approver, role, or date is invented.

Draft labels below are not register ids. Suggested role labels remain proposals. Attendance, payroll, migration, role implementation, permission screens, and flag replacement stay unauthorized.

No entry is ready for formal signing without further owner input. AUTH-DRAFT-01, AUTH-DRAFT-02, AUTH-DRAFT-03, and AUTH-DRAFT-07 have no further policy question in the proposed text. Each still needs the owner to confirm that text and to fill the pending fields. AUTH-DRAFT-04, AUTH-DRAFT-05, AUTH-DRAFT-06, and AUTH-DRAFT-08 cannot be finalized until the unresolved details named in each entry are answered. Section 12 asks those questions. It does not change AUTH-DRAFT-01, AUTH-DRAFT-02, AUTH-DRAFT-03, or AUTH-DRAFT-07.

### AUTH-DRAFT-01. Initial role categories

| Field | Draft |
| --- | --- |
| Proposed decision | **PROPOSED â€” NOT APPROVED.** Accept in principle four company-scoped categories: self-service employee, employee records manager, organization master manager, and membership and role administrator. One category does not grant another. |
| Rationale | Recorded intent separates employee management, organization-master management, and membership-and-role administration. |
| Conditions | Labels stay proposals until this entry is signed. Stage 5D's sentence that there is no company-administrator role remains the implementation rule until then. SEC-CORE-004 stays unsigned until the register is filled. |
| Evidence required before implementation | The signed register entry. Tests that one category does not grant another, and that none grants payroll, attendance, or migration. |
| Dependencies | Stage 5D original rule. The permission rows in the matrix, which this entry does not sign one by one. |
| Implementation boundary | No role is built from this draft. No permission screen. |
| Management's actual decision | PENDING OWNER |
| Approver name/role | PENDING OWNER |
| Approval date | PENDING OWNER |
| Unresolved before finalization | None in the proposed text. The owner still confirms or corrects it. |

### AUTH-DRAFT-02. Employee directory and own profile

| Field | Draft |
| --- | --- |
| Proposed decision | **PROPOSED â€” NOT APPROVED.** Another employee's record requires the employee-records category. Own-profile access is the self-service category. Company membership alone grants neither a directory-wide read nor unrestricted record access. |
| Rationale | The requirements note proposes that membership is not permission to read every row. The matrix keeps the two reads apart. |
| Conditions | Current gates and current screens are unchanged by this draft. They are not evidence that the rule is already enforced. |
| Evidence required before implementation | The signed register entry, then a test that a member without the directory permission cannot open another employee's id, and that own-profile access cannot be used for that id. |
| Dependencies | AUTH-DRAFT-01. After a signed entry, the SEC-CORE-004 gate is updated from that writing. This draft does not update the gate. SEC-CORE-003 remains the company-membership boundary until its own decision. |
| Implementation boundary | Do not change directory screens from this draft. |
| Management's actual decision | PENDING OWNER |
| Approver name/role | PENDING OWNER |
| Approval date | PENDING OWNER |
| Unresolved before finalization | None in the proposed text. The owner still confirms or corrects it. |

### AUTH-DRAFT-03. Existing permission-flag replacement

| Field | Draft |
| --- | --- |
| Proposed decision | **PROPOSED â€” NOT APPROVED.** Keep `can_manage_employees` and `can_manage_organization_masters` unchanged. A replacement is a separate later register entry. It requires the documented mapping and passing tests. This entry does not approve that replacement. |
| Rationale | Recorded intent keeps the flags until that mapping. The gates forbid copying the employee flag onto payroll, attendance correction, or period close. |
| Conditions | No third permission boolean. Neither flag grants the other. The master-write flag stays default false. |
| Evidence required before any replacement | A later signed entry, the mapping in the matrix, and the tests named there. Not required to leave the flags as they are. |
| Dependencies | Stage 4B and Stage 5D descriptions of the two flags. |
| Implementation boundary | Do not remove or remap the flags from this draft. |
| Management's actual decision | PENDING OWNER |
| Approver name/role | PENDING OWNER |
| Approval date | PENDING OWNER |
| Unresolved before finalization | None for the decision to leave the flags in place. The replacement itself is not this entry. |

### AUTH-DRAFT-04. Company creation and first membership

| Field | Draft |
| --- | --- |
| Proposed decision | **PROPOSED â€” NOT APPROVED.** Company creation is a controlled procedure outside the four categories. It does not grant employee records, pay, or punches. The same setup procedure creates the first membership and grants only the membership-and-role administration responsibility. Employee-records and organization-master grants are assigned afterward. This procedure is not recovery of a company that already has no role assigner. |
| Rationale | The four categories exist inside a company. Recorded intent separates role administration from employee and master management. |
| Conditions | The server assigns the company. The audit row does not store a password. Membership alone does not grant employee writes or master writes. |
| Evidence required before implementation | Signed answers to the unresolved details below. A test that creation returns no other company's employees. A test that the first membership cannot create an employee or a department until those grants are assigned. |
| Dependencies | AUTH-DRAFT-01 for the membership-and-role category. Stage 5D still blocks building that category. AUTH-DRAFT-08 is a different procedure. |
| Implementation boundary | Do not create a company-setup screen from this draft. |
| Management's actual decision | PENDING OWNER |
| Approver name/role | PENDING OWNER |
| Approval date | PENDING OWNER |
| Unresolved before finalization | Who the accountable operator is. Which fields may be set. Which audit fields are stored. The entry cannot be finalized until those answers exist. |

### AUTH-DRAFT-05. Account recovery and password or lock reset

| Field | Draft |
| --- | --- |
| Proposed decision | **PROPOSED â€” NOT APPROVED.** The account holder may initiate account recovery. Recovery does not grant a company membership or a role. |
| Rationale | Recovery was not included in the four categories. The requirements note proposes that a password is not stored as typed text. That proposal is unsigned. |
| Conditions | The audit row does not contain the password. Recovery does not add an employee or master write. |
| Evidence required before implementation | Signed answers to the unresolved details below. Confirmation of the password-storage rule before a reset is built. A test that recovery adds no grant and that the audit row has no password. |
| Dependencies | AUTH-REQ-007, still unsigned. Not dependent on AUTH-DRAFT-08. |
| Implementation boundary | Do not build a reset or a lock-clearing screen from this draft. |
| Management's actual decision | PENDING OWNER |
| Approver name/role | PENDING OWNER |
| Approval date | PENDING OWNER |
| Unresolved before finalization | Who, besides the account holder, may set a password. Who may clear a lock. The entry cannot be finalized until those answers exist. |

### AUTH-DRAFT-06. Exceptional access

| Field | Draft |
| --- | --- |
| Proposed decision | **PROPOSED â€” NOT APPROVED.** Exceptional access is time-limited, has a recorded reviewer, and is not a standing role and not access to every company. |
| Rationale | Recorded intent requires exceptional access to be controlled, accountable, and audited, and rejects an unrestricted all-company administrator. |
| Conditions | The grant expires. The next request checks it. It does not include payroll or attendance unless a later signed grant says so. |
| Evidence required before implementation | Signed answers to the unresolved details below. A test that access fails after the end time and does not open another company. |
| Dependencies | None of the four categories. Not a substitute for AUTH-DRAFT-08. |
| Implementation boundary | Do not build an exceptional-access action from this draft. |
| Management's actual decision | PENDING OWNER |
| Approver name/role | PENDING OWNER |
| Approval date | PENDING OWNER |
| Unresolved before finalization | The qualifying event, the duration, the reviewer, and the record scope. The entry cannot be finalized until those answers exist. |

### AUTH-DRAFT-07. Role revocation and last-assigner protection

| Field | Draft |
| --- | --- |
| Proposed decision | **PROPOSED â€” NOT APPROVED.** Every request checks authorization. If the system can reliably invalidate the affected open session, it does so immediately. Reliable means that session cannot complete another action after the revocation. If it cannot, the session may remain and the revoked permission is rejected on the next request. The last role assigner in a company is removed only after a replacement assigner's membership-and-role grant for that company is stored. If that grant is not stored, the removal does not proceed. |
| Rationale | A check on every request is the recorded direction. Immediate invalidation applies only where the system can actually end the session. The fallback is the next request. A stored replacement stops ordinary revocation from leaving no assigner. |
| Conditions | Revocation stays in the active company. It does not delete the user or the company. A denied attempt is not stored as a successful change. This entry does not recover a company that already has no assigner. |
| Evidence required before implementation | The signed register entry. A test that the next request denies the revoked grant. A test that, where reliable invalidation exists, the open session completes no further action. A test that removal of the last assigner does not proceed unless the replacement grant is stored. |
| Dependencies | AUTH-DRAFT-01. AUTH-DRAFT-08 covers a company that already has no assigner. |
| Implementation boundary | Do not build revocation from this draft. |
| Management's actual decision | PENDING OWNER |
| Approver name/role | PENDING OWNER |
| Approval date | PENDING OWNER |
| Unresolved before finalization | None in the proposed text. The owner still confirms or corrects it. |

### AUTH-DRAFT-08. Recovery when a company has no role assigner

| Field | Draft |
| --- | --- |
| Proposed decision | **PROPOSED â€” NOT APPROVED.** Recovery of a company that already exists and has no role assigner is a controlled procedure separate from first-membership setup. It appoints one membership-and-role assigner for that one company. It does not grant employee records, pay, punches, or another company's records. |
| Rationale | Item 5 of the sitting package creates the first membership during setup. Existing documents do not make that setup the repair for a company that has lost its assigner. An unrestricted cross-company role was rejected. |
| Conditions | One company. One grant. No employee, pay, or punch access. The operator, the evidence, the approval, and the audit fields are not implied by first-membership setup. |
| Evidence required before implementation | Signed answers to the unresolved details below. A test that the recovery adds only the membership-and-role grant in that company. |
| Dependencies | AUTH-DRAFT-01. Not AUTH-DRAFT-04's setup path. |
| Implementation boundary | Do not build this recovery from this draft. Do not name an operator in code. |
| Management's actual decision | PENDING OWNER |
| Approver name/role | PENDING OWNER |
| Approval date | PENDING OWNER |
| Unresolved before finalization | The accountable operator, the evidence required, who approves the recovery, and the audit fields. Those are not invented here. The entry cannot be finalized until the owner answers them. |

## 12. Owner-response worksheet

This worksheet is for the owner. A proposal is not a decision. Approver, role, and date are not filled. AUTH-DRAFT-01, AUTH-DRAFT-02, AUTH-DRAFT-03, and AUTH-DRAFT-07 are unchanged. Step 20R was not edited.

Leave each owner-response line blank until the owner writes it. Lines marked `PENDING OWNER` are facts no existing document supports. This worksheet does not supply them.

### AUTH-DRAFT-04. Company creation

**Policy.** Who operates company creation, which fields that person may set, and which facts the audit stores.

Proposal: a designated accountable operator outside the four company categories. The operator sets `company_name` and `status`. The server sets `id`, `created_at`, and `updated_at`. `company_code`, `source_system`, and `source_key` stay empty. The audit records the actor, the company id, the name, the status, the time, and the outcome.

Alternatives still open if the owner rejects that proposal: keep creation outside the product, as the three seeded companies were; allow a `company_code`; or allow `source_key` or `source_system`. The last of those can be read as a migration identity. No company is migration-approved.

**Safeguard.** The server assigns the id and the timestamps. The audit row does not store a password. The first membership, created by this same setup, still receives only the membership-and-role responsibility. That grant is not reopened here.

Operator name: PENDING OWNER

Operator job title: PENDING OWNER

Owner response:

### AUTH-DRAFT-05. Password recovery

**Policy.** The account holder initiates recovery through a verified process. Recovery grants no company membership and no role.

Proposal: that account-holder path only. This worksheet does not decide that another person may set a password. It does not decide that account locks exist.

Alternatives, if the owner wants them, are a later authority outside the four categories, or the membership-and-role category. The sitting package keeps password setting out of those four categories. Neither alternative is proposed here.

**Safeguard.** When a reset is built, the password is not stored or compared as the text the person typed, and the audit row does not contain it. AUTH-REQ-007 states that rule as an unsigned proposal. The verified process has to prove the account holder without adding a grant. The steps of that proof are not specified in the existing records.

Does another person set a password? Explicit owner question. Answer:

Do account locks exist, and who may clear one? Explicit owner question. Answer:

Owner response on account-holder recovery:

### AUTH-DRAFT-06. Exceptional access

**Policy.** Exceptional access requires an owner-approved closed list of event types, a maximum duration, an independent reviewer, and a permitted record scope. Payroll and attendance stay outside that scope unless a separate authorization includes them. This access is not AUTH-DRAFT-08.

Proposal: the owner writes the closed list and the maximum duration before any use. The reviewer does not receive the access. The scope is one named company, excluding payroll and attendance, until the end time.

Alternatives that the records do not support as proposals: an operator-declared emergency with no list; a reviewer who also receives the access; a reviewer who acts in every company; pay or attendance inside the scope; access in every company.

**Safeguard.** The grant has an end time, expires, and is checked on the next request. It is not a standing role.

Event list: PENDING OWNER

Maximum duration: PENDING OWNER

Reviewer name and job title: PENDING OWNER

Owner response:

### AUTH-DRAFT-08. No-assigner recovery

**Policy.** This is a controlled procedure of its own. It is not the first-membership setup in AUTH-DRAFT-04.

Proposal: a written request names the one company and the one replacement assigner. A second person, who is not the operator and not the new assigner, approves before the grant is stored. The audit records the actor, the approver, the company id, the user who received the membership-and-role grant, the time, and the outcome. The grant is that one responsibility in that one company. It does not include employee records, pay, or punches.

Alternatives the records do not support as proposals: any remaining member appoints the assigner; the operator approves alone; the new assigner approves; employee records are read to justify the appointment; exceptional access is used as this recovery.

**Safeguard.** The appointment does not proceed without the stored approval. The audit row does not store a password or employee, pay, or punch values. This does not design an audit table.

Operator name and job title: PENDING OWNER

What, beyond the company and the replacement assigner, makes the written request sufficient: PENDING OWNER

Approver name and job title: PENDING OWNER

Owner response:

AUTH-DRAFT-01, AUTH-DRAFT-02, AUTH-DRAFT-03, and AUTH-DRAFT-07 were left unchanged.

The parent `docs` folder was not updated. Section 5 of the active gates file was not changed. Step 20R was not edited.

OWNER-RESPONSE WORKSHEET FOR UNFINISHED AUTHORIZATION DRAFTS

## 13. Owner decision meeting sheet

This sheet orders the meeting. It does not select an option, name a person, or fill Step 20R. A verbal agreement is not approval. The register requires the actual decision, the approver name and role, and the approval date before an entry exists.

### Ready for confirmation

These four proposed texts have no further policy question. The owner confirms or corrects each one. Confirmation here is not the register entry.

| Draft | Proposed decision, not yet signed |
| --- | --- |
| AUTH-DRAFT-01 | Four company-scoped categories in principle: self-service employee, employee records manager, organization master manager, and membership and role administrator. One does not grant another. |
| AUTH-DRAFT-02 | Another employee's record requires the employee-records category. Own-profile access is separate. Membership alone grants neither. |
| AUTH-DRAFT-03 | Keep both existing permission flags. Replacement is a later entry and needs the documented mapping and passing tests. |
| AUTH-DRAFT-07 | Check every request. Invalidate the affected session immediately when that invalidation is reliable. Otherwise reject the revoked permission on the next request. Remove the last role assigner only after the replacement grant is stored. |

Owner confirmation of AUTH-DRAFT-01:

Owner confirmation of AUTH-DRAFT-02:

Owner confirmation of AUTH-DRAFT-03:

Owner confirmation of AUTH-DRAFT-07:

### Still requiring owner input

Section 12 holds the proposals. The owner still has to answer:

- AUTH-DRAFT-04: operator name and job title.
- AUTH-DRAFT-05: whether another person may set a password. Whether account locks exist, and who may clear one.
- AUTH-DRAFT-06: the event list, the maximum duration, and the reviewer name and job title.
- AUTH-DRAFT-08: operator name and job title, what makes the written request sufficient beyond the company and the replacement assigner, and the approver name and job title.

### Blocked until formal approval

Role implementation, permission screens, and enforcement of the directory rule. Replacement of the two flags. Attendance, payroll, correction, period close, and migration. A narrower organizational scope. Company creation, account recovery, exceptional access, and no-assigner recovery as product procedures. Stage 5D's original rule remains: no company-administrator role is implemented, and SEC-CORE-004 remains unimplemented. The implementation gates were not changed.

### What completes Step 20R

For each draft the owner accepts, the owner adds an entry to `UNIVERSAL_HRMS_STEP20R_MANAGEMENT_DECISION_REGISTER.md` and fills:

- Management's actual decision
- Approver name and role
- Approval date
- Conditions or exceptions

Those four fields are not filled in this sheet. AUTH-DRAFT-04, AUTH-DRAFT-05, AUTH-DRAFT-06, and AUTH-DRAFT-08 also need the section 12 answers written into the decision text before that entry can be finalized.

OWNER DECISION MEETING SHEET PREPARED

## 14. Consolidation proposal

Sections 11 through 13 stay as the evidence trail. They are not to be extended with another worksheet. This section is the single list for the owner. It is a proposal. It does not sign a decision, fill Step 20R, or change a gate. Stage 5D's original rule remains: there is no company-administrator role, and SEC-CORE-004 remains unimplemented. The owner's earlier ACCEPT marks and conversational choices stay intent awaiting formal confirmation.

### One architecture

A user has one or more company memberships. The session has one active company. A role is a set of permissions on that membership. It does not name a company, and it does not name an approval step.

The architecture covers:

- Company membership, with the two existing flags left in place until a tested mapping replaces them.
- The four role categories in AUTH-DRAFT-01, held on the membership.
- Active-company isolation on every request. Another company's id is refused.
- Record-level authorization. A filtered list does not authorize the next id. Own-profile access and access to another employee's record are different permissions.
- Organizational scope. The enforced boundary is the active company. A later branch, department, or employee limit is a further condition on the same check. It is not enforced, and it is not a new permission flag.
- Workflow-state checks. The server reads the current state and refuses a transition the signed workflow does not allow. This architecture does not choose those orders.
- Audit logging of a security-relevant change: actor, resource, operation, time, and outcome. A password is not stored in the audit. This does not design the audit table.
- Revocation as in AUTH-DRAFT-07: every request is checked; the affected session is invalidated immediately when that invalidation is reliable; otherwise the revoked permission is rejected on the next request; the last role assigner is removed only after the replacement grant is stored.

### What is not a separate owner decision

Once the owner confirms this architecture, the following are design choices or safeguards inside it. They do not each need a register entry.

| Matter | Where it sits |
| --- | --- |
| AUTH-DRAFT-01, AUTH-DRAFT-02, AUTH-DRAFT-03, and AUTH-DRAFT-07 | One confirmation of this architecture, not four gates |
| Server-generated company id and timestamps; name and status set by the operator; empty code and source fields | Safeguards and the field rule already proposed for company creation |
| Audit facts for company creation and for no-assigner recovery | The audit rule above |
| First membership receives only the membership-and-role category | A condition of this architecture |
| Account-holder recovery grants no membership and no role | A condition of this architecture. The proof steps are engineering |
| Password not stored as typed text | Safeguard. AUTH-REQ-007 stays an unsigned proposal until the register says otherwise |
| Exceptional access is time-limited, independently reviewed, one company, and excludes payroll and attendance unless a later grant includes them | The shape is this architecture. The list, the duration, and the reviewer's identity are the remaining policy |
| No-assigner recovery is not first-membership setup | A condition of this architecture |
| Middleware, table names, and the permission-screen layout | Engineering, after implementation is authorized. Not chosen here |
| BD-D16, BD-D17, PRB-04, DOC-07, and DOC-08 | Their own later decisions. They do not block this architecture |
| Which screens later receive a narrower scope | SEC-CORE-003 stays unsigned. It does not block this architecture |

### Genuine owner decisions

| Decision | Proposal | Consequence of the proposal | Consequence of leaving it open |
| --- | --- | --- | --- |
| Confirm this architecture in one Step 20R entry | Accept the text in this section, including AUTH-DRAFT-01, AUTH-DRAFT-02, AUTH-DRAFT-03, and AUTH-DRAFT-07 | One register entry. Stage 5D still blocks building the membership-and-role category until that entry is signed | The eight drafts stay separate, and implementation stays unauthorized |
| Controlled operator | One designated operator outside the four categories, used for company creation and, as a separate procedure, for no-assigner recovery | Company setup and recovery have an accountable class of person. The name and job are `PENDING OWNER` | No in-product way to create a company or restore an assigner |
| Other-person password reset, and account locks | Do not add either unless the owner writes that they exist | Recovery stays account-holder only | If the owner needs them, they remain impossible until named |
| Exceptional-access list, maximum duration, and reviewer identity | The owner writes the closed list and the duration before any use. The reviewer does not receive the access. Name and job are `PENDING OWNER` | Exceptional access cannot be used until those three answers exist. Payroll and attendance stay excluded | No exceptional access |
| What makes a no-assigner request sufficient | The request names the company and the replacement assigner. Any further fact is `PENDING OWNER`. A second person, who is not the operator or the new assigner, approves. That person's name and job are `PENDING OWNER` | Recovery cannot run until the owner accepts this shape and fills the pending identities | A company with no assigner has no recovery procedure |

### Still required before implementation is authorized

The owner writes one Step 20R entry for this architecture, with the actual decision, the approver name and role, the approval date, and the conditions. That writing has not been done. SEC-CORE-003 and SEC-CORE-004 stay unsigned. The implementation gates stay unchanged. After that entry, the SEC-CORE-004 gate is updated from it before any role is built. This proposal does not make that update.

Implementation of RBAC, permission screens, directory enforcement, flag replacement, attendance, payroll, and migration stays unauthorized.

RBAC CONSOLIDATION PROPOSAL PREPARED

# Universal HRMS — Authorization Decision-Record Delta

Date: 2026-10-16

Status: **OWNER INTENT RECORDED FOR CONFIRMATION.** This file prepares the decision record. It is not Step 20R. It does not sign SEC-CORE-003, SEC-CORE-004, the access brief, or any other gate. It does not authorize implementation. BD-D16, BD-D17, D1–D6, DOC-07, DOC-08, and PRB-04 were not changed. No gate row was changed. No company is migration-approved.

Source of the intent: the owner's message accepting the recommended direction, for the purpose of preparing these records. The formal register remains `UNIVERSAL_HRMS_STEP20R_MANAGEMENT_DECISION_REGISTER.md`. That register has no access-model entry, and its approver and date fields for open items stay blank. A decision is formal only when the owner writes it there with an approver, a date, and the conditions.

## 1. Accepted direction

These five points are the owner's direction for the record. They are not a role list, a procedure, or a build instruction.

1. Permissions are company-scoped roles with specific permissions. No further independent permission boolean is added. `can_manage_employees` and `can_manage_organization_masters` stay in place until an approved and tested mapping replaces them.
2. An unrestricted administrator who can act in every company is rejected. Company setup, membership recovery, and exceptional access are controlled operational responsibilities. Each one, when later written, has a named accountable actor and an audit record.
3. One user may hold more than one company membership. The session has one active company context. Every request validates that membership and the authorization for that request.
4. User-membership administration and role administration are one company-scoped responsibility. That responsibility is separate from employee management, from department and designation management, and from payroll and attendance approval.
5. Company isolation is the foundation that is enforced. A later branch, department, or employee limit may be designed as a further condition on the same check. That limit is not enforced until a separate approval.

## 2. What still requires formal confirmation

| Item | Why it is not yet a signed decision |
| --- | --- |
| SEC-CORE-004 | The gates file still says the role list is unsigned. This delta names no roles and no permission matrix. Step 20R has no row for it |
| SEC-CORE-003 | The gates file still says the scope model is unsigned. Direction point 5 accepts company isolation and reserves a narrower limit. It does not approve that limit for any screen |
| The access brief | Its status line remains unsigned. Direction point 2 rejects an unrestricted all-company administrator. It does not adopt the brief's Platform Super Admin surface |
| Replacement of the two flags | The owner requires an approved and tested mapping first. The mapping, the tests, and the removal of the flags are not written |
| Step 20R | The five points above are not copied into the register. Approver, date, and conditions are blank |

No proposed change to the gate rows follows from this intent. The SEC-CORE-003 row already permits company membership as implemented and prohibits a narrower scope until approval. The SEC-CORE-004 row already permits `can_manage_employees` on the employee module only, and it prohibits copying that flag onto payroll, attendance correction, or period close. Those rows stay as written.

## 3. Proposed details that are not yet procedures

The owner asked that the controlled responsibilities be documented. The sentences below are the bounds of that later writing. They are not the procedures.

| Responsibility | Bound already stated | Still unwritten |
| --- | --- | --- |
| Company setup | It is controlled, accountable, and audited. It is not an unrestricted all-company role | Who may create a company, what they may change, and the audit fields |
| Membership recovery | It is controlled, accountable, and audited. It is not a grant to manage employees or masters | Who may restore a membership, what evidence is required, and whether the first membership of a new company uses this same path |
| Exceptional access | It is controlled, accountable, and audited. It is not a standing role in every company | What event qualifies, how long it lasts, which records it may touch, and who reviews it afterward |
| Role assignment | The company-scoped responsibility in direction point 4 is the intended assigner, inside the active company, and only for permissions the owner has already confirmed | Role names, the permission list, revocation, the effect on an open session, and the case where the last assigner loses that grant |
| Narrower scope | The check may later add one limit. Company isolation remains the rule until a separate approval | Which screens, and whether the limit is a branch, a department, or a set of employees |

Password recovery, subscription, and company suspension are not named in the accepted direction. They stay unwritten. LIFE-CORE-006, an inactive or suspended employee, is a different subject from company suspension.

## 4. Stage 5D reconciliation

`UNIVERSAL_HRMS_ORGANIZATION_MASTERS_PERMISSION_DECISION.md` is working direction, not an owner signature. Its rule remains in that file, including the sentence that there is no company-administrator role and that SEC-CORE-004 remains unimplemented.

That sentence and direction point 4 address different moments:

- Stage 5D governs the product as implemented. The two flags stay separate. Neither flag grants the other. Neither flag grants payroll, attendance, or period close. No company-administrator role is implemented.
- Direction point 4 is owner intent for a later responsibility limited to user membership and role administration inside one company. It does not add a third boolean. It does not grant employee writes, master writes, or payroll or attendance approval.

The Stage 5D file was not rewritten into a signed decision. An added note there points at this delta and leaves the original rule in place. Until Step 20R confirms point 4, and until the approved tested mapping exists, implementation continues under the Stage 5D rule.

## 5. Conflicts after this intent

| Earlier conflict | Result of this intent |
| --- | --- |
| Stage 5D says there is no company-administrator role. The agenda proposed a company-administration responsibility | Reconciled as two layers, in section 4. The Stage 5D sentence is not deleted |
| The owner-review paper proposed leaving SEC-CORE-004 unsigned. The agenda proposed accepting a role model | The direction is accepted for this record. The gate stays unsigned until Step 20R |
| The owner-review paper said a narrower scope can wait. The agenda included it in the architecture questions | The owner answered it: company isolation is the foundation; enforcement of a narrower limit waits for a separate approval. SEC-CORE-003 stays unsigned |
| The access brief names a Platform Super Admin surface and also chooses no platform role | An unrestricted all-company administrator is rejected. The Platform Super Admin surface is not adopted. The three controlled responsibilities remain unwritten |

## 6. What remains blocked

Implementation of authorization, a permission screen, a role matrix, a third permission boolean, and any narrower scope. Attendance, payroll, leave, and period close. A named company load. DOC-07, DOC-08, and PRB-04. BD-D16, BD-D17, and D1–D6, which were not opened. Section 5 of the active gates file still does not authorize the next implementation milestone.

The next owner step is to confirm this delta in Step 20R, with an approver, a date, and the conditions, or to correct a point before that writing. This file is not that confirmation.

The parent `docs` folder was not updated.

AUTHORIZATION DECISION-RECORD DELTA PREPARED

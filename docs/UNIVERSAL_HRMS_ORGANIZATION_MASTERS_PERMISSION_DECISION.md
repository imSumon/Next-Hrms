# Universal HRMS — Organization Masters Permission

Date: 2026-10-15

Status: **WORKING DIRECTION FOR STAGE 5D.** This record is not an external approval. No approver signature is recorded. The named CTO in the Stage 5C proposal did not sign this file. D1, D2, D3, D4, and D6 stay unsigned. Step 20R fields were not filled.

## Source

Stage 5B prepared Option A and left it unsigned. Stage 5C kept that option proposed. The Stage 5D instruction adopts Option A as the working product direction for implementation and says not to wait for an external signature.

## Rule

`company_memberships.can_manage_organization_masters` is a separate boolean. The database default is false.

- An authenticated user with an active company context may read that company’s departments and designations.
- Creating a row, changing its name, or changing its status requires `can_manage_organization_masters` on the active membership for the session company.
- `can_manage_employees` does not grant those writes.
- The new flag does not grant employee, episode, payroll, attendance, or period-close writes.
- There is no company-administrator role. SEC-CORE-004 remains unimplemented.

## Master rules used with this direction

These come from schema section 4.2, the branch table, and ORG-CORE-005 and ORG-CORE-006. They are not new business policies.

- A department and a designation are separate company-owned tables. Neither has `branch_id` or an employee foreign key.
- `code` is optional. A blank code is stored as null. A non-null code is unique per company per table. The same code may exist on the other master and in another company.
- `code` is set at creation and is rejected on update.
- `name` is required. It is not unique.
- `status` is required on create and on update. The allowed values are `Active` and `withdrawn`. The server does not supply a status when the request omits one.
- Withdrawal keeps the row. Status may be set to either allowed value.
- Name and status changes are audited in the same transaction. Section 4.2 does not require a creation audit, and this direction does not add one.
- Employees are not assigned to either master.

## Owner intent recorded on 2026-10-16

This section does not replace the rule above and does not sign SEC-CORE-004.

The owner recorded intent, for preparation of the authorization record, to establish a later company-scoped responsibility for user membership and role administration. That responsibility is separate from employee management, from these organization masters, and from payroll and attendance approval. It does not add a third permission boolean. The two flags in the rule above stay in force until an approved and tested mapping replaces them.

`UNIVERSAL_HRMS_AUTHORIZATION_DECISION_RECORD_DELTA.md` is that preparation record. It is not Step 20R. Until the owner confirms it there, the sentence "There is no company-administrator role" remains the implementation rule.

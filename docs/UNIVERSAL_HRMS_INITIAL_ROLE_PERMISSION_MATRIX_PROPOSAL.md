# Universal HRMS — Initial Role and Permission Matrix Proposal

Date: 2026-10-16

Status: **PROPOSAL FOR OWNER REVIEW.** This file prepares a first permission catalog. Recommendations in sections 2, 3, and 6 await formal owner confirmation. They are not that confirmation. This file does not authorize implementation. It does not change a formal decision status. SEC-CORE-003 and SEC-CORE-004 stay unsigned. The access brief stays unsigned. Step 20R was not edited. The implementation gates were not edited. Stage 5D's original rule was not edited. No company is migration-approved.

Suggested role labels in this file are proposals. They are not approved names. Permission identifiers are labels for this proposal. They are not decision ids and they are not schema.

Sources: the documentation authority, the active control center, `UNIVERSAL_HRMS_AUTHORIZATION_REQUIREMENTS.md`, the owner-review paper, the decision agenda, the decision-readiness checklist, `UNIVERSAL_HRMS_AUTHORIZATION_DECISION_RECORD_DELTA.md`, and `UNIVERSAL_HRMS_ORGANIZATION_MASTERS_PERMISSION_DECISION.md`. Legacy audits were not repeated.

## 1. Three layers

| Layer | What it may support | What it does not do |
| --- | --- | --- |
| Confirmed existing behavior | Login, one active company on the session, POST logout, the employee directory and profile, display-name history, episode rules already in force, and the two membership flags | A role list. A permission screen. Employee self-service as a separate signed-in employee session |
| Owner direction recorded as intent | Company-scoped roles with specific permissions. No new permission boolean. The two flags remain until an approved and tested mapping. No unrestricted all-company administrator. Many memberships, one active company, checked on every request. A company-scoped membership and role responsibility, separate from employee management, organization masters, and payroll or attendance approval. Company isolation now. Narrower scopes designed and not enforced | Step 20R. A signed role list. Removal of the two flags |
| Recommendations in this file | The catalog and the suggested labels below | Implementation. Formal approval. The mapping that would retire the two flags |

AUTH-REQ-001 through AUTH-REQ-009 remain proposed requirements. This catalog uses their shape for the five areas below. It does not mark them signed.

## 2. Suggested roles

These labels are proposals only.

**Recommendation, awaiting formal owner confirmation.** Accept these four categories in principle: self-service employee, employee records manager, organization master manager, and membership and role administrator. That acceptance would not approve the words as signed role names, would not fill Step 20R, and would not sign SEC-CORE-004. The fourth label stays under the Stage 5D rule in section 7.

| Proposal label | Holds which proposal permissions | What it does not hold |
| --- | --- | --- |
| Self-service employee | P-ESS-01 | Another employee's rows. The two existing flags. Membership or role administration |
| Employee records manager | P-EMP-01 through P-EMP-04 | Organization-master writes. Membership or role administration. Payroll. Attendance |
| Organization master manager | P-ORG-01 and P-ORG-02 | Employee writes. Membership or role administration. Payroll. Attendance |
| Membership and role administrator | P-MEM-01, P-MEM-02, P-ROLE-01, P-ROLE-02 | Employee writes. Organization-master writes. Payroll. Attendance. Any grant this catalog excludes |

One membership may hold more than one of these labels. Holding one does not grant another. The Stage 5D contradiction in section 7 applies to the fourth label.

## 3. Proposed permission catalog

Company boundary for every row: the active company only. A requested company id is not the boundary. No row grants a branch, department, or employee-set limit. No row is an approval step. Payroll, attendance approval, attendance correction, leave, overtime, period close, migration, subscription, company suspension, and platform-wide access are absent on purpose.

### Employee self-service

| Id | Allowed action | Record relationship | Approval | Audit |
| --- | --- | --- | --- | --- |
| P-ESS-01 | Read that employee's own profile | The session's employee identity is that employee row. `employees.id` and the employee code are different values. A posted id becomes the employee only when the server binds it | None | None for this read |

A self-service write is not in this catalog. The checkpoint does not name which profile fields an employee may change. Preview ESS screens are not this permission.

The link from a user account to an employee row is not defined in the sources read for this proposal. P-ESS-01 applies only when that link already exists for the active company. This file does not define the link.

### Employee directory and profile management

These four permissions are the proposed reading of `can_manage_employees`. Section 5 is the mapping. It is not approval to replace the flag.

**Recommendation, awaiting formal owner confirmation.** Listing or opening another employee's record requires P-EMP-01. Membership in the company does not grant that read. P-ESS-01 remains the employee's own profile and does not include the directory. The running application is unchanged by this recommendation. The gates still describe company membership as the implemented boundary.

| Id | Allowed action | Record relationship | Approval | Audit |
| --- | --- | --- | --- | --- |
| P-EMP-01 | List employees in the active company, and read an employee profile that is not the actor's own profile | The employee row belongs to the active company. The actor holds P-EMP-01. Holding a membership, or holding only P-ESS-01, does not | None | None for this read |
| P-EMP-02 | Create an employee identity in the active company, including the optional first episode the create form already describes | The new row is stored for the active company. The server sets the company. A posted company id does not | None | No signed audit rule for creation. Display-name history does not cover creation |
| P-EMP-03 | Change `display_name` on an employee in the active company | The employee row belongs to the active company | None | The checkpoint already records previous name, new name, actor, and time for this field |
| P-EMP-04 | Create and correct episodes under the rules already in force: at most one `Open` episode, no last working date while `Open`, rejoin inserts a new episode and leaves the previous episode, and a non-`Open` episode is not set back to `Open` | The episode belongs to an employee in the active company. The employee code is not changed | None | No signed audit rule beyond the display-name history |

P-EMP-02 and P-EMP-04 do not include a legacy load. D1–D6 stay unsigned.

The control center says employee writes need `can_manage_employees`, and it also says the profile can change `display_name` inside the active company. The display-name slice does not restate the flag. Section 7 leaves that gap open.

### Department and designation master management

Read of these masters is confirmed existing behavior for any authenticated user with an active company. It is not one of the two flags. P-ORG-01 records that read. P-ORG-02 is the proposed reading of `can_manage_organization_masters`.

| Id | Allowed action | Record relationship | Approval | Audit |
| --- | --- | --- | --- | --- |
| P-ORG-01 | Read departments and designations in the active company | The master row belongs to the active company. It is not an employee relationship. Employees are not assigned to either master | None | None for this read |
| P-ORG-02 | Create a department or designation, change its name, or set its status to `Active` or `withdrawn` | The master row belongs to the active company. `code` is set only at creation. Withdrawal keeps the row | None | Name and status changes are already audited in the same transaction. The Stage 5D direction does not require a creation audit |

P-ORG-02 does not grant branch writes or employee assignment.

### User membership management

These permissions are not implemented. They are the proposed content of the company-scoped responsibility in the decision-record delta. They do not add a third boolean.

| Id | Allowed action | Record relationship | Approval | Audit |
| --- | --- | --- | --- | --- |
| P-MEM-01 | Add an existing user to the active company, or remove that membership | The membership is for the active company. The actor's own authority is also a membership of that company. This permission does not create the company and does not create the first membership of a company that has none | None | SEC-CORE-011 names an audit of permission changes. The audit fields are not designed. This proposal does not sign that design |
| P-MEM-02 | Set or clear `can_manage_employees` and `can_manage_organization_masters` on a membership in the active company | Same company boundary as P-MEM-01. The actor cannot grant a permission this catalog excludes | None | Same as P-MEM-01. A denied attempt is not stored as a successful grant |

P-MEM-02 exists only while the two flags remain. After an approved and tested mapping, the same responsibility would assign the proposed roles instead. That replacement is not approved here.

### Role assignment and revocation

| Id | Allowed action | Record relationship | Approval | Audit |
| --- | --- | --- | --- | --- |
| P-ROLE-01 | Assign one of the proposed labels in section 2 to a membership in the active company | The membership and the actor's membership are in the active company. The assigned label must be one the owner has confirmed. This proposal is not that confirmation | None | Same as P-MEM-01 |
| P-ROLE-02 | Remove one of those labels from a membership in the active company | Same company boundary. Revocation does not delete the user and does not delete the company | None | Same as P-MEM-01 |

P-ROLE-02 does not say what happens to a session already open, and it does not say what happens when the last person who holds P-ROLE-01 loses it. Those procedures are section 6.

## 4. Excluded from the initial grant set

No proposed role above includes these. The source documents do not authorize them in this catalog.

- Payroll reads or writes, including pay, tax, and bank values.
- Attendance approval, attendance correction, leave, overtime, and period close.
- Employee migration, a legacy load, and reconciliation execution.
- Subscription and company suspension.
- A platform-wide or all-company grant. Exceptional access is section 6, not a role in this catalog.
- Enforcement of a branch, department, or employee-set scope.
- Branch writes, and assignment of an employee to a department, designation, or location.
- Product-default administration.
- Password recovery, and reading or writing a password value.
- The file cabinet. DOC-08 stays unresolved.

## 5. Proposed mapping of the two flags

The two flags stay as they are. This section does not approve a mapping and does not implement one.

Evidence already recorded:

- Stage 4B: employee and episode writes need `can_manage_employees`. That flag is not a universal role.
- Stage 5D: `can_manage_organization_masters` defaults to false. It does not grant employee, episode, payroll, attendance, or period-close writes. `can_manage_employees` does not grant master writes. There is no company-administrator role. SEC-CORE-004 remains unimplemented.
- The gates permit `can_manage_employees` on the employee module only. They prohibit copying that flag onto payroll, attendance correction, or period close.
- The owner's recorded intent keeps both flags until an approved and tested mapping replaces them, and it adds no further permission boolean.
- Department and designation reads already require an authenticated user and an active company. They do not require the master-write flag. That read is P-ORG-01. It is not an employee-directory grant.

Requirements before any future replacement:

- The owner confirms the mapping in Step 20R, with an approver, a date, and the conditions.
- The tests below have been run and have passed.
- Until that confirmation, screens keep the flags they use today.
- The replacement adds no third boolean and does not copy either flag onto payroll, attendance, correction, or period close.

This mapping is a recommendation. It is not approved. It is not a change to the application.

| Flag on the active membership | Proposed label while the flag is true | When the flag is false |
| --- | --- | --- |
| `can_manage_employees` | Employee records manager, and therefore P-EMP-01 through P-EMP-04 | That label is absent. P-ESS-01 is not implied by either state of this flag |
| `can_manage_organization_masters` | Organization master manager, and therefore P-ORG-02. P-ORG-01 stays with every authenticated member of the active company, which is the Stage 5D read rule | P-ORG-02 is absent. P-ORG-01 remains |

Neither flag maps to Membership and role administrator. Neither flag grants the other. A membership may have both flags. The default of `can_manage_organization_masters` remains false.

Tests that would have to pass before a later replacement, and which this file does not run:

- A membership with only `can_manage_employees` can perform P-EMP-01 through P-EMP-04 and cannot perform P-ORG-02, P-MEM-01, P-MEM-02, P-ROLE-01, or P-ROLE-02.
- A membership with only `can_manage_organization_masters` can perform P-ORG-02 and cannot perform the employee-write permissions or the membership and role permissions.
- A membership with both flags false can read departments and designations and cannot perform the writes above.
- A requested id from another company is denied for every permission in section 3.
- After the directory recommendation in section 3 is confirmed, a membership that does not hold P-EMP-01 can read only the actor's own profile, and only when P-ESS-01 applies. It cannot list or open another employee. That test is not a claim about the screens running today.

## 6. Procedure checklist

These procedures are not permissions in section 3. The owner column is still unanswered. The safeguard column is how a later build stays inside bounds the records already state. A safeguard is not permission to build the procedure.

| Procedure | Owner decision, still open | Implementation safeguard | Classification |
| --- | --- | --- | --- |
| Company creation | Who may create a company. Which fields they may set. Who is accountable. Which audit fields are stored | The action does not grant employees, pay, or punches in every company. The server assigns the company. The audit row does not store a password. P-MEM-01 does not create a company | Owner: the actor, the fields, and the audit contents. Safeguard: no all-company operational grant, and no password in the audit row |
| First membership | Who attaches the first user when the company has no membership. Whether that path is membership recovery. Which initial grants that membership receives, including whether it holds P-ROLE-01 | One active company context. Membership alone does not grant P-EMP-02, P-EMP-03, P-EMP-04, or P-ORG-02. P-MEM-01 does not cover this empty case. The employee flag is not copied on as the assigner role | Owner: the actor, the path, and the initial grants. Safeguard: membership is not those write grants |
| Account recovery | Whether the person may recover the account. Who else may set a password. Who may clear a lock | When a reset is built, the password is not stored or compared as the text the person typed, and the audit row does not contain that text. Recovery does not add a role, a membership, or an employee or master write. AUTH-REQ-007 states the password rule as a proposal. It is unsigned | Owner: who may recover or reset. Safeguard: the stored secret and the audit contents |
| Exceptional access | What event qualifies. How long it lasts. Which records it may touch. Who reviews it afterward | It expires. It is not a standing role. It is audited. It is not a platform-wide grant. It does not include payroll or attendance unless a later signed grant says so. The next request checks it again | Owner: the event, the duration, the records, and the reviewer. Safeguard: expiry, audit, and no standing all-company role |
| Role revocation | Whether an open session ends immediately or the next request denies the removed grant while the session remains. Whether the last holder of P-ROLE-01 in that company can be removed. If removal is allowed, who restores an assigner | Revocation applies only to the active company. It does not delete the user or the company. A later request denies the removed grant. A denied attempt is not stored as a successful change. The actor cannot grant the removed label back in that same unchecked request | Owner: session end versus next-request denial, and the last-assigner rule. Safeguard: company scope, no deletion of the user or company, and a fresh check on the next request |

Membership recovery, as named in the decision-record delta, is the restore path for a membership. It is not account recovery. LIFE-CORE-006, an inactive or suspended employee, is not company suspension.

The last-assigner problem and the open-session question are owner decisions. They are not settled by P-ROLE-02.

## 7. Contradictions left in place

**Stage 5D.** The working-direction file still says there is no company-administrator role and that SEC-CORE-004 remains unimplemented. Its original rule was not deleted. This proposal uses the label "Membership and role administrator" for a later responsibility limited to P-MEM-01, P-MEM-02, P-ROLE-01, and P-ROLE-02. The decision-record delta calls these two layers. This file does not merge them. Building that label now would contradict the Stage 5D implementation rule.

**Directory audience.** The gates still permit company-wide access inside the active company as the implemented boundary. AUTH-REQ-001 proposes that membership alone is not permission to read every row. Section 3 recommends that another employee's record require P-EMP-01, and that P-ESS-01 stay limited to the actor's own profile. That recommendation awaits confirmation. It does not change the gates, and it does not describe the screens running today.

**Display-name actor.** Stage 4B says employee writes need `can_manage_employees`. The display-name slice says the profile can change `display_name` inside the active company and does not restate the flag. P-EMP-03 follows the Stage 4B sentence. It does not rewrite the display-name slice.

**Role names.** The decision-record delta says the role names and the permission list are still unwritten as formal facts. The labels in section 2 are candidates for that writing. They do not fill Step 20R.

**SEC-CORE-004 gate.** The gate still says the role list is unsigned, and it still prohibits copying `can_manage_employees` onto payroll, attendance correction, or period close. This catalog does not copy the flag. It also does not change the gate.

**Platform surface.** The access brief names a Platform Super Admin surface and chooses no platform role. The owner's recorded intent rejects an unrestricted all-company administrator. This catalog grants no platform permission. The brief stays unsigned.

## 8. What this file does not do

This matrix does not authorize implementation. It does not change SEC-CORE-003, SEC-CORE-004, the access brief, Stage 5D's original rule, Step 20R, or any other formal decision status. It does not add a permission boolean. It does not approve the flag mapping. It does not enforce a narrower scope.

The parent `docs` folder was not updated. Section 5 of the active gates file was not changed.

INITIAL ROLE AND PERMISSION MATRIX PROPOSAL UPDATED

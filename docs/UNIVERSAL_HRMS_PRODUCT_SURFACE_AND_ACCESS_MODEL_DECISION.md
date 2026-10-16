# Universal HRMS — Product Surfaces and Access Model

Date: 2026-10-16

Status: **UNSIGNED DECISION BRIEF.** This file prepares a product-owner review. It is not an approval, not a working direction for implementation, and not a signature. No approver is named. Step 20R was not edited. SEC-CORE-003, SEC-CORE-004, and every BD decision stay at the status already recorded in the control center and the decision gates. D1–D6 stay unsigned. No company is migration-approved.

This brief does not add a role catalogue, a permission matrix, an organizational-scope rule, a platform-principal model, or role-assignment rules.

## 1. Current evidence and approved principles

### Accepted principles

These are already in force. This brief does not reopen them.

| Principle | Source | What it already decides |
| --- | --- | --- |
| One product serves many companies. Client differences are configuration or an extension, not an alias branch | AP-01, AP-02, ADR-001 | Proedge, Viyellatex, Atlas, and CEVA are evidence, not templates |
| The company is the tenant. Every operational fact belongs to one company | ADR-002, ORG-CORE-002, SEC-CORE-002 | Isolation is a rule on the operation. A physical database per client is not required |
| The server authorizes the action. A hidden control is not the control | AP-07, ADR-013, SEC-CORE-001, SEC-CORE-005 | Direct URLs and AJAX follow the same check as the screen |
| Approval is an ordered configurable workflow. The actor is a user | ADR-009 | Boss and grand-boss columns are not the authorization model |
| Product, then company, then a lower level only when that policy allows it | CFG-CORE-001 | The resolution order is accepted. The policy values are not |
| Extensions default off | ADR-014, AP-08 | A company switch is not a role |
| Organization nodes are approval scopes | Step 20 final domain decisions | A node is not a department, a branch, a reporting link, or a boss column |
| A location employment fact cites `location_nodes.id` | Step 20 final domain decisions | Branch remains a separate assignment. Assignment columns are still undefined |
| Rejoin keeps the employee and the employee code and inserts a new episode | LIFE-CORE-008, Step 20B | Unchanged by this brief |

### Implemented foundation to preserve

The Laravel application already has session login, an active company on the membership, POST logout, employee directory and profile, display-name edit and history, episode protections, and department and designation masters.

Two membership flags exist:

- `can_manage_employees` gates employee and episode writes. It is the employee-module grant.
- `can_manage_organization_masters` gates department and designation writes. The default is false. The Stage 5D file records this as working direction, not an external signature. It does not grant employee, episode, payroll, attendance, or period-close writes. `can_manage_employees` does not grant master writes.

Reads of departments and designations require an authenticated user and an active company. They do not require the master-write flag. Employees are not assigned to either master. Branch writes are not authorized. This brief does not change those flags or those screens.

A later focused run reported 50 passed tests and 500 assertions. That run is automated evidence. It is not signed-in browser UAT.

### Legacy evidence, not a model to copy

AUD-03 found custom session checks in the Yii admin application. The level strings in source are `supadmin`, `admin`, and `admin_user`. Eight appadmin controllers have no level test. `moduleAccess()` checks a module name and is commented out inside appadmin. `user_companies_module_controllers` is read by grids to choose buttons. AUD-03 classifies those level names, the company-list table, and the button grants as legacy only. It leaves unsigned which actions a role may call, and whether a role is global or company-scoped.

AUD-02 and AUD-07 show the same split on workflow authority. `actionApproveatt` for `proedge` and `vtl` changes an application status and does not rewrite the day. Other aliases use other status numbers, including through `actionAttendanceapprovernms`. That variation is evidence that approval steps are configuration. It does not sign BD-D17.

The executive summary describes today's permissions as a module, company, controller, and action structure, and draws a target stack of tenant, identity and RBAC, common modules, and a policy engine. AUD-03 is the source finding for the admin application: much of that structure hides buttons or checks a level string, and it does not satisfy SEC-CORE-001. The summary's target stack is direction. It is not a signed role list.

The Master Workplan puts roles in the future-requirements phase and tenancy and security in the target-architecture phase. It says not to start by coding screens. The Universal HRMS proposal names role-based permissions and company-level access control as a technology line. Its Phase 1 module list includes provident fund, which SCOPE-01 still defers. Its weeks 5–10 describe fleet, driver, and GPS work. Those weeks are not this authorization model. The proposal does not override the control center.

## 2. Five-surface responsibility matrix

The surfaces are responsibilities inside one product. They are not five deployable systems and not five Boolean flags.

| Surface | Intended responsibility | Company boundary | Authorization need already stated | Unresolved, and not decided here |
| --- | --- | --- | --- | --- |
| Platform Super Admin | Operate the platform: tenants and platform-level operations such as subscription and suspension, once those operations are specified | Above a single company. Must not become a default right to read every company's employees, pay, or punches | ADR-002 keeps operational facts inside one company. AUD-03 refuses to copy `supadmin` as the default cross-company role | Whether a platform principal exists, which platform actions it has, and how it is appointed |
| HRMS product administration | Maintain product defaults, shared capability switches, and extensions that stay off until a company enables them | Product level. A product default is not a company's data | CFG-CORE-001 and ADR-014 | Which settings are product defaults, and who may change a default |
| Organization / company administration | Configure one company's structure, users, access, and company settings | The active company only. SEC-CORE-002 rejects another company's data | Department and designation maintenance already uses the Stage 5D flag inside the active company | The role that may administer a company. Branch writes. Assignment columns. Any scope narrower than the company |
| HR and manager operations | Run employee administration and the workflows a company enables for attendance, leave, payroll, and related work | The active company, and only the requests and employees that the signed rules allow | SEC-CORE-006, SEC-CORE-008, SEC-CORE-009, and SEC-CORE-010 require distinct checks for approval, payroll, correction, and period close | The role list. The approval step list (BD-D17). The correction fields (BD-D16). Payroll and attendance behavior |
| Employee Self-Service | Let an employee see and submit only that employee's enabled information and requests | One employee in one company for that session | SEC-CORE-007 and ESS-CORE-001 | Which request types a company enables. Preview ESS screens are fixtures, not this surface |

### Distinctions the future model has to keep

**Roles and the permissions they grant.** SEC-CORE-004 says permissions are granted by role. The role list is unsigned. A role names actions. It does not name a company and it does not name an approval step.

**Company membership.** Membership answers which company the user may enter. The implemented active-company session is that boundary for the foundation already built. Holding a membership is not a grant to change employees or masters. The two existing flags are module grants on top of membership. AUD-03 left multi-company holding unsigned for the legacy admin user. The new application already stores memberships and requires one active company. This brief preserves that implementation. It does not decide a platform-wide rule for how many companies one person may join.

**Optional narrower organizational scope.** SEC-CORE-003 says the system shall be able to limit a user to a branch, department, or employee scope. The decision gates keep that limit blocked until the scope model is signed. Company membership, as implemented, is the permitted boundary. A narrower scope is not authorized by this brief.

**Workflow authority.** ADR-009 makes approval an ordered configurable workflow whose actor is a user. Step 20 domain decisions make an organization node an approval scope, distinct from a department and from `employees.boss`. Being allowed to open the attendance module is not the same as being the actor for one request's current step. The step list itself is BD-D17 and stays unsigned.

**Product defaults and company policy.** A product administrator may later set a default. A company administrator may later set the company's value where the policy allows it. AP-10 and ADR-015 forbid a universal numeric default where the evidence conflicts. D01–D18 stay unsigned, so this brief names no grace, rate, or approval sequence.

**Server authorization and audit.** SEC-CORE-001 and ADR-013 require the check on the server for the action. SEC-CORE-011 and AUDIT-CORE-001 require an audit of pay, attendance results, corrections, and permission changes. The department and designation masters already audit name and status changes. That pattern is evidence for those masters. It is not a general audit design for roles that do not exist yet.

## 3. Why the Boolean flags stop here

`can_manage_employees` and `can_manage_organization_masters` are implemented module grants. They remain in place for the employee foundation and the two masters.

They are not the authorization model for the next module.

- SEC-CORE-004 requires grant by role. A new column such as `can_manage_branches` or `can_manage_attendance` would add another module flag and still leave the role list unsigned.
- The decision gates already prohibit copying `can_manage_employees` onto payroll, attendance correction, or period close.
- The Stage 5D direction says the master flag does not grant those other writes, and that there is no company-administrator role.
- AUD-03 shows the legacy alternative: a level string plus a button list. That alternative failed to check the action on the server.
- Branch maintenance, employee assignment, attendance, leave, and payroll each need their own signed rule. A flag copied from the employee screen would hide that missing rule.

Proposal, not a decision: leave both flags as they are until a signed role model replaces them. Do not add a third flag while this brief is unsigned.

## 4. Open decisions that need the product owner

None of these are selected here.

| Question | Why it is blocked | What this brief does not choose |
| --- | --- | --- |
| Does a platform principal exist, and which cross-company actions does it have? | ADR-002 and AUD-03 | No platform role and no subscription model |
| Which roles exist, and which actions does each role grant? | SEC-CORE-004 | No role names and no permission matrix |
| Is every role company-scoped, or may a signed role act across companies? | AUD-03, still unsigned | No global-administrator default |
| For which surfaces, if any, is a branch, department, or employee scope applied? | SEC-CORE-003 gate | No scope rule |
| How is a role given to a user inside a company? | Not specified | No assignment screen and no assignment table |
| Which settings are product defaults, and which are company policy values? | CFG-CORE-001 gives the order. D01–D18 give the values | No policy value |
| Who may act on a particular request? | ADR-009 gives the shape. BD-D17 gives the steps | No boss sequence and no status-code map |

Signing this file, if the owner later signs it, would confirm the distinctions in section 2 and the stop on new Boolean flags. It would still not sign SEC-CORE-003, SEC-CORE-004, BD-D16, BD-D17, or the migration decisions.

## 5. Risks if implementation starts before those decisions

- A new Boolean becomes permanent product behavior and splits administration into unrelated grants.
- Payroll, attendance correction, or period close inherits the employee flag and fails the gate that forbids that copy.
- A platform screen copied from legacy `supadmin` can read every company. AUD-03 says that cross-company access exists only if an owner signs it.
- A narrower scope built before the rule is signed can omit employees who should be visible, or include employees outside the intended limit.
- An approval button that only checks a role can approve a request the actor does not hold, which is the gap AUD-02 already found on parts of the legacy path.
- Organization nodes get reused as departments or as boss columns, which the domain decision forbids.
- Preview ESS routes are treated as the employee session. SEC-CORE-007 is then unmet.
- Permission changes go unaudited, which misses SEC-CORE-011.

## 6. Acceptance criteria for a later implementation task

These criteria apply only after the product owner has signed the open items that the task needs. They are not authorization to build now.

- The task names the signed role or roles it uses. It does not invent role names in code.
- The task adds no new Boolean membership flag.
- `can_manage_employees` and `can_manage_organization_masters` keep their current meaning until a signed replacement migrates them.
- Every new state change checks the action on the server and matches the record to the active company.
- A hidden control is not the check.
- No query returns another company's rows by changing an id.
- No scope narrower than the company is enforced unless SEC-CORE-003's model is signed for that task.
- Branch writes, employee assignments, attendance, leave, payroll, and period close stay out of the task unless their own gates are signed.
- Organization nodes are not stored as departments, branches, or boss columns.
- An ESS session, when ESS is in scope, is one employee in one company.
- Permission changes introduced by the task are audited.
- Automated tests cover an allowed action, a missing grant, and a cross-company id. Those tests are not signed-in browser UAT.
- Step 20R is updated only with the owner's decision, date, and conditions. A draft in this file is not that update.

## 7. Supporting paths

- `next-hrms\docs\UNIVERSAL_HRMS_PROJECT_CONTROL_CENTER.md`
- `next-hrms\docs\UNIVERSAL_HRMS_INCREMENTAL_DELIVERY_AND_DECISION_GATES.md` — SEC-CORE-003 and SEC-CORE-004 rows
- `next-hrms\docs\UNIVERSAL_HRMS_REQUIREMENTS.md` — section 11, SEC-CORE-001 through SEC-CORE-011
- `next-hrms\docs\UNIVERSAL_HRMS_ARCHITECTURE_ASSESSMENT.md` — sections 6, 7, 16, and 21, including ADR-009 and ADR-013
- `next-hrms\docs\UNIVERSAL_HRMS_STEP20_FINAL_DOMAIN_DECISIONS.md`
- `next-hrms\docs\UNIVERSAL_HRMS_ORGANIZATION_MASTERS_PERMISSION_DECISION.md`
- `next-hrms\docs\UNIVERSAL_HRMS_AUD03_APPADMIN_SERVER_AUTHORIZATION_AUDIT.md`
- `next-hrms\docs\UNIVERSAL_HRMS_AUD02_LEAVE_AND_ATTENDANCE_APPROVAL_AUDIT.md`
- `next-hrms\docs\UNIVERSAL_HRMS_AUD07_APPROVAL_CALLERS_AND_PAYROLL_READS.md`
- `C:\Users\ASUS\Downloads\HRMS_Redevelopment_Master_Workplan_and_AI_Collaboration_Guide.docx`
- `C:\Users\ASUS\Downloads\HRMS_Overall_Executive_Summary.docx`
- `C:\Users\ASUS\Downloads\Universal_HRMS_Full_Proposal.docx`

The attendance findings document was not required for this access-model question and was not re-read.

## 8. Statement

No application code, schema, test, configuration, database, or Git state was changed. No file other than this brief was written. Milestone 3 was not started.

UNSIGNED DECISION BRIEF

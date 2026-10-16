# Universal HRMS — Authorization Requirements

Date: 2026-10-16

Status: **PROPOSED REQUIREMENTS NOTE.** This file consolidates AUD-08 through AUD-21. It does not approve a policy, sign a decision, fill Step 20R, or authorize implementation. Legacy source behavior is evidence. It is not approval to copy that behavior into the new product. Source inspection is not runtime verification. No company is migration-approved.

The access-model brief remains unsigned. SEC-CORE-003, SEC-CORE-004, BD-D16, BD-D17, D1–D6, and the other open gates in the active control center remain unsigned. SCOPE-01 still defers provident fund, gratuity, recruitment, and PMS content.

## 1. What this note is allowed to say

The Step 21 catalog counts 416 controller records. It does not name actions. A source count of `function action` in the 24 ESS controller files is 295 declarations. Four deferred controllers account for 85. The remaining 210 declarations were not each classified. The identity work is named actions in AUD-08 through AUD-21 and the read-identity matrix. It is not a claim that every legacy controller has the weaknesses below.

Where a list already binds the session employee, or a delete already tests both the employee and status `New`, this note records that as evidence to keep. It does not treat the list as proof that the matching detail action is safe.

## 2. Requirements

### A. Authentication and session context

An authenticated user, the active company, the employee identity, and the permission for the current operation are four different facts.

- Authentication establishes who signed in.
- The active company establishes which tenant the session is acting in. Membership in a company is not a grant to read or change every row in that company.
- Employee identity is the employee row for this session, when the user is acting as an employee. `employees.id` and `employees.emp_id_no` are different columns. A posted value is not either of them until the server binds it.
- Authorization is the allow or deny for this actor, this operation, and this resource. A session value, a hidden form field, or a requested id is not that decision.

The employee foundation already requires login and one active company. `can_manage_employees` and `can_manage_organization_masters` are module grants on top of that membership. They are not a role list. This note does not add a third Boolean.

### B. Resource-level authorization

Every read or write that accepts a resource id must decide, on the server, whether this actor may perform this operation on this resource. The operations are list, detail, update, delete, approval, rejection, download, and export.

A list filter is not that decision. If the list shows only the session employee's rows, the detail, update, delete, approval, download, and export endpoints still evaluate the same relationship for the requested id.

### C. Role and relationship authorization

The relationship is evaluated at the action, not only on the screen that linked to it. The relationships the audits actually found are:

- The employee owns the row.
- The employee is named as supervisor or grand-supervisor on that row.
- The employee is named as first or second approver, or as a rater, on that row.
- A company-admin level opened an employee by id. The audits did not find a reliable `emp_unit` test on those id loads.
- A module string plus any user level opened an accounts list. A hardcoded username selected a menu and was not repeated on the list action.

Legacy names such as `clientAdmin`, `Trustee`, `External`, `customer`, and `atlascopcoacc` are evidence of old branches. They are not the universal role list. SEC-CORE-004 leaves that list unsigned. This note does not adopt those names.

### D. Company and organizational scope

Authorization uses the active company. A query that does not name the company is not evidence that the table is company-scoped. Some legacy actions use the session connection. Some use a requested database name. Some call a hardcoded connection. Those are not the same rule.

Company-wide access inside the active company is the boundary already implemented for the employee foundation. A limit to a branch, department, or a set of employees is SEC-CORE-003. That model is unsigned. This note requires the product to be able to enforce a signed narrower scope later. It does not authorize building that scope now, and it does not infer that every legacy table has a reliable company column.

### E. Workflow state and operation authorization

The server checks the current state, the actor's authority for this transition, and that the new state is one the signed workflow allows. A hidden button, a status posted by the client, and a link that is shown only for status `New` do not do that.

This note does not invent the allowed order for probation, appraisal, leave, overtime, bills, or payment. Where the source shows labels and does not code an order, the order stays unresolved. ADR-009 already says approval is an ordered configurable workflow whose actor is a user. BD-D16 and BD-D17 stay unsigned, so this note names no attendance step list and no correction field list.

### F. Sensitive data and file access

Salary, bank, tax, password, identity documents, uploads, the file cabinet, and exports require an explicit allow for that data and that operation. A screen that loads `SELECT *` does not thereby have permission to render every column.

Password values are not stored or compared as the text the user typed. Uploaded files are not kept under the original client name without an ownership rule. A cabinet whose model has no employee column is not given an owner by this note. Sharing stays unresolved until an owner states the rule.

### G. Auditability

A security-relevant change records the actor, the resource, the operation, the time, and the outcome, including the prior state and the new state where the operation changes stored data. The legacy actions read in these audits did not insert that record. The department and designation masters already audit name and status changes. That is evidence for those masters. It is not evidence that ESS writes are audited, and it is not a signed audit design for roles that do not exist yet. SEC-CORE-011 and AUDIT-CORE-001 already require an audit of pay, attendance results, corrections, and permission changes. This note does not mark those rows signed beyond what the gates file already says.

### H. Reusable enforcement

The target behavior, in terms that are not a framework choice:

- One policy point evaluates actor, operation, resource, company, and current state. Screens, device clients, APIs, downloads, exports, and background jobs call that point. They do not each invent a filter.
- Reusable rules cover "this employee owns the row," "this employee is the named supervisor, approver, or rater on this row," and "this operation is allowed for this resource type."
- Company context is an input to every rule. A narrower organizational scope is an input only after SEC-CORE-003 is signed for that surface.
- Workflow rules reject a transition the signed definition does not allow, including a client-supplied status.
- Tests can show allow and deny for two employees in one company, a named approver who is not the owner, a company administrator, and a requested id that matches none of those relationships.

This note does not choose middleware, tables, or screens. SEC-CORE-001 and ADR-013 already require the check on the server for the action.

## 3. Evidence-linked matrix

| ID | Requirement | Legacy evidence | Observed pattern | Proposed target | Status | Later verification |
| --- | --- | --- | --- | --- | --- | --- |
| AUTH-REQ-001 | The server treats authentication, active company, employee identity, and the operation grant as separate facts. A posted id is not any of those facts | ESS-ID-01 keeps session reads. `getEmpInfo` loads `employees.id` and does not force the session employee (DASH-04). `actionCloudpunch` lists by `emp_id_no` (AUD-21) | Session values and posted ids are used as if they were the grant | Bind the actor and the company on the server. Compare the resource to that actor for the operation | **Proposed.** Fits the implemented login and active company. Does not sign a role list | Two sessions. A request that substitutes the other employee's id is denied for a self-only operation |
| AUTH-REQ-002 | A requested resource id is authorized for that operation even when the list was filtered | P1. Cited actions include AUD-09 `findModel`; AUD-11 dashboard detail; AUD-12 profile, payslip, and salary card; AUD-16 print; AUD-17 delete and both approval saves; AUD-18 detail views; AUD-19 `findModel` and review; AUD-20 and the multirater rater views; AUD-21 Atlas detail views and the cloud-punch remark | The list names the session employee, a boss, a grand-boss, or a rater. The later action loads the id only | The detail, update, delete, approval, download, and export actions repeat the relationship | **Proposed** | For each cited action family, the owner's id is allowed and another id in the same company is denied |
| AUTH-REQ-003 | A write stores the server's employee binding, not the posted employee number, unless the operation is an authorized action on another employee | P2. AUD-15 lunch; AUD-16 mobile-bill insert; AUD-11 overtime create and edit; AUD-09 document create; AUD-19 probation insert, which also updates `employees` for the posted id. AUD-21 tax 108 post stores the posted number. The tax 108 list itself uses the session number (PAY-04) | The form prefills the session employee. The action stores the posted value | Self-service writes ignore a conflicting posted employee. An action on another employee is allowed only when the relationship check passes | **Proposed** | Submit a self-service form with a different employee number. The stored row remains the session employee |
| AUTH-REQ-004 | The action checks current state and the actor. A status sent by the client, or a button hidden in the view, is not the check | P3. Deletes in AUD-15, AUD-16, and AUD-17 do not test the status the view hides. AUD-18 `actionBilldel` selects the session employee and deletes only when status is `New`. AUD-13 approval updates status before any approver compare. AUD-19 review writes the posted status. AUD-21 `actionFlagrise` writes a flag by id | UI status and server status disagree, except the Atlas own-bill delete | Keep the Atlas delete tests as the shape of a check. Do not copy a delete or approval that skips them. Do not adopt an uncoded status order | **Proposed** for the check. **Unresolved** for the allowed orders. See the decision register | A row that is not in the allowed prior state cannot be deleted or approved. The Atlas `New` test is the regression example for that shape |
| AUTH-REQ-005 | Company context comes from the active company. A requested database name or a hardcoded connection is not a company grant | P4. AUD-11 `searchorgo` comments out `emp_unit`, and overtime writes use `vtlw`. AUD-13 uses a requested `db`. AUD-17 uses `sesdata('dbname')` beside `user.db`. AUD-21 `searchorgo` confirms `id` and `emp_id_no` are different filters. Atlas approval views also call `Yii::$app->atlas`. Alias-to-company for source keys 24, 20, and 10 is DOC-07 | Scope is whichever connection the action picked | Evaluate the active company on every rule. Refuse a client-selected connection. Do not map an alias to company ids 24, 20, or 10 until that map is evidence | **Proposed** for the active company. **Unresolved** for alias maps and for tables with no company column | A user whose active company is A cannot read company B by passing B's id or database name |
| AUTH-REQ-006 | The relationship for this row is checked at the action. Legacy level names are not copied as universal roles | Supervisor and grand-boss lists in AUD-18 and AUD-21. Rater lists in AUD-20 and the matrix. `clientAdmin` service book and admin payroll reads load the requested id without `emp_unit` (ADM-03, SVC-01). Username `atlascopcoacc` selects a menu and is not the check on `actionAtlasbillacc` (AUD-21). First-approver history does filter `internal_first_approver` (BILL-05) | Some queues name the relationship. The matching detail, and the accounts list, do not | Authorize the named relationship per resource. Company administration and platform administration stay the unsigned distinctions in the access brief | **Proposed** for the per-resource check. **Requires owner decision** for the role list: SEC-CORE-004 | A boss sees the queued row and does not see a row where they are not boss, grand-boss, or approver. A module grant alone does not open the accounts list |
| AUTH-REQ-007 | Sensitive columns and files are allowed explicitly. Password text is not the stored secret | P5. AUD-08 and AUD-21 compare and store password text for the session user. AUD-12 prints `username.password` and pay fields for the requested id. AUD-09 stores an upload under the original name. DOC-08: the document list is session-scoped; `hrm_filecab` has no employee column | Pay and password fields render from the loaded row. Cabinet ownership is not in the model | Minimize the columns. Store passwords so the text cannot be read back. Authorize each download. Leave cabinet sharing unsigned | **Proposed** for passwords, pay fields, and named uploads. **Unresolved** for the cabinet | A company-admin read of another employee does not include the password column. A file id outside the actor's grant is denied |
| AUTH-REQ-008 | Security-relevant writes record actor, resource, operation, time, and outcome | P7. The cited ESS writes do not insert an audit row. Master name and status changes already do. SEC-CORE-011 and AUDIT-CORE-001 name pay, attendance results, corrections, and permission changes | Absence in the actions that were read. Another listener was not searched | Record the change after the authorization check. Do not record a denied or unauthorized write as a successful business change | **Proposed.** Not a claim that legacy ESS writes are audited | An allowed change has one audit row. A denied id does not change the business row |
| AUTH-REQ-009 | One server policy serves web, device, API, download, export, and job callers | SEC-CORE-001 and ADR-013. The audits found the same id load in a page render, a print, and a posted update | Each action repeats, or omits, its own filter | Callers share the policy. This note does not name the mechanism | **Proposed.** Blocked as implementation by the unsigned role and scope decisions | The same allow and deny results for a page and for the matching download |

AUTH-REQ-002 is the highest-priority pattern because it recurs from AUD-09 through AUD-21. AUTH-REQ-003 and AUTH-REQ-004 are next, because they are the write and the state check on that same id. AUTH-REQ-007 is next where the loaded row includes pay or password text.

Actions that do not follow the weak pattern, and are cited so the note does not over-claim:

- Session-bound self reads: ESS-ID-01, PAY-01, PAY-04, HLV-01, LCH-01, MBL-01, BILL-01, ATL-01, PRB-01, A360-01, and the DefaultController profile, help, and others actions.
- Relationship-bound lists: A360-02, ATL-05, BILL-05, and the multirater responder list.
- Delete that tests the employee and status `New`: ATL-02.

## 4. Decision register

These rows are not approved. This note adds no decision id. It points at the id that already exists.

| Matter | Existing id | What the audits add | Status |
| --- | --- | --- | --- |
| Which roles exist, and which actions each role grants | SEC-CORE-004 | Legacy levels and one hardcoded username are not that list | Unsigned. Blocks a role matrix and any copy of the employee Boolean onto payroll or attendance |
| A scope narrower than the active company | SEC-CORE-003 | `searchorgo` comments out `emp_unit`. Service book and admin id reads do not test it | Unsigned. Company membership remains the implemented boundary. Building a branch, department, or employee scope stays blocked |
| Attendance correction fields and approval steps | BD-D16, BD-D17 | ESS overtime and leave actions show posted ids and status writes. They do not define the step list | Unsigned. ADR-009 stays the ordered configurable workflow. This note names no steps |
| Employee identity, codes, and dispositions | D1–D6 | `employees.id` and `emp_id_no` are different columns. Cloud-punch `employee_id` is compared to `emp_id_no` | Unsigned. No migration approval |
| Provident fund, gratuity, recruitment, and PMS content | SCOPE-01 | The salary card reads PF tables for the session employee. `actionFlagrise` writes a PMS table by id. Those controllers were not opened | Deferred |
| Alias and connection names versus company ids 24, 20, and 10 | DOC-07, and P4 | Session connection, requested `db`, `vtlw`, and `Yii::$app->atlas` were observed. No map was established | Unresolved. Do not invent the map |
| Who may see the organizational directory | DASH-05, inside SEC-CORE-003 | `searchorgo` can filter `id` or `emp_id_no` and does not force the company | Unresolved purpose. The missing company predicate is a proposed check, not a signed audience |
| Shared file cabinet | DOC-08 | The cabinet model has no employee column. The document list beside it forces the session employee | Unresolved sharing rule |
| Probation and appraisal status order | PRB-04, A360-04, MRA-03 | Labels exist. The actions store a posted status or do not code an order | Unresolved. Do not adopt the labels as policy |
| Which prior states allow delete, approval, rejection, or payment | P3, with ATL-02 as the one tested delete | Several deletes skip the status the view uses. Atlas own-bill delete requires `New` | Unresolved as a catalog of transitions. The requirement to check on the server is proposed |
| What `mra_organogram.responder_id` contains | MRA-04 | The admin form writes `emp_id_no`. The import writes `mra_customers.id`. The ESS customer screen binds `employees.id` | Unresolved. A stored row was not read |
| Platform principal and product-default administration | Access brief, unsigned. ADR-002 | AUD-03 refused to copy `supadmin` as the default cross-company role | Unsigned. This note does not create a platform role |

## 5. Conflicts and insufficient evidence

- The same product area both filters and does not filter. Atlas own-bill delete checks the employee and status `New`. Atlas approve and accounts views load by id. Both are source. Neither cancels the other.
- `user.db` and `sesdata('dbname')` appear in the same controllers. Equality was not shown.
- `responder_id` is written two ways and read a third way. The column type in a live database was not read.
- The file cabinet cannot be given an owner from the model that was read. Calling it shared would be a decision. Calling it a defect would invent a column.
- `indexnreedemo.php` was not re-read. Attendance and leave were not re-labeled action by action. The 210 declarations were not each reviewed.
- No finding in this series was executed. A cited id load is source text, not a demonstrated access.

## 6. Next milestone

Review this note in the access-model sitting described by the decision-readiness review. That sitting decides whether the next product work waits for a signed role model. It can leave SEC-CORE-003 and SEC-CORE-004 unsigned.

That review precedes implementation. The employee foundation, including login, active company, and the two existing module grants, is already in place and is not a reason to build authorization middleware, a permission screen, or ESS workflow copies. Department and designation masters stay under the Stage 5D working direction. That direction is not an owner signature.

Implementation of attendance, payroll, roles, narrower scope, and migration stays unauthorized.

AUTHORIZATION REQUIREMENTS NOTE COMPLETE FOR THIS BATCH

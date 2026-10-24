# Universal HRMS — Authorization Owner Review

Date: 2026-10-16

Status: **OWNER REVIEW AID.** This paper prepares a sitting. It does not approve a policy, sign a decision, fill Step 20R, or authorize implementation. Recommendations are proposals. The access-model brief stays unsigned. SEC-CORE-003, SEC-CORE-004, BD-D16, BD-D17, and D1–D6 stay unsigned. SCOPE-01 stays deferred. No company is migration-approved.

Sources: `UNIVERSAL_HRMS_AUTHORIZATION_REQUIREMENTS.md`, the unsigned access-model brief, and the decision-readiness review. Legacy audits were not repeated. Source inspection is not runtime verification.

## 1. One policy

The nine requirements are one rule, stated in four groups. A filtered list, a hidden button, a posted employee number, and a posted status are not permission.

**Group 1. Who is acting, and in which company.** AUTH-REQ-001 and the company half of AUTH-REQ-005.

The product already knows the signed-in user and the active company. Those two facts are not permission to read or change every row in that company. The employee’s internal id and the employee number are different values. A value sent by the screen becomes one of those facts only when the server binds it.

**Group 2. May this person do this to this record.** AUTH-REQ-002, AUTH-REQ-003, the relationship half of AUTH-REQ-006, and AUTH-REQ-009.

Every list, detail, update, delete, approval, rejection, download, and export checks that question on the server. A list that already shows only one person’s rows does not answer it for the next screen. A self-service save stores the signed-in employee. Saving another employee requires a relationship that has already been allowed for that record: the owner, the named supervisor or grand-supervisor, or the named approver or rater. The same answer applies to a page, a device, an API, a download, an export, and a job. Old level names and one hardcoded username are not that relationship.

**Group 3. Is this change still allowed.** AUTH-REQ-004.

The server reads the record’s current state and the actor. It refuses a transition the signed workflow does not allow. The screen’s button and the status posted with the form are not that check. This group does not choose the legal order of statuses.

**Group 4. What may be shown, and what is remembered.** AUTH-REQ-007 and AUTH-REQ-008.

Salary, bank, tax, and password values are shown only when that action is allowed to show them. A password is not stored or compared as the text the person typed. Each file download is allowed on its own. After an allowed change, the product records who, which record, what changed, when, and the outcome. A refused attempt is not stored as a successful business change. This group does not decide whether the file cabinet is shared.

These four groups are the proposed security baseline. They do not add a third yes-or-no flag beside `can_manage_employees` and `can_manage_organization_masters`. They do not name middleware, tables, or screens.

## 2. What the owner still has to decide

The baseline says “check the relationship and the state.” It does not say which roles exist, who may see a whole department, which status may follow which, or who owns the file cabinet.

| Id | Question for the owner | Proposal for this sitting | Trade-off of deciding now | Trade-off of leaving it |
| --- | --- | --- | --- | --- |
| SEC-CORE-004 | Which roles exist, and which actions each role grants? The access brief also asks whether a role may act across companies, and how a role is given to a user | Leave unsigned. Do not add a third flag. Do not copy `can_manage_employees` onto payroll or attendance | A short role list would unblock a permission screen. The brief’s own risk is that a rushed list becomes permanent, or that a legacy super-admin is copied across companies | The two existing flags stay as they are. No role matrix is built. Workflow steps that need a role keep waiting |
| SEC-CORE-003 | For which screens, if any, is a person limited to a branch, department, or a set of employees? | Leave unsigned, as the decision-readiness review already allows. The active company remains the boundary | A scope chosen too early can hide people who should be visible, or show people outside the intended limit | Company-wide access inside the active company continues. That is the boundary already implemented for the employee foundation |
| DOC-07 | Which legacy database nickname belongs to which company? | Do not map nicknames to company ids 24, 20, or 10 in this sitting | A guessed map would send one company’s rows to another | Connection migration and any screen that still switches databases stay blocked. The active-company rule does not need this map |
| DOC-08 | Is the file cabinet shared, or does each file belong to one employee? | Do not choose. The model that was read has no employee column. The document list beside it is limited to the signed-in employee | Calling it shared, or calling the missing column a defect, would invent a rule | Cabinet behavior stays unspecified. Named uploads still follow Group 4 when that feature is built |
| PRB-04 | What order of probation and appraisal statuses is allowed? | Do not adopt the labels seen in the legacy screens as that order | A copied sequence would become policy without a signed workflow | Probation and appraisal stay unbuilt. Group 3 still applies when a signed order exists |
| Access brief | Does a platform principal exist? Which settings are product defaults rather than company policy? Who may act on one request? | Leave the brief unsigned. Confirm only that its distinctions are not yet a decision. Do not create a platform role | Signing the brief would still not sign SEC-CORE-003, SEC-CORE-004, BD-D16, or BD-D17. The brief says that itself | Administration above a single company, and product-default ownership, stay closed |

## 3. What blocks an authorization build, and what can wait

**Blocks a build of the authorization product.** SEC-CORE-004, together with the access-brief questions about a platform principal, a cross-company role, and how a role is assigned. AUTH-REQ-009 says the shared check is not to be built while those are unsigned. The decision-readiness review says the same: do not choose middleware or table names in this sitting.

**Does not block the baseline, and can wait.** SEC-CORE-003. The policy can accept a narrower scope later. Building that scope now is blocked. Describing the active company as today’s boundary is not.

**Does not block the authorization baseline at all.** DOC-07 blocks only a legacy connection map. DOC-08 blocks only the cabinet. PRB-04 blocks only probation and appraisal order.

**Blocked dependencies. This paper does not decide them.**

- BD-D16 and BD-D17: attendance correction fields and approval steps. ADR-009 remains an ordered configurable workflow. A step that names a role waits on SEC-CORE-004. Decide them together, after this access sitting, and only by writing Step 20R.
- D1–D6: employee identity, codes, and dispositions. They block a named company load. They do not block the authorization baseline. No company is migration-approved.

## 4. Proposed sitting outcome

If the owner is not ready to name roles, the proposed outcome of the sitting is:

- Accept Groups 1 through 4 as the security baseline to use when authorization is later built.
- Leave SEC-CORE-003 and SEC-CORE-004 unsigned.
- Leave the access brief unsigned.
- Leave DOC-07, DOC-08, and PRB-04 unresolved.
- Leave BD-D16, BD-D17, and D1–D6 for their own sittings.

That outcome is a proposal. It becomes a decision only when the owner writes it into Step 20R with an approver, a date, and the conditions. This file is not that writing.

Implementation of attendance, payroll, roles, narrower scope, and migration stays unauthorized. The parent `docs` folder was not updated.

AUTHORIZATION OWNER REVIEW PREPARED

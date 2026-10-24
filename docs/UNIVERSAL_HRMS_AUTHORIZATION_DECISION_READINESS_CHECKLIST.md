# Universal HRMS — Authorization Decision Readiness Checklist

Date: 2026-10-16

Status: **OWNER INTENT RECORDED FOR CONFIRMATION.** The five marks below record the owner's message of 2026-10-16 as intent for preparing the decision record. They do not fill Step 20R, sign SEC-CORE-003 or SEC-CORE-004, sign the access brief, or authorize implementation. BD-D16, BD-D17, and D1–D6 stay unsigned. SCOPE-01 stays deferred. No company is migration-approved. The implementation gates were not changed. The preparation record is `UNIVERSAL_HRMS_AUTHORIZATION_DECISION_RECORD_DELTA.md`.

Sources: `UNIVERSAL_HRMS_AUTHORIZATION_DECISION_AGENDA.md`, `UNIVERSAL_HRMS_AUTHORIZATION_OWNER_REVIEW.md`, the unsigned access-model brief, `UNIVERSAL_HRMS_DECISION_READINESS_REVIEW.md`, and `UNIVERSAL_HRMS_ORGANIZATION_MASTERS_PERMISSION_DECISION.md`. Legacy audits were not repeated.

A mark in this file is a draft for the sitting. It becomes a decision only when the owner writes it into Step 20R with an approver, a date, and the conditions.

## 1. Owner marks

The agenda's recommended option is repeated so the mark can be read against it. A mark here is owner intent. Formal confirmation is still Step 20R.

### Question 1. Initial role model, and who may assign a role

SEC-CORE-004. Agenda proposal: Option B. A role is a named set of actions on a company membership. The first set is the grants already in force plus the right to assign roles inside that company. The two existing flags stay until a signed replacement maps them.

Owner decision (ACCEPT, MODIFY, or DEFER): ACCEPT

Rationale: Owner intent for record preparation. Use company-scoped roles with specific permissions. Add no further independent permission boolean. Keep the two existing flags until an approved and tested mapping replaces them. Role names and the permission list remain unwritten. This mark does not sign SEC-CORE-004.

Date: 2026-10-16

### Question 2. Platform administrator

Agenda proposal: Option A for this architecture. The first model has no platform administrator and no role that acts in every company.

Owner decision (ACCEPT, MODIFY, or DEFER): MODIFY

Rationale: Owner intent rejects an unrestricted administrator who can act in every company. Agenda Option A left company setup and membership recovery outside the model. The recorded direction instead requires those responsibilities, together with exceptional access, to be written later as controlled, accountable, and audited procedures. Agenda Option C is not the recorded direction. The procedures are not written in this mark. The access brief's Platform Super Admin surface is not adopted.

Date: 2026-10-16

### Question 3. One company or several

Agenda proposal: Option B. One login may hold more than one membership. The session has one active company. Grants travel with that membership.

Owner decision (ACCEPT, MODIFY, or DEFER): ACCEPT

Rationale: Owner intent for record preparation. One user may hold more than one company membership. Only one company context is active at a time. Every request validates that membership and the authorization for the request. The maximum number of companies, and the recovery procedure, remain unwritten.

Date: 2026-10-16

### Question 4. Who administers users and permissions inside a company

Agenda proposal: Option B. That administration is its own company-scoped responsibility, separate from employee writes and from department and designation writes. Read the conflict in section 4 before marking this question.

Owner decision (ACCEPT, MODIFY, or DEFER): ACCEPT

Rationale: Owner intent for record preparation. Establish a company-scoped responsibility for user membership and role administration. Keep it separate from employee management, organization-master management, and payroll and attendance approval. The Stage 5D conflict is reconciled in section 4 and in the decision-record delta. Stage 5D's implementation rule is not withdrawn. This mark does not sign SEC-CORE-004.

Date: 2026-10-16

### Question 5. Company boundary, and a later narrower limit

SEC-CORE-003. Agenda proposal: Option B. The enforced boundary is the active company. The check reserves a later branch, department, or employee limit and does not enforce it. Read the conflict in section 4 before marking this question.

Owner decision (ACCEPT, MODIFY, or DEFER): ACCEPT

Rationale: Owner intent for record preparation. Company isolation is the foundation that is enforced. Future narrower organizational scopes may be designed and are not enforced until a separate approval. This mark does not sign SEC-CORE-003 and does not choose a branch, department, or employee limit for any screen.

Date: 2026-10-16

## 2. Unresolved operational questions

The owner named company setup, membership recovery, and exceptional access as controlled responsibilities that must be accountable and audited. The steps, the actor, and the audit fields are not written. Password recovery was not named in that intent. LIFE-CORE-006, an inactive or suspended employee state, is a different subject from company subscription or company suspension.

| Question | What is now recorded | What is still unwritten |
| --- | --- | --- |
| Initial company setup | Controlled, accountable, and audited. It is not an unrestricted all-company role | Who may create a company, what they may change, and the audit fields. Required before a create-company screen |
| First company membership | Memberships and one active company are the accepted shape. Whether the first membership uses the membership-recovery path is unwritten | Who attaches the first user to a new company. Required before an onboarding screen |
| Membership recovery | Named by the owner as a controlled responsibility, with accountability and audit | Who may restore a membership, and what evidence is required. Required before a recovery screen |
| Exceptional access | Named by the owner as a controlled responsibility, with accountability and audit. It is not a standing role in every company | The qualifying event, the duration, the records it may touch, and the later review. Required before any such action |
| Account recovery | Not named in the 2026-10-16 intent. AUTH-REQ-007 still proposes that a password is not stored as typed text. That proposal is unsigned | Self-service recovery, an administrator setting another person's password, and clearing a lock. Required before a password-reset screen |
| Role assignment and revocation | Question 4 accepts a company-scoped assigner, separate from employee and master management | Role names, the permission list, revocation, the open session, and the last remaining assigner. SEC-CORE-011 names an audit of permission changes and does not design it. Required before an assignment screen |
| Subscription and suspension authority | Not named in the 2026-10-16 intent. The access brief still chooses no subscription model | Who may subscribe or suspend a company. Required before that screen. It does not define the company-level check |

## 3. What design needs, and what a screen needs

"Design" here means describing the authorization check. It does not mean building middleware, tables, or screens. The gates still prohibit that build. This checklist does not move that prohibition.

| Topic | Before the check can be described | Only before a particular screen or module |
| --- | --- | --- |
| Question 1, role shape | Owner intent: company-scoped roles, no new permission boolean, and the two flags kept until an approved tested mapping. Formal confirmation is still Step 20R | The role names, the permission list, the mapping, and its tests. A permission screen |
| Question 2, platform administrator | Owner intent: no unrestricted all-company administrator. The company check does not depend on one | The written procedures for company setup, membership recovery, and exceptional access. Subscription and suspension. Product-default ownership |
| Question 3, membership | Owner intent: many memberships, one active company, validated on every request | A maximum number of companies, if the owner later wants one. The first-membership procedure |
| Question 4, company administration | Owner intent: a company-scoped membership and role responsibility, reconciled with Stage 5D in section 4. Implementation still follows Stage 5D until Step 20R confirms it | The person's title, the assignment screen, revocation, and the permission-change audit |
| Question 5, narrower limit | Owner intent: company isolation is the enforced foundation. A narrower limit may be designed and is not enforced | A separate approval naming the screens and the limit. Branch writes. Employee assignment. SEC-CORE-003 stays unsigned |
| Attendance and payroll behavior | A role-typed workflow step waits on a signed role list and on BD-D17. The check's company boundary does not need BD-D16 or BD-D17 | Correction fields, approval steps, leave pay, overtime, payroll calculation, and period close |
| Employee load | D1–D6 do not define the check | A named company load |

DOC-07, DOC-08, and PRB-04 remain outside this checklist. They block a legacy connection map, the file cabinet, and probation order.

## 4. Conflicts

**Conflict A. Company administrator. Reconciled as two layers. Stage 5D is not withdrawn.** The Stage 5D file still says there is no company-administrator role and that SEC-CORE-004 remains unimplemented. That sentence remains the implementation rule. Question 4 records owner intent for a later responsibility limited to user membership and role administration inside one company. The added note in the Stage 5D file points at the decision-record delta. It does not delete the original rule, and it does not sign SEC-CORE-004.

**Conflict B. SEC-CORE-004. Direction recorded. Gate unchanged.** Question 1 records the company-scoped role direction. The gates file still says the role list is unsigned. Step 20R has no access-model entry. The owner-review proposal to leave the gate unsigned remains true until that register is written.

**Conflict C. Narrower scope. Direction recorded. Gate unchanged.** Question 5 records company isolation as the enforced foundation and leaves narrower enforcement for a separate approval. The gates file still says the scope model is unsigned. SEC-CORE-003 is not signed.

**Conflict D. Platform surface. Unrestricted role rejected. Procedures unwritten.** Question 2 rejects an unrestricted all-company administrator and does not adopt the access brief's Platform Super Admin surface. Company setup, membership recovery, and exceptional access are required as later controlled procedures. The brief stays unsigned.

**Stored memberships.** Question 3 records the owner's acceptance of many memberships and one active company. The rows already stored were evidence. They were not, by themselves, the decision. Formal confirmation is still Step 20R.

## 5. Smallest next owner-review step

The sitting paper is `UNIVERSAL_HRMS_AUTHORIZATION_PRE_SITTING_DECISION_PACKAGE.md`. Items 1 through 7 remain proposed owner intent, marked ACCEPT, and are not a signature. Item 8 is a consistency proposal: every request is checked; an open session is invalidated immediately only when invalidation is reliable; otherwise the revoked permission is rejected on the next request. The last role assigner is removed only after a replacement grant is stored. A company that already has no role assigner uses item 8A, not the first-membership setup. Stage 5D's original rule is unchanged. The directory proposal is not what the current gates or screens enforce. Section 11 of that paper is a Step 20R entry draft. It was not copied into the register. Approver, role, and date are `PENDING OWNER`. No decision is ready for formal signing without further owner input. Section 14 of that paper is the consolidation proposal. AUTH-DRAFT-01, AUTH-DRAFT-02, AUTH-DRAFT-03, and AUTH-DRAFT-07 are one architecture confirmation, not four gates. They are not signed. Do not open another authorization worksheet. The genuine remaining owner decisions are the table in that section.

Do not name roles in that writing unless the permission list is ready. Do not open BD-D16, BD-D17, D1–D6, DOC-07, DOC-08, or PRB-04. Do not implement authorization from this intent.

The parent `docs` folder was not updated. Section 5 of the active gates file was not changed.

AUTHORIZATION DECISION READINESS CHECKLIST UPDATED

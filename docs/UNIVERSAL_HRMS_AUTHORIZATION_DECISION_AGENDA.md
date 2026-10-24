# Universal HRMS — Authorization Decision Agenda

Date: 2026-10-16

Status: **OWNER SITTING AGENDA.** This paper lists the questions that need an owner answer before an authorization architecture can be drawn. It does not approve a policy, sign a decision, fill Step 20R, or authorize implementation. Every recommended option is a proposal. SEC-CORE-003, SEC-CORE-004, and the access brief stay unsigned. BD-D16, BD-D17, and D1–D6 stay unsigned. SCOPE-01 stays deferred. No company is migration-approved.

Sources: `UNIVERSAL_HRMS_AUTHORIZATION_OWNER_REVIEW.md`, `UNIVERSAL_HRMS_AUTHORIZATION_REQUIREMENTS.md`, the unsigned access-model brief, and the decision-readiness review. The four security groups in the owner-review paper stay the proposed baseline. This agenda does not reopen them. Legacy audits were not repeated.

The owner may leave any item unsigned. An unsigned item remains a blocker for the part named under that item. Writing an answer into this file does not sign it. A decision exists only when the owner writes it into Step 20R with an approver, a date, and the conditions.

## Sitting boundary

These five questions are the sitting. They are the items the owner-review paper says block a build of authorization.

Left for later sittings, and not asked here: DOC-07, DOC-08, PRB-04, BD-D16, BD-D17, D1–D6, which settings are product defaults, and who may change a product default. Payroll, attendance correction, period close, branch writes, and employee assignment stay out of the first role list.

## 1. Initial role model, and who may assign a role

SEC-CORE-004 says permissions are granted by role. The role list is unsigned. The access brief already states that a role names actions. A role does not name a company, and it does not name an approval step.

**Business question.** Which actions may a person perform, and who inside a company may give that person those actions?

**Options**

| Option | What the owner would accept | Advantage | Risk and operational consequence |
| --- | --- | --- | --- |
| A | Keep adding a yes-or-no flag for each new area, beside `can_manage_employees` and `can_manage_organization_masters` | Matches the two grants already in the product. No new role names | Each new area becomes another permanent flag. The gates already forbid copying the employee flag onto payroll, attendance correction, or period close. Administration splits into unrelated switches |
| B | A role is a named set of actions, held on a company membership. The first set covers only the grants already in force and the right to assign roles inside that company. The two flags stay until a signed replacement maps them | Satisfies the requirement that a grant is a role. One later list can hold attendance or payroll when those gates are signed. Assignment is visible as its own action | The owner must name the first roles in Step 20R. Until that writing, no permission screen is built. A role-typed approval step still waits on BD-D17 |
| C | Copy the legacy level names `supadmin`, `admin`, and `admin_user`, with a button list per screen | Familiar to people who use the old admin application | AUD-03 classified those names as legacy only. Much of that path hides a button or checks a level string, and it does not satisfy the server check already in force |

**Proposal: Option B.** Do not add a third flag. Do not put payroll, attendance, correction, or period close on the first list. The person who may assign a role is the company-administration responsibility in item 4, and only inside the active company.

**Stays unresolved.** The signed role names and the exact action list. Middleware and table names. BD-D17, because a workflow step is not a role. The two existing flags, until a signed replacement maps them.

## 2. Platform administrator

The access brief names a platform surface for tenant operations such as subscription and suspension, once those operations are specified. It also says that surface must not become a default right to read every company's employees, pay, or punches. AUD-03 refuses to copy `supadmin` as the default cross-company role. Whether the principal exists is still open.

**Business question.** Is there a person who acts above a single company, and what may that person do?

**Options**

| Option | What the owner would accept | Advantage | Risk and operational consequence |
| --- | --- | --- | --- |
| A | The first authorization model has no platform administrator and no role that acts in every company | Company checks can be drawn now. No one receives a default view of every company's employees, pay, or punches | Creating a company, and creating the first membership in it, stays outside this model until a later sitting names that procedure |
| B | A platform role exists only for tenant actions the owner later names. It has no default access to employees, pay, or punches | Reserves a place for subscription or suspension without opening company data | Those tenant actions are not specified. Naming the role before the action list invites it to be used as a way into company records |
| C | A platform administrator may enter any company and act with that company's grants | One operator can support every company | This is the legacy `supadmin` pattern. AUD-03 says that cross-company access exists only if an owner signs it. ADR-002 keeps operational facts inside one company |

**Proposal: Option A for this architecture.** Company authorization does not need a platform role. If a later sitting needs tenant actions, the shape to bring back is Option B, with the action list written first.

**Stays unresolved.** Who creates the first company and its first membership. Subscription and suspension. Product-default ownership, which is a different surface in the access brief.

## 3. One company or several

The application already stores memberships and requires one active company in the session. Holding a membership is not a grant to change employees or masters. The two flags sit on the membership. A company switch is not a role. The access brief preserves that implementation and leaves the platform-wide rule unsigned. AUD-03 left multi-company holding unsigned for the legacy admin user.

**Business question.** May one login belong to more than one company, and what does that person carry when the active company changes?

**Options**

| Option | What the owner would accept | Advantage | Risk and operational consequence |
| --- | --- | --- | --- |
| A | One login belongs to one company. Another company means another login | Simple to explain. No question of grants leaking across companies | A person who works for two companies keeps two accounts. The membership rows already stored would have to be constrained later |
| B | One login may hold more than one membership. The session has one active company. Roles and the two existing flags belong to that membership, so they apply only in the active company | Matches the membership and active-company behavior already built. SEC-CORE-002 still rejects another company's rows | Someone must add each membership. Item 2 leaves the first membership of a new company unspecified. A person can be powerful in one company and limited in another, which operators must check per company |
| C | One login has one set of roles, and those roles apply in every company the person can enter | One assignment serves every company | A grant in one company becomes a grant in the others. The access brief leaves a cross-company role unsigned and sets no global-administrator default |

**Proposal: Option B.** Confirm the stored shape: many memberships are allowed, one is active, and grants travel with the membership.

**Stays unresolved.** The maximum number of companies, if the owner later wants a limit. The procedure that creates the first membership. D1–D6, which still block a named company load.

## 4. Who administers users and permissions inside a company

The access brief separates company administration from employee and manager work. Stage 5D records that there is no company-administrator role, and that the master-write flag does not grant employee writes. The employee flag does not grant master writes. Both statements are working direction, not a signature.

**Business question.** Inside one company, who may create users, attach them to that company, and give or remove their permissions?

**Options**

| Option | What the owner would accept | Advantage | Risk and operational consequence |
| --- | --- | --- | --- |
| A | Anyone who may manage employees may also manage users and permissions | Uses a grant the product already has | Employee maintenance and access control become the same switch. The gates forbid extending that flag into unrelated areas |
| B | User and permission administration is its own responsibility, held inside the active company. It is separate from employee writes and from department and designation writes. It can assign only actions the owner has already signed | Matches the brief's split between company administration and HR operations. A permission change can be audited as its own kind of change when that screen is later built | The owner must name who receives this responsibility in Step 20R. Until then, no administration screen is built. The two current flags continue to gate only the screens they already gate |
| C | Only a platform administrator manages users in every company | Central control | Depends on item 2. It gives a person above the company the power to write that company's access |

**Proposal: Option B.** This responsibility is company-scoped. It does not include payroll, attendance, correction, period close, or a scope narrower than the company. It is the same responsibility that item 1 proposes as the role assigner.

**Stays unresolved.** The person's title and the exact action list, which are Step 20R text. SEC-CORE-011 names an audit of permission changes; this agenda does not design that audit. Branch writes and assignment columns stay unauthorized.

## 5. Company boundary, and a later narrower limit

SEC-CORE-003 says the system shall be able to limit a user to a branch, a department, or a set of employees. The gates permit company membership as already implemented, and they block a narrower limit until that model is signed. The decision-readiness review allows this item to be reviewed and left deferred. Employees are not assigned to departments or designations. Assignment columns are undefined. An organization node is an approval scope, and it is not a department.

**Business question.** What is the smallest boundary the product enforces now, and how can a branch, department, or employee limit be added later without replacing the model?

**Options**

| Option | What the owner would accept | Advantage | Risk and operational consequence |
| --- | --- | --- | --- |
| A | The company is the only boundary the product will ever enforce | Nothing further to design | A later branch or department limit would be a new model. The written requirement says the system shall be able to apply a narrower limit |
| B | The enforced boundary is the active company. The same check keeps a place for one later limit: branch, department, or a set of employees. That limit is an extra condition on the records a role may touch. It is not a new yes-or-no flag, and it is not enforced until SEC-CORE-003 is signed for a named screen | Architecture can treat "which company" and "which records inside it" as separate facts. Today's employee and master screens stay company-wide | A department limit cannot be turned on while employees are unassigned. Choosing the limit too early can hide people who should be visible |
| C | Enforce a branch, department, or employee limit on the current screens now | Narrow access sooner | The scope model is unsigned. Employees have no department assignment. The owner-review paper says this item can wait |

**Proposal: Option B.** Active-company membership remains the boundary that is enforced. The reserved condition is how a later signed limit attaches, without a third flag and without a redesign of roles or membership.

**Stays unresolved.** Which screens, if any, receive a narrower limit. Branch writes. Assignment columns. DOC-07, which is only the legacy database-name map.

## Proposed outcome of the sitting

If the owner accepts the five proposals, the architecture to draw later is:

- A person has one or more company memberships, and the session uses one active company.
- A role is a set of actions on that membership. The first actions are the employee grant, the master grant, and company administration of users and roles.
- There is no platform administrator and no cross-company role in that drawing.
- The check asks whether this membership's role allows this action on a record in the active company, with a reserved place for a narrower limit that is not enforced yet.

That outcome is a proposal. It becomes a decision only in Step 20R. Accepting it still does not authorize a permission screen, a schema change, attendance, payroll, a narrower scope, or a company load.

## Blockers after this agenda

| If the owner accepts the proposal | Still blocked |
| --- | --- |
| The authorization check can be described | Building it. Step 20R is still blank |
| SEC-CORE-004 can be answered at the level of "roles name actions, assigned inside the company" | The named role list and any permission screen |
| The platform question can be answered as "none in the first model" | Tenant creation, subscription, and suspension |
| Membership can stay many-per-login, one active | The procedure for the first membership |
| Company administration can be a separate responsibility | The title, the action list, and the audit of permission changes |
| The boundary can stay the active company, with a reserved narrower condition | Enforcing any branch, department, or employee limit |

BD-D16, BD-D17, and D1–D6 were not decided. DOC-07, DOC-08, and PRB-04 were not asked. The parent `docs` folder was not updated.

AUTHORIZATION DECISION AGENDA PREPARED

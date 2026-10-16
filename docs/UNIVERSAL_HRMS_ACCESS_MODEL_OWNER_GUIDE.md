# Universal HRMS — Access Model Owner Guide

Date: 2026-10-16

Status: **EXPLANATION FOR THE OWNER.** This guide restates the unsigned access-model brief in business language. It does not replace that brief. It does not choose a policy, sign SEC-CORE-003 or SEC-CORE-004, fill Step 20R, or authorize implementation. No company is migration-approved.

The active checkpoint is `next-hrms\docs\UNIVERSAL_HRMS_PROJECT_CONTROL_CENTER.md`. The next product implementation milestone is not authorized.

## 1. What this decision is about

The product is one HRMS used by many companies. Each company's records stay inside that company. What still needs an owner decision is who may do what, and how far that permission reaches.

Five responsibilities are already described. They are parts of one product. They are not five separate systems, and they are not five on/off switches.

Two switches already exist in the application. They cover employee changes and department and designation changes. They are not a full access model. The requirement SEC-CORE-004 says permissions are granted by role. The list of roles has not been signed.

## 2. Five ways of separating people from permissions

These five ideas stay separate. None of them replaces the others.

| Idea | Plain meaning | What it does not mean |
| --- | --- | --- |
| Identity | The person signed in | A right to change records, or a right to approve a request |
| Company membership | Which company that person may enter. The application keeps one active company for the session | A right to administer that company. Membership alone does not grant employee changes or master changes |
| Permission to act | Whether this person may perform a named action, such as changing an employee name | Which company they are in, and which single request they may approve |
| Organizational scope | An optional narrower limit: a branch, a department, or a set of employees inside the company | The company boundary itself. A narrower limit is not authorized yet |
| Approval authority | Whether this person may take the current step of one particular request | A general right to open attendance, leave, or payroll |

A manager is not, by that title alone, allowed to approve every request. Being in a company is not, by itself, permission to administer it.

## 3. The five product surfaces

The names below are the brief's names. "HR and manager operations" is the surface that would run HR and manager workflows. Nothing in this section is a build instruction.

### Platform Super Admin

**Purpose.** Run the platform itself: companies as tenants, and platform operations such as subscription and suspension, once those operations are specified.

**What it would need to administer.** Actions that sit above one company. It must not become a default right to read every company's employees, pay, or punches.

**Undecided.** Whether such a person exists, which platform actions they have, and how they are appointed.

**Dependencies.** The company remains the tenant for operational records. The legacy admin level `supadmin` is evidence from AUD-03, not a role to copy.

**Risk of building it now.** A screen copied from that legacy level could see every company. AUD-03 says cross-company access exists only if an owner signs it.

### HRMS product administration

**Purpose.** Keep product-wide defaults, shared capability switches, and extensions. An extension stays off until a company turns it on.

**What it would need to administer.** Defaults that are not one company's data.

**Undecided.** Which settings are product defaults, and who may change a default.

**Dependencies.** The accepted order is product, then company, then a lower level only when that policy allows it. The actual policy values, including grace and rates, are unsigned. A company switch is not a role.

**Risk of building it now.** A product screen would choose values the owner has not chosen, or would treat one company's rule as the product default.

### Organization / company administration

**Purpose.** Configure one company's structure, users, access, and company settings.

**What it can already do.** An authenticated person in the active company may read that company's departments and designations. Creating or changing those masters requires `can_manage_organization_masters`. That flag defaults to off. It is a working direction for the masters already built, not an owner signature, and it is not a company-administrator role.

**Undecided.** Which role may administer the company. Branch changes. How employees are assigned to structure. Any limit narrower than the whole company.

**Dependencies.** Another company's data is refused. Employees are not assigned to departments or designations. Organization nodes are approval scopes, not departments and not boss columns.

**Risk of building more of it now.** Another on/off switch would not name the company administrator. Branch changes and assignments have no signed rule.

### HR and manager operations

**Purpose.** Run employee administration, and later the attendance, leave, payroll, and related workflows a company enables.

**What it can already do.** Employee and episode changes require `can_manage_employees`. That flag does not grant department or designation changes, payroll, attendance, or period close.

**Undecided.** The role list. Who may approve a request (BD-D17). Which correction values may change a calculated day (BD-D16). Attendance and payroll behavior.

**Dependencies.** Approval, payroll, attendance correction, and period close each need their own check. Those checks are requirements. They are not built, and the business rules they depend on are unsigned.

**Risk of building it now.** An attendance or payroll button could reuse the employee flag. The gates forbid that copy. An approval button that only checks a broad role can approve a request the person does not hold. AUD-02 found that kind of gap on parts of the old path.

### Employee Self-Service

**Purpose.** Let an employee see and submit only that employee's own enabled information and requests.

**What it would need.** A session bound to one employee in one company.

**Undecided.** Which request types a company turns on.

**Dependencies.** Preview screens in the application are fixtures. They are not this surface.

**Risk of building it now.** Treating those fixtures as the real employee session would miss the one-employee, one-company rule.

## 4. The decisions, in plain language

Where the source does not list a set of business choices, this guide does not invent one. It states the question, the boundary already written, and the brief's labeled proposal where one exists.

### SEC-CORE-004 — Permissions are granted by role

**The question.** Which roles exist, and which actions does each role allow?

**Why it matters.** Without a role list, every new area of the product needs its own special switch. The product then cannot answer a simple question: what may this person do?

**Options already documented.** The gates allow the existing employee-module flag to keep gating employee changes. They forbid copying that flag onto payroll, attendance correction, or period close. The brief does not name roles and does not provide a permission matrix. Its labeled proposal, which is not a decision, is to leave the two existing flags as they are until a signed role model replaces them, and to add no third flag while the brief is unsigned.

**Trade-offs.** The source supports these limits. A new flag would leave SEC-CORE-004 unmet, because the role list would still be blank. Keeping the two flags avoids disturbing the employee and master screens that already work. Inference, not a signed design: replacing them later will need a careful handover so people who can change employees today do not lose that ability by accident. The handover itself is not designed and is not approved.

**Dependencies.** This decision comes before any new permission. A workflow step that says "a role" also waits on this list. That step list is BD-D17 and is a separate unsigned decision.

**Current status.** Unsigned. The role list is not implemented. The next implementation milestone is not authorized.

### SEC-CORE-003 — A narrower limit inside the company

**The question.** For which of the five surfaces, if any, may a person be limited to a branch, a department, or a set of employees?

**Why it matters.** Some companies will want a manager to see only their own group. Applying that limit too early, or in the wrong place, hides people who should be visible or shows people who should not be.

**Options already documented.** The gates allow company membership as it works today: the active company is the boundary. They forbid enforcing a narrower limit until the scope model is signed. The brief does not choose a scope rule.

**Trade-offs.** Staying at company level matches what the application does now. The source states the risk of a narrower rule built before it is signed: the wrong employees can be omitted or included. The documents do not say which surface should use a narrower limit. That absence is an open question, not a decision for "none" or "all."

**Dependencies.** Company membership already exists and does not need this decision. Any screen that filters below the company does.

**Current status.** The requirement exists. The scope model is unsigned. The narrower limit is blocked.

### Whether a platform-level person exists

**The question.** Does someone operate the platform across companies, and which actions may they take?

**Why it matters.** Tenant setup and suspension are not the same job as HR administration inside one company. Joining those jobs gives one person every company's records.

**Options already documented.** None are listed. The brief refuses a default copied from legacy `supadmin` and does not define a subscription model. AUD-03 left unsigned whether a role is global or limited to a company.

**Trade-offs.** Inference from AUD-03 and ADR-002: delaying this decision blocks platform administration and does not block the employee screens inside one company. That inference is not a decision that platform administration is unnecessary.

**Dependencies.** ADR-002: operational facts belong to one company. A cross-company action needs its own signed rule.

**Current status.** Unsigned. No platform role is defined.

### How a person receives a role inside a company

**The question.** Once roles exist, how does a person get one inside a company?

**Why it matters.** Membership answers which company they may enter. It does not answer what they may do there.

**Options already documented.** None. The brief says no assignment screen and no assignment table are chosen.

**Trade-offs.** Not stated as a business choice in the source. Open question.

**Dependencies.** SEC-CORE-004, because there is nothing to assign until roles exist.

**Current status.** Unspecified. Not signed.

### Product defaults versus company policy

**The question.** Which settings are product defaults, and which are each company's own values?

**Why it matters.** One company's grace period or approval habit must not become the rule for every company. The product also needs a place for a genuine shared default.

**Options already documented.** The order of lookup is accepted: product, then company, then a lower level only when that policy allows it. The values are not chosen. D01–D18 stay unsigned. No grace, rate, or approval sequence is named.

**Trade-offs.** Choosing values now would pick numbers the evidence does not agree on. Leaving them unsigned blocks product administration and does not block the employee foundation.

**Dependencies.** A signed answer to who may change a product default. That person is not named.

**Current status.** The order is an accepted principle. The values and the editor of those values are unsigned.

### Who may act on one request

**The question.** Who may approve or reject a particular request, and in what order?

**Why it matters.** Opening a module is not the same as holding the current approval step. The old system used different sequences for different companies. Those sequences are evidence, not the future rule.

**Options already documented.** The shape is accepted: an ordered workflow, and the actor is a user. A step may be a role, a reporting-line manager, an organizational approver, or a named fallback. A fixed boss-then-grand-boss sequence is the prohibited copy. The step list is BD-D17. The worksheet lists alternatives and selects none. This guide does not repeat that selection and does not make one.

**Trade-offs.** Deciding the step list before the role list is possible only for steps that are not role-based. A role-typed step cannot be built while SEC-CORE-004 is unsigned. That dependency is in the access brief.

**Dependencies.** SEC-CORE-006 requires the server to check this user for this step. BD-D16 is the separate question of which correction values may change a calculated day.

**Current status.** BD-D17 is unsigned. The access brief does not sign it.

### The brief's own proposal about the two existing flags

**The question.** Until a role model is signed, what happens to `can_manage_employees` and `can_manage_organization_masters`?

**Why it matters.** Those flags already protect the screens that exist. Removing them, or copying them onto new modules, changes either today's behavior or tomorrow's model.

**Options already documented.** One labeled proposal, not a decision: leave both flags as they are until a signed role model replaces them, and do not add a third flag while the brief is unsigned. The brief also says that signing the brief later would confirm the separations in its section 2 and the stop on new flags. That signature would still not sign SEC-CORE-003, SEC-CORE-004, BD-D16, BD-D17, or the migration decisions.

**Trade-offs.** The proposal keeps today's employee and master behavior. It does not, by itself, create the role model SEC-CORE-004 requires. This guide does not adopt the proposal as the owner's choice.

**Current status.** Proposal only. Unsigned.

## 5. Why more on/off flags do not finish the model

The two flags answer two narrow questions: who may change employees, and who may change departments and designations. SEC-CORE-004 asks a wider question: which role grants which actions.

Adding `can_manage_attendance`, or a similar flag, would record another narrow question. The role list would still be blank. The gates already forbid using the employee flag as the payroll, correction, or period-close grant. The Stage 5D note says the master flag does not grant those writes, and that there is no company-administrator role.

The old admin application used level names plus buttons that appear or disappear. AUD-03 found that this did not check the action on the server. That finding is a reason not to copy the old pattern. It is not a signed design for the replacement.

No replacement design is approved. The acceptance criteria in the brief apply only after the owner signs the decisions a future task needs. They are not permission to build now.

## 6. What is known, and what is not

### Established in the application

Session login, one active company, logout, the employee directory and profile, display-name edit and history, episode protections, and the approved rejoin rule. Department and designation reads for a signed-in user in the active company. Writes to those masters only with `can_manage_organization_masters`, which defaults to off. Employee writes only with `can_manage_employees`. The two flags do not grant each other's work. A later focused run reported 50 tests and 500 assertions. That is automated evidence, not signed-in browser UAT.

The employee foundation is not blocked by the unsigned access decisions. New surfaces are.

### Legacy findings, not future rules

AUD-03: admin levels `supadmin`, `admin`, and `admin_user`; some admin controllers with no level check; module checks commented out; button lists that are not server checks. Whether a legacy admin user may hold more than one company was left unsigned. The new application already stores memberships and requires one active company. The brief preserves that implementation and does not decide a platform-wide rule for how many companies one person may join.

AUD-02 and AUD-07: approval behavior differs by company database. That shows why approval must be configuration. It does not choose the future step list.

### Accepted principles

One product, many companies. The company is the boundary for operational records. The server checks the action. A hidden button is not the check. Approval is an ordered workflow whose actor is a user. Organization nodes are approval scopes, not departments or boss columns. Extensions default to off. Product defaults sit above company values, and company values sit above a lower level only when the policy allows it.

### Waiting for the owner

SEC-CORE-003, SEC-CORE-004, the platform-principal question, how a role is given, which values are product defaults, BD-D16, and BD-D17. D1–D6 block a legacy employee load. They do not decide access. The next implementation milestone is not authorized.

### Questions the documents do not answer

- The names of the roles, and the actions each role grants.
- Which surfaces use a branch, department, or employee limit.
- Whether any signed role may act across companies.
- Who appoints a platform-level person, if one exists.
- How many companies one person may belong to, as a platform rule. The application can store more than one membership. That is not a signed rule.
- Which request types Employee Self-Service will offer.
- The approval step list and the correction field list. Those are BD-D17 and BD-D16, and they are not answered here.

## 7. Owner decision agenda

These are review questions. This guide does not pick an answer.

| Question for the owner | Why it is needed | Related ID | What waits on it | Blocks the next implementation milestone? |
| --- | --- | --- | --- | --- |
| Do we wait for a signed role model before any new area of the product gets a permission? | The brief's proposal is to add no third on/off flag. Only the owner can adopt or reject that proposal | SEC-CORE-004 | Any new administration or workflow screen | Yes. The next product milestone is not authorized, and a new permission is the thing that milestone would need |
| Which roles will exist, and what may each role do? | SEC-CORE-004 cannot be met by another flag | SEC-CORE-004 | Role-based approval steps, payroll access, correction access, period close | Yes, for any work that needs a new grant. Not for the employee screens already built |
| Should anyone be limited below the whole company, and on which surfaces? | A manager's view and a company administrator's view are different jobs | SEC-CORE-003 | Screens that filter by branch, department, or employee | Not for today's company-wide screens. Yes, before a narrower limit is built |
| Will anyone operate the platform across companies, and what may they do? | Tenant setup must not become a right to read every company's pay and attendance | Platform principal, left open by AUD-03 and ADR-002 | Platform administration | Not for in-company employee work. Yes, before a cross-company screen |
| Which settings are shared product defaults, and which belong to each company? | One company must not become the template | CFG-CORE-001. Values are D01–D18 | Product administration and company policy screens | Not for the employee foundation. Yes, before those settings are built |
| Who may approve one request, in what order? | Opening a module is not approval authority | BD-D17, after the access distinctions | The approval workflow | Not this access guide's signature. Attendance workflow stays blocked until BD-D17 is signed on its own worksheet |

Engineering can choose tables, screens, and checks after these answers exist. Those technical choices are not on this agenda.

## 8. Sources

- `next-hrms\docs\UNIVERSAL_HRMS_DOCUMENTATION_AUTHORITY.md`
- `next-hrms\docs\UNIVERSAL_HRMS_PROJECT_CONTROL_CENTER.md`
- `next-hrms\docs\UNIVERSAL_HRMS_PRODUCT_SURFACE_AND_ACCESS_MODEL_DECISION.md`
- `next-hrms\docs\UNIVERSAL_HRMS_INCREMENTAL_DELIVERY_AND_DECISION_GATES.md` — SEC-CORE-003 and SEC-CORE-004 rows, and section 5

The original brief was not edited.

## 9. Statement

No application code, test, schema, configuration, database, or Git state was changed. No decision was signed. Implementation was not authorized.

EXPLANATION FOR THE OWNER

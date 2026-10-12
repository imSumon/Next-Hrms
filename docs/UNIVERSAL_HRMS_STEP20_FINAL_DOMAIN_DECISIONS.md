# Universal HRMS — Step 20 Final Domain Decisions

Date: 2026-10-10

Status: decision freeze. No database, table, foreign key, migration, or application code was created.

These two decisions close the location-fact parent and the organizational-approver parent. They do not reopen employee-code reuse, weekday storage, or any earlier approved encoding. D01–D18 and BD-D15 are unchanged.

## 1. Location employment fact decision

**Approved target decision.** An employment fact with `fact_kind = location` stores `fact_reference_id` as `location_nodes.id`. It does not store `branches.id`.

Branch remains a separate assignment. The branch for a location node is the branch already required on `location_nodes.branch_id`. The location fact does not replace that branch.

This is a target HRMS domain decision. It does not mean legacy `job_location`, `unit`, `floor`, `line`, `section`, or `sub_section` are rows in `location_nodes`.

No foreign key is created in this step.

## 2. Organization reference decision

**Approved target decision.** `organization_reference_id` references `organization_nodes.id`.

An organization node is an organizational approval scope. The target model supports hierarchical organization nodes. The node stays distinct from:

- a named employee approver (`fallback_user_id`)
- an approver role (`resolver_role_key`)
- reporting depth (`reporting_depth`)
- an employee boss or other reporting relationship (`reporting_links`)

`employees.boss` and `employees.g_father` are employee reporting relationships. They are not `organization_reference_id`.

No `organization_nodes` table and no foreign key are created in this step.

## 3. Rationale

Location: company and branch assignment is already separate from the location fact. Legacy employees carry `company_branch_id` and, beside it, unit, floor, line, section, and job-location text. The target architecture already separates `branches` from `location_nodes`, and a location node belongs to a branch. The location fact therefore cites the location node. The branch is reached through that node.

Organization: the approval step kinds already split role, reporting depth, named user, and organizational approver. Only the last of those uses `organization_reference_id`. The reference is an approval scope in the organization hierarchy, not a person and not a reporting edge.

## 4. Legacy evidence

The evidence is the read-only result in `docs/UNIVERSAL_HRMS_STEP20_FINAL_DOMAIN_BLOCKER_RESULT.md`. It is not re-investigated here.

Location evidence that this decision uses:

- `employees.company_branch_id` points at `company_branch`
- `employees.unit`, `floor`, `line`, `section`, `sub_section`, and `job_location` are separate values, not a node table
- payroll and attendance use the branch and those texts together

Organization evidence that this decision uses:

- leave and attendance approval copy `employees.boss` and `employees.g_father` onto `boss_id` and `gboss_id`
- those values are employee ids
- no approved document named a parent for `organization_reference_id` before this decision

## 5. Legacy meaning and target meaning

| Topic | Legacy meaning | Target meaning |
|---|---|---|
| Branch | `employees.company_branch_id` → `company_branch.id` | `branches`. Separate from the location fact |
| Extra place text | `unit`, `floor`, `line`, `section`, `sub_section`, `job_location` strings, plus `job_location` and `employee_work_location` masters | Not automatically `location_nodes` |
| Location employment fact | Not a single legacy foreign key | `fact_kind = location`, `fact_reference_id` → `location_nodes.id` |
| Boss and grand boss | Employee ids copied onto approval `boss_id` and `gboss_id` | `reporting_links`. Not `organization_reference_id` |
| Organizational approver | Not a traced legacy lookup | `organization_reference_id` → `organization_nodes.id` |

## 6. Impact on physical schema

When a later step creates objects, and not before:

- `employment_assignments.fact_reference_id` for `fact_kind = location` references `location_nodes` in the same company. It does not reference `branches`.
- `location_nodes.branch_id` remains the link from that node to `branches`.
- `organization_nodes` is the parent entity for `organization_reference_id`. Nodes are hierarchical and company-scoped.
- `organization_reference_id` is populated only when `resolver_kind` is `organization_approver`.
- Role, reporting depth, named user, and reporting links stay on their existing columns.

The hierarchy is a same-company parent relationship among `organization_nodes`. This decision does not name a path string or a closure table.

## 7. Impact on migration mapping

`employees.company_branch_id` stays a branch source for `branches`. It is not the location-fact reference.

The mapping workbook row that places Atlas `company_branch_id` into an employment assignment with fact kind location is not the rule for this decision. That load is not authorized by this freeze. Unit, floor, line, section, and `job_location` are not inserted into `location_nodes` by this decision.

`employees.boss` and `employees.g_father` stay reporting evidence. They are not loaded into `organization_reference_id`. No legacy column is declared to be an organization node. Organization-node rows are not invented from departments, branches, or the organogram.

## 8. Remaining assumptions

- A location fact is used only when the company has a `location_nodes` row to cite. A company that has only a branch does not get a location node invented from `company_branch_id`.
- The organization-node parent column is the hierarchy this decision requires. Its name is fixed when the table is created. No second business parent is open.
- Employee-code reuse is closed. Codes are not reused inside a company. `UNIQUE (company_id, employee_code)`. See `docs/UNIVERSAL_HRMS_STEP20_FINAL_EMPLOYEE_CODE_DECISION.md`.

## Step 20 readiness

READY FOR STEP 20 PHYSICAL IMPLEMENTATION

The location fact, `organization_reference_id`, and employee-code reuse are decided. Nothing in this decision set is created here.

## Safety statement

Production modified: NO

Legacy databases modified: NO

Legacy data imported: NO

Nree modified: NO

new_proedge_hrms created: NO

HRMS database created: NO

HRMS tables created: NO

organization_nodes created: NO

HRMS migrations executed: NO

HRMS application created: NO

Foreign key created: NO

D01–D18 changed: NO

BD-D15 changed: NO

Migration approval granted: NO

# Step 20 Final Domain Blocker Result

Date: 2026-10-10

Status: read-only investigation. The two domain questions below were later closed by explicit business decision in `docs/UNIVERSAL_HRMS_STEP20_FINAL_DOMAIN_DECISIONS.md`. No database, table, foreign key, migration, or application code was created or changed. The evidence sections are unchanged.

## 1. Scope

This investigation covered only two concepts:

- The location employment fact (`fact_kind = location` and `fact_reference_id`)
- `organization_reference_id` on an organizational-approver step

Employee-code reuse was not investigated. Approved Step 20 encodings were not reopened. D01–D18 and BD-D15 were not changed.

## 2. Location Employment Fact

### Evidence

The legacy employee master stores more than one location-like value at the same time.

| Legacy store | What the source shows |
|---|---|
| `employees.company_branch_id` | Integer. Payroll and attendance filter employees by it and join `company_branch.id` to print `company_branch.name` |
| `employees.unit`, `floor`, `line`, `section`, `sub_section` | Strings on the employee. Payroll report code lists distinct `unit`, `floor`, and `line` inside a selected `company_branch_id`. They are not rows in a location-node table |
| `employees.job_location` | String. Attendance exports compare it to text such as `Corporate`, `Back Office`, and `RTE`, in the same query as `company_branch_id` |
| `job_location` | Separate master: `id`, `company_id`, `location_name`. The employee column is declared as a string, not as that id |
| `employee_work_location` | Separate master: `id`, `company_id`, `code_name`, `type`. It is not the employee branch column |
| `employees.emp_unit` | Legacy key already classified as not a target location and not a tenant |
| `employees.bank_branch_id` | Bank branch, not a work site |

`backend/modules/pims/models/Employees.php` declares these employee columns. `backend/modules/organizationsettings/models/CompanyBranch.php` is the `company_branch` master. `PayrollreportsController.php` builds unit, floor, and line lists from employee strings after a branch is chosen. `backend/modules/attendance/controllers/DefaultController.php` filters on both `job_location` text and `company_branch_id`.

The closed-month audits and the mapping workbook add company scope, not a single meaning:

- Atlas: `company_branch_id` is set on 117 of 151 employees. The workbook maps that id to `branches` and also to an employment assignment with fact kind location.
- Proedge August and VTL: a branch was not established as the required site. Factory location fields exist.
- The migration architecture says factory depth (unit, floor, line) is not a universal tree. Extra depth may later be optional `location_nodes`.

The target schema says `location_nodes.branch_id` belongs to `branches`, and `fact_reference_id` may cite a branch or a location node depending on kind. It does not say which of those two is the parent when the kind is `location`.

### Legacy behavior

A person’s site filter in payroll and attendance is `company_branch_id`. Unit, floor, line, section, and job-location text are additional employee attributes used beside that branch. They are not a stored child of `company_branch` in a node table.

### Target interpretation

The evidence does not prove that `fact_reference_id` for `fact_kind = location` is `branches.id`. It does not prove that it is `location_nodes.id`. Legacy has no `location_nodes` table. The Atlas mapping points one source field, `company_branch_id`, at a location assignment, and it still leaves unit, floor, and line as optional depth. That is one source field’s mapping. It is not a proof that every location fact cites `branches`.

### Confidence

High that the evidence is insufficient for one parent. Low that either `branches` or `location_nodes` is the universal parent.

### Decision required

BUSINESS DECISION REQUIRED / DOMAIN CLARIFICATION REQUIRED.

No foreign key is created.

## 3. Organization Reference ID

### Evidence

APP-CORE-002 names four step kinds: role, reporting-line manager, organizational approver, and named fallback. The target schema gives each a different column. `organization_reference_id` is set only for `organization_approver`. No requirement, architecture, logical model, physical model, schema, or migration document names the table that id cites.

Legacy approval copies employee ids, not an organization id.

| Legacy store | Use |
|---|---|
| `employees.boss` | Employee id. Leave `HrmleaveempappController.php` copies it to `boss_id` |
| `employees.g_father` | Employee id. The same controller copies it to `gboss_id` |
| Attendance approval | `AttendanceapprovalfileController.php` copies `boss` and `g_father` onto `boss_id` and `gboss_id` |
| `organogram_department` | `department_id`, `designation_id`, and `parent`. No approval controller found here reads it to choose an approver |

The mapping workbook sends `boss` and `g_father` to `reporting_links`. It says those columns are not approval steps. The requirements say the reporting line supplies approvers and must not hard-code boss and grand boss. That reporting path is already `reporting_depth` on a reporting-manager step. It is a different column from `organization_reference_id`.

### Legacy behavior

The approval rows that were traced obtain the first and second approver from the employee’s boss and grand-boss employee ids. They do not look up a department, branch, unit, or organogram node as the approver reference.

### Target interpretation

`organization_reference_id` has no established parent. It is not the company, not the reporting chain, and not the named user. Those already have their own fields. Calling it a department, a branch, or a generic organization node would be a new choice.

### Confidence

High that no parent is defined. The legacy boss path does not fill this column.

### Decision required

BUSINESS DECISION REQUIRED / DOMAIN CLARIFICATION REQUIRED.

No organization table is added. No foreign key is created.

## 4. Cross-Document Consistency

The schema sentence that `fact_reference_id` can cite a branch or a location node matches the ambiguity. It does not resolve it. The mapping workbook’s Atlas row, which places `company_branch_id` on a location assignment, does not agree with a rule that the location fact always cites `location_nodes`, and it does not by itself establish that rule for every company. Proedge and VTL were not given that same branch fact.

`organization_reference_id` is consistent across the requirements, the architecture, and the schema: the step kind exists, and the parent does not. Legacy boss columns are consistently assigned to reporting links, not to this id. Nothing in that set names a parent.

No document was rewritten.

## 5. Final Step 20 Readiness

The two domain blockers in this investigation are resolved by explicit business decision, recorded in `docs/UNIVERSAL_HRMS_STEP20_FINAL_DOMAIN_DECISIONS.md`:

- `fact_kind = location` cites `location_nodes.id`, not `branches.id`
- `organization_reference_id` cites `organization_nodes.id`

The employee-code reuse blocker is closed by explicit business decision in `docs/UNIVERSAL_HRMS_STEP20_FINAL_EMPLOYEE_CODE_DECISION.md`. Codes are not reused inside a company. The target constraint is `UNIQUE (company_id, employee_code)`.

Those objects were not created here.

READY FOR STEP 20 PHYSICAL IMPLEMENTATION

## 6. Exact Decisions Needed

None. The location parent, the organization-reference parent, and employee-code reuse are decided.

## Safety statement

Production modified: NO

Legacy databases modified: NO

Legacy data imported: NO

Nree modified: NO

new_proedge_hrms created: NO

HRMS database created: NO

HRMS tables created: NO

HRMS migrations executed: NO

HRMS application created: NO

Foreign key created: NO

D01–D18 changed: NO

BD-D15 changed: NO

Migration approval granted: NO

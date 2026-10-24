# Universal HRMS Target Database Schema Specification

Status: design specification only. No migrations, tables, SQL, database changes, models, APIs, UI, or data movement are included.

Steps 9, 10, and 11 stay as approved. This document does not amend them.

Proedge storage is a migration source. Viyellatex storage is a migration source. Neither is the target schema.

```
UNIVERSAL HRMS CORE
  + COMPANY-CONFIGURABLE POLICIES
  + OPTIONAL CLIENT EXTENSIONS
```

---

## 1. Purpose

This is the first target schema specification for the Universal HRMS. It names the physical tables, their fields, ownership, keys, uniqueness, and history rules so a later implementation can create them without inventing a second model.

The schema stores the separations already approved: punch event, `work_date`, day-result version, correction, calculated overtime, payable overtime, paid overtime, and finalized payroll result. Policy values for D01–D18 are not stored.

## 2. Design principles

| Principle | Schema consequence |
|---|---|
| One product | One logical schema for every company |
| Company is the business boundary | Operational rows carry `company_id` or a parent that does, and both when the child can be read alone |
| Surrogate primary keys | Every table’s primary key is a ULID. UUID is acceptable only where creation-time order is unnecessary |
| Business codes are attributes | Employee code, punch identity, head code, and period label are not primary keys |
| Effective dating | Intervals use `effective_from` and `effective_to`. An open interval has no `effective_to`. Intervals are half-open: start included, end excluded |
| Immutable raw events | `punch_events` content is insert-only |
| Versioned attendance | Recalculation inserts a new calculation and a new result version. The original remains |
| Immutable finalized payroll | A finalized `payroll_results` row and its children are not updated |
| Explicit history | Cited versions stay. Audit events append. Withdrawal is a status |
| No alias behavior | No `db_alias`, no `previous_db_id`, no client code branch |
| No universal legacy vocabulary | No status number with one meaning for every company. No `employees.photo` policy column. No boss or grand-boss columns |

Actor references point at `users`. A migrated row with no known person uses an explicit unknown-actor marker on that row, not a fabricated user. Role and permission tables are deferred. A role-based approval step stores a `resolver_role_key` until that later security schema exists.

## 3. Domain and table inventory

Disposition is Required, Optional, Merged, or Deferred. Merged and Deferred tables are not specified again in section 4.

### Tenancy and organization

| Candidate | Disposition | Reason |
|---|---|---|
| `companies` | Required | Tenant |
| `branches` | Required | A site under the company |
| `departments` | Required | Under the company, not required to sit under a branch |
| `designations` | Required | Under the company |
| `employee_groups` | Required | Shared policy audience |
| `location_nodes` | Optional | Extra location depth only when a company uses it. Not a factory tree |
| `organization_units` | Merged | A single hierarchy would force department under branch. The separate tables above are the schema |

### Employee

| Candidate | Disposition | Reason |
|---|---|---|
| `employees` | Required | Person in one company |
| `employment_episodes` | Required | Joining-to-separation span, including rejoin |
| `employment_assignments` | Required | Effective-dated department, designation, location, group, status, and shift default |
| `reporting_chains` | Required | One chain in force over an interval |
| `reporting_links` | Required | Ordered positions in that chain. Length is not fixed |
| `users` | Required | Actor identity only |
| `user_employee_bindings` | Required | ESS session is one employee in one company |
| Role and permission tables | Deferred | Authorization is required. Its tables are a later security schema |

### Shift and calendar

| Candidate | Disposition | Reason |
|---|---|---|
| `shifts` | Required | Stable shift identity |
| `shift_versions` | Required | Start, end, punch window, and cross-midnight flag. A cited window cannot be edited in place |
| `shift_assignment_candidates` | Required | Default interval or dated roster entry |
| `shift_resolutions` | Required | The one effective assignment for an employee and `work_date` |
| `weekly_off_rules` | Required | Weekly off is not a photo column and not a public holiday |
| `holiday_calendars` | Required | Named public-holiday calendar for a company |
| `public_holidays` | Required | A holiday date |
| `public_holiday_shifts` | Optional | Only when a company limits a holiday to selected shifts |

### Attendance ingestion

| Candidate | Disposition | Reason |
|---|---|---|
| `punch_sources` | Required | Named source |
| `punch_identities` | Required | Identity mapped to an employee. Not the employee code |
| `punch_identity_overlaps` | Required | Explicit overlap authorization. Rows exist only when a company records one |
| `punch_events` | Required | Immutable event. Source event id is a column, not a separate table |
| Separate punch-source-reference table | Merged | Into `punch_events` |
| Generic legacy-key table | Deferred | Migrated rows carry `source_system` and `source_key` |

### Attendance

| Candidate | Disposition | Reason |
|---|---|---|
| `attendance_calculations` | Required | One calculation of one employee and one `work_date` |
| `attendance_selected_punches` | Required | Which events that calculation used |
| `attendance_day_result_versions` | Required | Immutable result history |
| `attendance_day_results` | Required | Current pointer only. It does not store the result body |
| Batch calculation-run table | Deferred | The required grain is employee plus `work_date` |

### Leave

| Candidate | Disposition | Reason |
|---|---|---|
| `leave_types` | Required | Company-defined type |
| Leave policy table | Merged | Pay effect and which outcome writes the day live in `policy_values` |
| `leave_requests` | Required | Date range, reason, approval subject |
| `leave_day_impacts` | Required | The `work_date`s an allowed outcome marked |

### Correction and approval

| Candidate | Disposition | Reason |
|---|---|---|
| `attendance_corrections` | Required | Request header |
| `attendance_correction_values` | Required | Original, requested, approved, and final values. Field list is not frozen |
| `approval_workflows` | Required | Workflow identity |
| `approval_workflow_versions` | Required | Effective-dated step list |
| `approval_workflow_steps` | Required | Ordered resolvers |
| `approval_instances` | Required | One subject’s run, with the binding version copied |
| `approval_instance_steps` | Required | The steps that bound that instance |
| `approval_actions` | Required | Acting user, time, outcome |

### Overtime

| Candidate | Disposition | Reason |
|---|---|---|
| `overtime_calculations` | Required | Calculated OT |
| `payable_overtime` | Required | Payable OT |
| Overtime approval table | Merged | Into `approval_instances` when a company enables that subject |
| Paid-OT table | Merged | Money is a `payroll_result_heads` amount. The payable figure used is a `payroll_result_inputs` row |

### Payroll

| Candidate | Disposition | Reason |
|---|---|---|
| `salary_heads` | Required | Company-defined earning or deduction identity |
| `salary_structures` | Required | Structure identity |
| `employee_structure_assignments` | Required | Which structure an employee has over an interval |
| `salary_structure_heads` | Required | Which heads a structure includes over an interval |
| `employee_structure_head_values` | Required | Employee-specific head inputs over an interval. No amounts are specified |
| `payroll_periods` | Required | Period identity and bounds |
| `payroll_period_events` | Required | Finalization is on the result. Close and reopen are events |
| `payroll_runs` | Required | A calculation version for the period |
| `payroll_run_declared_inputs` | Required | Named inputs that version may use |
| `payroll_results` | Required | The employee snapshot. This is the finalized payroll record |
| `payroll_result_heads` | Required | Head amounts, including deductions and paid OT when declared |
| `payroll_result_inputs` | Required | Copied values of declared inputs |
| `payroll_result_optional_facts` | Required | Place for optional lines. No line is selected |
| Separate `payroll_snapshots` | Merged | Into `payroll_results` and its child tables |
| Payslip amount table | Merged | The payslip reads the current finalized `payroll_results` row |

### Period control

| Candidate | Disposition | Reason |
|---|---|---|
| `attendance_periods` | Required | Period identity |
| `attendance_period_events` | Required | Cutoff changes are events. Enablement is not preset |
| Payroll period tables | Required | Listed under payroll |

### Policy, audit, extensions

| Candidate | Disposition | Reason |
|---|---|---|
| `policy_definitions` | Required | Catalog of policy keys. No values |
| `policy_values` | Required | Scope, precedence, interval, and value |
| Separate policy-scope table | Merged | Scope type and scope id are columns of `policy_values` |
| `policy_citations` | Required | The policy value a calculation or run used |
| `audit_events` | Required | Append-only history where the row itself is not the history |
| `extension_catalog` | Required | Product list of known slots. Global |
| `company_extension_enablements` | Required | Off until a company enables one |

## 4. Detailed table specifications

Shared rules for every table below:

- Primary key column is `id`, a ULID, required.
- `company_id` is required on DIRECT tables and points at `companies.id`.
- `created_at` is required. Insert-only tables have no `updated_at`.
- `source_system` and `source_key` are optional. They hold a legacy or external identity. They are not the primary key and not a business code.
- Nullable intent is written Required or Optional.
- No table contains `db_alias`, `previous_db_id`, a photo policy, or boss and grand-boss columns.

### 4.1 companies

| Item | Specification |
|---|---|
| Domain | Tenancy |
| Purpose | One tenant |
| Primary key | `id` |
| Company scope | Not applicable. This row is the company |
| Business identifier | `company_code`, optional, unique among companies when present |
| Mutability | Identity immutable. `status` may change and is audited |
| Lifecycle | Created and retained. Not hard-deleted |

| Field | Intent | Purpose |
|---|---|---|
| `company_code` | Optional | External label |
| `company_name` | Required | Display name |
| `status` | Required | Active or withdrawn |

Foreign keys: none. Uniqueness: `company_code` when present. Access: by `id`. Audit: status changes.

### 4.2 branches, departments, designations, employee_groups

These four tables share one shape. Names differ. A department does not require a branch.

| Item | Specification |
|---|---|
| Domain | Organization |
| Purpose | Company structure used by assignments and policy scope |
| Company scope | DIRECT |
| Business identifier | `code`, optional, unique per company per table when present |
| Mutability | Identity immutable. Name and status changes are audited. Effective use is on `employment_assignments` and policy intervals, not by overwriting history |
| Lifecycle | Retained after withdrawal |

| Field | Intent | Purpose |
|---|---|---|
| `company_id` | Required | Owner |
| `code` | Optional | Business label |
| `name` | Required | Display name |
| `status` | Required | Active or withdrawn |

`location_nodes` is the optional fifth table: same fields, plus required `branch_id`. It exists for extra depth. Unit, floor, and line are not columns.

Foreign keys: `company_id`; `location_nodes.branch_id` to `branches`. Uniqueness: (`company_id`, `code`) when code is present. Audit: name and status.

### 4.3 employees

| Item | Specification |
|---|---|
| Domain | Employee |
| Purpose | The person master inside one company |
| Company scope | DIRECT |
| Business identifier | `employee_code` |
| Mutability | Identity immutable. Status lives on assignments and episodes, not as the only history |
| Lifecycle | Retained after separation |

| Field | Intent | Purpose |
|---|---|---|
| `company_id` | Required | Owner. An employee has one company |
| `employee_code` | Required | Business code. Not the primary key. Not the punch identity |
| `display_name` | Required | Name |

No weekly-off column. No shift column. No pay amounts. Additional attributes, when a company enables them, are a later configuration concern and are not a fixed legacy field list.

Foreign keys: `company_id`. Uniqueness: `UNIQUE (company_id, employee_code)` for every employee row in the company. Separation does not release the code. A later identity must not receive it. Rejoin keeps the same employee row, the same code, and a new episode. Access: (`company_id`, `employee_code`), (`company_id`, `id`). Audit: creation and code change. Closed by `docs/UNIVERSAL_HRMS_STEP20_FINAL_EMPLOYEE_CODE_DECISION.md`. The earlier “reuse not decided / no all-history unique” wording is withdrawn.

### 4.4 employment_episodes

| Item | Specification |
|---|---|
| Domain | Employment |
| Purpose | One joining-to-separation span. Rejoin inserts another episode |
| Company scope | DIRECT |
| Mutability | Append-only. Dates are not rewritten to erase the episode |
| Lifecycle | Retained |

| Field | Intent | Purpose |
|---|---|---|
| `company_id` | Required | Owner |
| `employee_id` | Required | The person |
| `joining_date` | Required | No attendance or pay day is created before this date |
| `last_working_date` | Optional | No day is invented after this date. Empty while the episode is open |
| `episode_status` | Required | Open, inactive, or separated. Pay effect of inactive is not chosen |

Foreign keys: `employee_id` to `employees` in the same company. Uniqueness: one open episode per employee. Access: (`company_id`, `employee_id`, `joining_date`). Audit: status and last working date.

### 4.5 employment_assignments

| Item | Specification |
|---|---|
| Domain | Employment |
| Purpose | One fact over an interval |
| Company scope | DIRECT |
| Effective dating | `effective_from` required, `effective_to` optional |
| Mutability | A closed interval is immutable. A change inserts the next interval |
| Lifecycle | Retained |

| Field | Intent | Purpose |
|---|---|---|
| `company_id` | Required | Owner |
| `employment_episode_id` | Required | Episode |
| `employee_id` | Required | Denormalized owner for access |
| `fact_kind` | Required | One of department, designation, location, group, status, shift_default |
| `fact_reference_id` | Required | The branch, department, designation, group, location node, or shift cited by that kind. Status uses a status token, not a legacy spelling |
| `effective_from` | Required | Interval start |
| `effective_to` | Optional | Interval end, excluded |

Foreign keys: episode, employee, and the referenced organization or shift row, same company. Uniqueness: one interval of the same `fact_kind` contains a given date. Overlap is rejected. Access: (`company_id`, `employee_id`, `fact_kind`, `effective_from`). Audit: each new interval.

### 4.6 reporting_chains and reporting_links

`reporting_chains`: DIRECT, effective-dated, one chain contains a given date for an employee. Fields: `company_id`, `employee_id`, `effective_from`, `effective_to`. Closed chains are immutable.

`reporting_links`: DIRECT, immutable with the chain. Fields: `company_id`, `reporting_chain_id`, `sequence_no`, `manager_employee_id`. `sequence_no` starts at 1 for the nearest manager and increases. The number of links is whatever chain the company stored. There is no first-boss or second-boss column.

Foreign keys: both employees in the same company. Uniqueness: (`reporting_chain_id`, `sequence_no`). Access: employee plus date, then links by sequence. Audit: chain creation.

### 4.7 shifts and shift_versions

`shifts`: DIRECT. Fields: `company_id`, optional `shift_code`, `name`, `status`. Identity is stable.

`shift_versions`: DIRECT, immutable once cited. Fields:

| Field | Intent | Purpose |
|---|---|---|
| `company_id` | Required | Owner |
| `shift_id` | Required | Identity |
| `effective_from` | Required | When this window became valid |
| `effective_to` | Optional | When it stopped |
| `start_time` | Required | Time of day the shift starts |
| `end_time` | Required | Time of day the shift ends |
| `punch_window_start` | Required | Earliest punch time considered |
| `punch_window_end` | Required | Latest punch time considered |
| `crosses_midnight` | Required | True when the window passes midnight |

No grace column. A window from 09:00 to 09:00 with `crosses_midnight` true is a 24-hour shift whose `work_date` is the start date. Example: Oct 9 09:00 through Oct 10 09:00 has `work_date` Oct 9.

Uniqueness: one version of a shift contains a given date. Access: (`company_id`, `shift_id`, `effective_from`). Audit: new version.

### 4.8 shift_assignment_candidates

DIRECT, effective-dated, closed rows immutable.

| Field | Intent | Purpose |
|---|---|---|
| `company_id` | Required | Owner |
| `employee_id` | Required | Who |
| `candidate_kind` | Required | `default` or `roster` |
| `shift_version_id` | Required | The window |
| `effective_from` | Required | For a default, the interval start. For a roster date, that date |
| `effective_to` | Optional | For a default, the interval end. For a single roster date, the next date |
| `roster_work_date` | Optional | Set for a roster candidate. This is a `work_date`, not a punch date |

Uniqueness: candidates of the same kind and precedence do not overlap for one employee. Default and roster may both exist. Which one wins is not stored here. Access: (`company_id`, `employee_id`, `effective_from`). Audit: each insert.

### 4.9 shift_resolutions

DIRECT, immutable, one per calculation.

| Field | Intent | Purpose |
|---|---|---|
| `company_id` | Required | Owner |
| `employee_id` | Required | Who |
| `work_date` | Required | Business date. Not a punch date |
| `shift_assignment_candidate_id` | Required | The candidate that won |
| `shift_version_id` | Required | The window used |
| `attendance_calculation_id` | Required | The calculation that resolved it |
| `policy_citation_id` | Optional | The shift-winner policy value used. Empty until a company sets BD-D03 |

Uniqueness: one resolution per calculation. Among current results, one resolution per (`company_id`, `employee_id`, `work_date`) because the current pointer cites one calculation. Access: (`company_id`, `employee_id`, `work_date`). Audit: the calculation insert is the audit.

### 4.10 weekly_off_rules, holiday_calendars, public_holidays, public_holiday_shifts

`weekly_off_rules`: DIRECT, effective-dated. Fields: `company_id`, `scope_kind` (company, group, or employee exception), `scope_id` optional for company scope, `weekday_set`, `effective_from`, `effective_to`. One rule at a scope contains a given date. Person exception is allowed. No photo column.

`holiday_calendars`: DIRECT. Fields: `company_id`, `name`, `status`.

`public_holidays`: DIRECT. Fields: `company_id`, `holiday_calendar_id`, `holiday_date`, `name`. A holiday date already cited by a stored result is not edited. A future correction inserts a successor and marks the prior row withdrawn.

`public_holiday_shifts`: OPTIONAL, DIRECT. Fields: `company_id`, `public_holiday_id`, `shift_id`. Absent means the holiday is not shift-limited.

Access: calendar by company; holidays by (`company_id`, `holiday_date`); weekly off by scope and date. Audit: new versions and withdrawals.

### 4.11 punch_sources

DIRECT. Fields: `company_id`, `source_name` required, `status` required. `source_name` is unique per company. Enablement changes are audited. This table does not store a vendor protocol.

### 4.12 punch_identities and punch_identity_overlaps

`punch_identities`: DIRECT, effective-dated, closed rows immutable.

| Field | Intent | Purpose |
|---|---|---|
| `company_id` | Required | Owner |
| `employee_id` | Required | Mapped person |
| `identity_value` | Required | The punch identity. Not `employee_code` |
| `effective_from` | Required | Active start |
| `effective_to` | Optional | Active end |

Uniqueness: one active identity value per company unless a `punch_identity_overlaps` row authorizes that interval. How many active identities one employee may hold is a company setting, not a second unique key.

`punch_identity_overlaps`: DIRECT, immutable. Fields: `company_id`, `identity_value`, `effective_from`, `effective_to`, `reason` required. Access: (`company_id`, `identity_value`, `effective_from`). Audit: inserts.

### 4.13 punch_events

DIRECT, immutable, never hard-deleted.

| Field | Intent | Purpose |
|---|---|---|
| `company_id` | Required | Owner, derived through the identity mapping at ingestion and then stored |
| `punch_identity_id` | Required | Mapping in force at `event_at` |
| `punch_source_id` | Required | Named source |
| `event_at` | Required | Actual calendar timestamp. Not rewritten to `work_date` |
| `ingested_at` | Required | When the product accepted it |
| `source_event_id` | Optional | Present only when the source provides one |
| `withdrawal_status` | Required | Accepted or withdrawn. Withdrawal does not remove the row |
| `source_system` | Optional | Legacy origin |
| `source_key` | Optional | Legacy key, uninterpreted. Not a device id |

No `work_date`. No status. No overtime. No `area` or `balid` meaning. If a legacy load must retain those two raw texts, they sit in `source_key` or an uninterpreted import note, not as business columns.

Uniqueness: when `source_event_id` is present, one row per (`company_id`, `punch_source_id`, `source_event_id`). No uniqueness on (`event_at`, identity). Two events in the same second may both exist. Access: (`company_id`, `punch_identity_id`, `event_at`); source event id when present. Audit: ingestion and withdrawal are audit events. The event body is the history.

### 4.14 attendance_calculations

DIRECT, immutable, append-only.

| Field | Intent | Purpose |
|---|---|---|
| `company_id` | Required | Owner |
| `employee_id` | Required | Who |
| `work_date` | Required | Business date assigned by the effective shift |
| `shift_resolution_id` | Required | The one effective shift |
| `previous_calculation_id` | Optional | Prior calculation when this is a recalculation |
| `correction_id` | Optional | Set when this calculation applies an approved correction |
| `leave_day_impact_id` | Optional | Set when leave marked the day |
| `selection_state` | Required | `selected` or `explicitly_empty` |
| `calculated_at` | Required | When |
| `calculated_by_user_id` | Optional | Required for a product-native run. Unknown marker allowed for migration |
| `generation_kind` | Required | `original` or `recalculation` |

Foreign keys: employee, resolution, previous calculation, correction, leave impact, user, all same company. Uniqueness: `id`. One original generation per employee and `work_date` in ordinary operation. Access: (`company_id`, `employee_id`, `work_date`, `calculated_at`). Audit: the row is the history.

### 4.15 attendance_selected_punches

DIRECT, immutable with the calculation.

| Field | Intent | Purpose |
|---|---|---|
| `company_id` | Required | Owner |
| `attendance_calculation_id` | Required | Calculation |
| `punch_event_id` | Required | The event |
| `work_date` | Required | The business date this selection assigned. The punch still keeps `event_at` |
| `selection_role` | Optional | A label such as the company’s selected in or out. The label set is not universal |

An empty selection has no rows and `attendance_calculations.selection_state = explicitly_empty`. Uniqueness: (`attendance_calculation_id`, `punch_event_id`). Access: by calculation; by punch event for traceability.

### 4.16 attendance_day_result_versions

DIRECT, immutable.

| Field | Intent | Purpose |
|---|---|---|
| `company_id` | Required | Owner |
| `employee_id` | Required | Who |
| `work_date` | Required | Business date |
| `attendance_calculation_id` | Required | The calculation that produced it |
| `shift_version_id` | Required | Effective shift window |
| `status_code` | Required | The company’s status token. Not a universal letter or word |
| `incomplete_condition` | Optional | Missing in, missing out, or both. Distinct from `status_code`. Outcome remains BD-D02 |
| `late_duration` | Optional | Measured duration. Grace and measurement start remain BD-D01 and BD-D04 |
| `early_duration` | Optional | Measured duration. Pay effect remains BD-D05 |
| `correction_id` | Optional | Set on a version produced by an approved correction |
| `previous_version_id` | Optional | The version this one succeeded |

No pay amount. No raw punch timestamp. Uniqueness: one version per calculation. Access: (`company_id`, `employee_id`, `work_date`). Audit: the version row is the history.

### 4.17 attendance_day_results

DIRECT. This is only the current pointer.

| Field | Intent | Purpose |
|---|---|---|
| `company_id` | Required | Owner |
| `employee_id` | Required | Who |
| `work_date` | Required | Business date |
| `current_version_id` | Required | The version reports read |
| `pointer_updated_at` | Required | When the pointer last moved |

Uniqueness: one row per (`company_id`, `employee_id`, `work_date`). The pointer changes only in the same operation that inserts a new version. Versions are not updated. Audit: pointer moves.

### 4.18 leave_types and leave_requests and leave_day_impacts

`leave_types`: DIRECT. Fields: `company_id`, `type_code`, `name`, `status`, `effective_from`, `effective_to`. Pay effect is not a column. It is a policy value (BD-D06). Uniqueness: (`company_id`, `type_code`) for an open interval.

`leave_requests`: DIRECT. Request content is immutable after submit. Fields: `company_id`, `employee_id`, `leave_type_id`, `start_date`, `end_date`, `reason` required, `requested_by_user_id`, `requested_at`, `approval_instance_id`, `request_state`. State follows the approval instance. A legacy status number is not a column value with universal meaning.

`leave_day_impacts`: DIRECT, immutable once written. Fields: `company_id`, `leave_request_id`, `employee_id`, `work_date`, `attendance_day_result_version_id`. Written only when the company’s configured outcome allows the day to be marked. Which outcome that is remains configuration.

Overlap of two requests on the same dates is not a unique constraint. Access: employee plus date range; impacts by `work_date`. Audit: request submit and each approval action.

### 4.19 attendance_corrections and attendance_correction_values

`attendance_corrections`: DIRECT.

| Field | Intent | Purpose |
|---|---|---|
| `company_id` | Required | Owner |
| `employee_id` | Required | Who |
| `work_date` | Required | The day being corrected |
| `original_result_version_id` | Required | The result version that was current at request |
| `approval_instance_id` | Required | Generic approval |
| `applied_calculation_id` | Optional | Filled when application commits a recalculation |
| `applied_result_version_id` | Optional | The new current version |
| `reason` | Required | Why |
| `requested_by_user_id` | Required | Requester. Unknown marker only for migration |
| `requested_at` | Required | When |
| `correction_state` | Required | `submitted`, `in_approval`, `approved`, `rejected`, or `applied` |

`attendance_correction_values`: DIRECT, each value immutable once written.

| Field | Intent | Purpose |
|---|---|---|
| `company_id` | Required | Owner |
| `attendance_correction_id` | Required | Header |
| `value_stage` | Required | `original`, `requested`, `approved`, or `final` |
| `field_key` | Required | Which fact. The allowed keys are company configuration (BD-D16), not a fixed in/out pair |
| `value_text` | Required | The value at that stage |

Original and requested rows are written at submit and never updated. Approved rows are written when approval completes. Final rows are written when recalculation commits. All four stages remain. The raw punch is not a parent that gets updated.

Uniqueness: one (`correction`, `value_stage`, `field_key`). One in-progress correction application per employee and `work_date`. Access: (`company_id`, `employee_id`, `work_date`). Audit: state transitions and value inserts.

### 4.20 approval_workflows, versions, and steps

`approval_workflows`: DIRECT. Fields: `company_id`, `subject_kind` (`leave_request`, `attendance_correction`, or `payable_overtime`), `name`, `status`.

`approval_workflow_versions`: DIRECT, effective-dated, immutable once an instance cites it. Fields: `company_id`, `approval_workflow_id`, `effective_from`, `effective_to`, `bypass_policy` optional. `bypass_policy` stays empty until the company decides BD-D17. Empty is not a chosen “no bypass” rule for every company. It means not configured.

`approval_workflow_steps`: DIRECT, immutable with the version.

| Field | Intent | Purpose |
|---|---|---|
| `company_id` | Required | Owner |
| `approval_workflow_version_id` | Required | Version |
| `sequence_no` | Required | Order, unique inside the version, starting at 1 |
| `resolver_kind` | Required | `role`, `reporting_manager`, `organization_approver`, or `named_fallback` |
| `resolver_role_key` | Optional | Set when kind is role. The roles table is deferred |
| `reporting_depth` | Optional | Set when kind is reporting manager. 1 is the nearest link. Further depths are allowed. This is not a boss or grand-boss field |
| `organization_reference_id` | Optional | Set when kind is organization approver |
| `fallback_user_id` | Optional | Set when kind is named fallback |

No company is given a default step list. Employee, manager, HR, and payroll is a legal configuration, not seeded data.

Uniqueness: (`version`, `sequence_no`). One open version per workflow contains a given date. Audit: new version.

### 4.21 approval_instances, instance steps, and actions

`approval_instances`: DIRECT. The binding step list is copied and then immutable. Fields: `company_id`, `subject_kind`, `subject_id`, `approval_workflow_version_id`, `instance_state` (`in_progress`, `approved`, `rejected`, `cancelled`), `started_at`.

`approval_instance_steps`: DIRECT, immutable copy. Fields: `company_id`, `approval_instance_id`, `sequence_no`, plus the resolver fields copied from the definition step.

`approval_actions`: DIRECT, append-only, immutable.

| Field | Intent | Purpose |
|---|---|---|
| `company_id` | Required | Owner |
| `approval_instance_id` | Required | Instance |
| `approval_instance_step_id` | Required | The step taken |
| `acting_user_id` | Optional | Required for a product-native action. Unknown marker only for migration |
| `action_outcome` | Required | `approved` or `rejected` |
| `action_at` | Required | When |
| `comment` | Optional | Reason or comment |

The server accepts an action only when that user may take that step. Bypass of an earlier step is stored as an action only when that instance’s copied policy allows it. Uniqueness: a step that is not configured for bypass has at most one action. Access: by subject; by instance. Audit: each action is the history.

### 4.22 overtime_calculations

DIRECT, immutable with the result version.

| Field | Intent | Purpose |
|---|---|---|
| `company_id` | Required | Owner |
| `employee_id` | Required | Who |
| `work_date` | Required | Business date |
| `attendance_day_result_version_id` | Required | Source result |
| `shift_version_id` | Required | Window used |
| `calculated_quantity` | Required | The figure. Unit is whatever the company’s formula policy states. No formula is stored as a number here |
| `quantity_unit_key` | Required | A label from the formula policy version, not a universal seconds-or-hours choice |

Uniqueness: one row per result version. Access: (`company_id`, `employee_id`, `work_date`). Audit: the row.

### 4.23 payable_overtime

DIRECT, versioned, a written row is immutable.

| Field | Intent | Purpose |
|---|---|---|
| `company_id` | Required | Owner |
| `employee_id` | Required | Who |
| `work_date` | Required | Business date |
| `overtime_calculation_id` | Required | The calculated figure considered |
| `payable_quantity` | Required | May differ from calculated |
| `quantity_unit_key` | Required | Unit label from policy |
| `approval_instance_id` | Optional | Present only when the company enabled OT approval |
| `previous_payable_id` | Optional | Prior payable version |
| `payable_state` | Required | `current` or `superseded` |

No eligibility flag column copied from legacy. No rate. No minimum. No maximum. Uniqueness: one current row per employee and `work_date`. Access: (`company_id`, `employee_id`, `work_date`). Audit: each version.

### 4.24 salary_heads, salary_structures, assignments, and values

`salary_heads`: DIRECT, effective-dated identity. Fields: `company_id`, `head_code`, `name`, `head_kind` (`earning` or `deduction`), `effective_from`, `effective_to`, `status`. Uniqueness: one open (`company_id`, `head_code`). No universal head catalogue and no absenteeism formula.

`salary_structures`: DIRECT. Fields: `company_id`, `structure_code`, `name`, `status`.

`employee_structure_assignments`: DIRECT, effective-dated. Fields: `company_id`, `employee_id`, `salary_structure_id`, `effective_from`, `effective_to`. One assignment contains a given date.

`salary_structure_heads`: DIRECT, effective-dated. Fields: `company_id`, `salary_structure_id`, `salary_head_id`, `effective_from`, `effective_to`. Closed rows immutable.

`employee_structure_head_values`: DIRECT, effective-dated, closed rows immutable. Fields: `company_id`, `employee_id`, `salary_head_id`, `amount`, `effective_from`, `effective_to`. This holds the employee’s input amount for a head. The specification defines no amounts and no formula that produces them. A payroll run copies the value it used.

Access: employee plus date; structure plus head. Audit: new intervals.

### 4.25 attendance_periods and attendance_period_events

`attendance_periods`: DIRECT. Fields: `company_id`, `period_start`, `period_end`, `label`. Uniqueness: one period per company for a given start. Cutoff is not defaulted on.

`attendance_period_events`: DIRECT, append-only. Fields: `company_id`, `attendance_period_id`, `event_kind` (`cutoff_enabled`, `cutoff_boundary_set`, `cutoff_disabled`), `boundary_at` optional, `acted_by_user_id`, `acted_at`. Whether any event exists is BD-D13. The table is empty of business choices.

### 4.26 payroll_periods and payroll_period_events

`payroll_periods`: DIRECT. Fields: `company_id`, `period_start`, `period_end`, `label`. Uniqueness: one period per company and start.

`payroll_period_events`: DIRECT, append-only. Fields: `company_id`, `payroll_period_id`, `event_kind` (`close_enabled`, `closed`, `reopened`), `reason` required for `reopened`, `acted_by_user_id`, `acted_at`. Close is not preset (BD-D14). Reopen roles are not stored (BD-D15 deferred). A `reopened` event is valid only after a `closed` event. The event does not update finalized results.

### 4.27 payroll_runs and payroll_run_declared_inputs

`payroll_runs`: DIRECT. A completed version is immutable.

| Field | Intent | Purpose |
|---|---|---|
| `company_id` | Required | Owner |
| `payroll_period_id` | Required | Period |
| `run_sequence` | Required | 1, 2, 3… for that period |
| `run_state` | Required | `in_progress`, `draft`, `finalized`, or `superseded` |
| `attendance_usage` | Required | `uses_declared_attendance` or `declares_no_attendance` |
| `calculated_by_user_id` | Optional | Required for a product-native run |
| `calculated_at` | Optional | Set when calculation completes |
| `previous_run_id` | Optional | Prior version |

Uniqueness: (`payroll_period_id`, `run_sequence`). One `in_progress` run per company and period.

`payroll_run_declared_inputs`: DIRECT, immutable with the run. Fields: `company_id`, `payroll_run_id`, `input_key`. `input_key` names an allowed input such as a day-result aggregate, leave, or payable OT. The keys a company declares are configuration. A run that ignores attendance has no attendance keys and `attendance_usage = declares_no_attendance`.

### 4.28 payroll_results, heads, inputs, and optional facts

`payroll_results` is the employee snapshot. DIRECT. Finalized content is immutable.

| Field | Intent | Purpose |
|---|---|---|
| `company_id` | Required | Owner |
| `payroll_run_id` | Required | Version |
| `payroll_period_id` | Required | Period |
| `employee_id` | Required | Who |
| `employee_structure_assignment_id` | Required | Structure identity in force |
| `net_pay` | Required | Result |
| `finalization_state` | Required | `draft` or `finalized` |
| `is_current` | Required | One current row per employee and period |
| `calculated_at` | Required | Copied from the run |
| `finalized_by_user_id` | Optional | Required when state becomes finalized |
| `finalized_at` | Optional | Required when state becomes finalized |
| `previous_result_id` | Optional | Prior snapshot version, including a prior finalized row after reopen |

`payroll_result_heads`: DIRECT, immutable with the result. Fields: `company_id`, `payroll_result_id`, `salary_head_id`, `amount`, `head_kind`. Deductions are rows with `head_kind = deduction`. Paid OT, when the run declared it, is an earning row plus a matching input citation. It is not an attendance column.

`payroll_result_inputs`: DIRECT, immutable copied facts. Fields: `company_id`, `payroll_result_id`, `input_key`, `source_record_id` optional, `copied_value` required. `source_record_id` may cite an attendance version or a payable OT row. The copy does not update when that source later versions. A declared input with no copied value blocks finalization.

`payroll_result_optional_facts`: DIRECT, immutable with the result. Fields: `company_id`, `payroll_result_id`, `fact_key`, `copied_value`. `fact_key` may be payable days, paid leave, unpaid leave, absence, late or early treatment, calculated OT, approved OT, or payable OT. No key is required by this specification (BD-D18). A row exists only when that company’s policy says to store it.

Uniqueness: one result per (`payroll_run_id`, `employee_id`). One `is_current` finalized or draft result per (`company_id`, `payroll_period_id`, `employee_id`). Access: (`company_id`, `payroll_period_id`, `employee_id`). Audit: finalization is an audit event. The result rows are the snapshot.

The payslip reads the current finalized `payroll_results` row and its children. It has no table of amounts.

### 4.29 policy_definitions and policy_values and policy_citations

`policy_definitions`: GLOBAL. No `company_id`. Fields: `policy_key` required and unique, `description`. The catalog lists capability keys only:

`grace`, `late`, `early_out`, `incomplete_punch`, `shift_precedence`, `leave_pay`, `absence_pay`, `payable_days`, `ot_eligibility`, `ot_calculation`, `ot_rounding`, `ot_approval`, `ot_rate_payment`, `attendance_cutoff`, `payroll_close`, `correction_fields`, `approval_sequence`, `snapshot_optional_facts`, `weekly_off_exception`, `punch_sources_accepted`, `duplicate_punch_treatment`, `punch_selection`, `punch_identity_cardinality`.

No row in this specification contains a value. Reopen roles are not a key. BD-D15 stays deferred.

`policy_values`: DIRECT except a product-scope default, which has no `company_id`. A cited row is immutable. A change inserts a new row.

| Field | Intent | Purpose |
|---|---|---|
| `company_id` | Optional | Required unless `scope_kind` is `product` |
| `policy_definition_id` | Required | Which policy |
| `scope_kind` | Required | `product`, `company`, `branch`, `department`, `employee_group`, `shift`, or `employee` |
| `scope_id` | Optional | Required for every scope except product and company |
| `precedence` | Required | Order inside that scope. Lower scopes are used only when the policy allows them |
| `effective_from` | Required | Interval start |
| `effective_to` | Optional | Interval end |
| `value_payload` | Optional | The company’s value. Empty in this specification. Shape depends on the key and is not chosen |

Allowed lower scopes follow the approved levels: grace, late, early, and OT formula may go to shift; weekly off may go to group and rarely employee; OT eligibility and rate may go to group and rarely employee; shift winner, incomplete punch, leave pay, absence, payable days, rounding, OT approval, correction fields, approval steps, cutoff, and snapshot lines stay at company. Branch override exists only when that company has enabled it for that policy.

Uniqueness: one open interval per policy, company, scope, and precedence. Overlap is rejected. Resolution does not depend on scan order.

`policy_citations`: DIRECT, immutable. Fields: `company_id`, `subject_kind`, `subject_id`, `policy_value_id`. Subjects are attendance calculations and payroll runs. Access: by subject; policy lookup by (`company_id`, `policy_definition_id`, `scope_kind`, `effective_from`). Audit: new policy rows.

### 4.30 audit_events

DIRECT, append-only, immutable, never hard-deleted.

| Field | Intent | Purpose |
|---|---|---|
| `company_id` | Required | Owner |
| `subject_kind` | Required | The business object |
| `subject_id` | Required | That object’s id |
| `action_kind` | Required | What happened |
| `acting_user_id` | Optional | Required for product-native sensitive actions. Unknown marker for migration |
| `acted_at` | Required | When |
| `previous_value` | Optional | Old value when the subject is not itself a version |
| `new_value` | Optional | New value |
| `reason` | Optional | Required for correction actions and for payroll reopen |

Access: (`company_id`, `subject_kind`, `subject_id`, `acted_at`).

### 4.31 extension_catalog and company_extension_enablements

`extension_catalog`: GLOBAL. Fields: `extension_key` unique, `name`. Keys are the four approved slots: legal payslip layout, external day-count feed, mobile location punch source, and an unnamed future device protocol. No vendor.

`company_extension_enablements`: DIRECT, effective-dated. Fields: `company_id`, `extension_catalog_id`, `enabled` , `effective_from`, `effective_to`. Default for a company with no row is off. Audit: each change.

### 4.32 users and user_employee_bindings

`users`: DIRECT. Fields: `company_id`, `user_name`, `status`. One company per user. A person in two companies is two user rows. This is not a permission model.

`user_employee_bindings`: DIRECT. Fields: `company_id`, `user_id`, `employee_id`, `effective_from`, `effective_to`. One open binding per user. The employee is in the same company. This is the ESS binding.

Access: user by company; binding by user. Audit: binding changes and user status.

## 5. Identifier strategy

| Table class | Primary identifier | Business identifier | Source identifier |
|---|---|---|---|
| Every table | `id` ULID | None, unless listed | Optional `source_system` + `source_key` |
| `companies` | `id` | `company_code` | Legacy company id in `source_key` if loaded |
| `employees` | `id` | `employee_code` | Legacy employee id in `source_key` |
| `punch_identities` | `id` | `identity_value` | Legacy card key in `source_key` |
| `punch_events` | `id` | None | `source_event_id` when the source has one, else `source_key` |
| `shifts`, heads, structures, leave types, workflows | `id` | `shift_code`, `head_code`, `structure_code`, `type_code`, workflow `name` | Optional `source_key` |
| `payroll_periods`, `attendance_periods` | `id` | `label` plus the date bounds | Optional `source_key` |
| `payroll_results` | `id` | None. The business identity is company, period, and employee | Optional `source_key` for a legacy salary row |

Business identifiers never become primary keys. Imported identifiers never replace `id`.

## 6. Company scoping

| Scope | Tables |
|---|---|
| DIRECT | All operational tables in section 4 except `companies`, `policy_definitions`, `extension_catalog`, and product-scope `policy_values` |
| INDIRECT, and still stores `company_id` | `reporting_links`, `approval_workflow_steps`, `approval_instance_steps`, `approval_actions`, `attendance_selected_punches`, `attendance_correction_values`, `payroll_run_declared_inputs`, `payroll_result_heads`, `payroll_result_inputs`, `payroll_result_optional_facts`, `public_holiday_shifts`, `salary_structure_heads` |
| GLOBAL | `policy_definitions`, `extension_catalog`, and `policy_values` rows whose scope is product |
| NOT APPLICABLE | `companies` |

Every operational read and write has a company path. A child that stores `company_id` must match its parent’s company. Punch events receive `company_id` only from a punch identity in that company. No operational foreign key crosses companies.

## 7. Effective dating

Tables with `effective_from` and optional `effective_to`:

| Table | Non-overlap rule |
|---|---|
| `employment_assignments` | One interval per employee, episode, and `fact_kind` contains a date |
| `reporting_chains` | One chain per employee contains a date |
| `shift_versions` | One version per shift contains a date |
| `shift_assignment_candidates` | Same employee, same `candidate_kind`, no overlap. Default and roster may both cover a date |
| `weekly_off_rules` | One rule per scope contains a date |
| `punch_identities` | One active employee per identity value per company, unless an overlap authorization covers that interval |
| `punch_identity_overlaps` | Overlap rows for the same identity do not contradict each other. The authorization is explicit |
| `leave_types` | One open meaning per `type_code` |
| `approval_workflow_versions` | One open version per workflow contains a date |
| `salary_heads` | One open row per `head_code` |
| `employee_structure_assignments` | One structure per employee contains a date |
| `salary_structure_heads` | One inclusion of a head on a structure contains a date |
| `employee_structure_head_values` | One amount per employee and head contains a date |
| `policy_values` | One open row per policy, company, scope, and precedence |
| `company_extension_enablements` | One open row per company and extension |
| `user_employee_bindings` | One open binding per user |

`public_holidays` use `holiday_date` rather than an interval. `shift_resolutions`, attendance rows, and payroll results use `work_date` or a period, not `effective_from`.

Lookup picks the single interval that contains the `work_date` or period date at the required precedence. Overlap at that grain is rejected. Query order is not a rule. No sample dates and no policy values are chosen. The Oct 9 to Oct 10 example in section 8 is an illustration of cross-midnight storage, not a company calendar.

## 8. Attendance schema

Physical path:

```
punch_events.event_at                         immutable calendar timestamp
→ attendance_calculations                     generation_kind = original
→ attendance_day_result_versions              previous_version_id empty
→ attendance_day_results                      current pointer
→ attendance_corrections                      original result version
→ attendance_correction_values                original and requested
→ approval_actions
→ attendance_correction_values                approved
→ attendance_calculations                     generation_kind = recalculation
→ attendance_day_result_versions              new version, cites correction
→ attendance_day_results                      pointer moves
→ attendance_correction_values                final
```

| Concept | Where it lives |
|---|---|
| Punch timestamp | `punch_events.event_at` |
| Ingestion time | `punch_events.ingested_at` |
| `work_date` | Resolution, calculation, selected punch, result version, current pointer, correction. Not on the punch |
| Employee | `employee_id` on those rows |
| Punch source | `punch_events.punch_source_id` |
| Punch identity | `punch_events.punch_identity_id` |
| Source event reference | `punch_events.source_event_id` |
| Selected punches | `attendance_selected_punches` |
| Calculation version | `attendance_calculations.id` and `previous_calculation_id` |
| Original result | The first `attendance_day_result_versions` row for that employee and `work_date` |
| Correction | `attendance_corrections.id` on the later version |
| Final result | `attendance_day_results.current_version_id` |

Cross-midnight: `shift_versions.crosses_midnight`, `start_time`, `end_time`, and the punch window describe the window. A shift from Oct 9 09:00 to Oct 10 09:00 resolves to `work_date` Oct 9. A punch at Oct 10 02:00 keeps `event_at` of Oct 10 02:00. The selected-punch row records that it was used for `work_date` Oct 9 when the window and the company’s selection policy include it. Which punches count remains configuration.

One effective shift: `shift_resolutions` for the current calculation is the only current assignment for that employee and `work_date`. Candidates may be plural. The winner value is BD-D03 and is not filled in.

## 9. Leave schema

`leave_types` define the company’s types. `leave_requests` hold the range and reason and point at one `approval_instances` row. `leave_day_impacts` record each `work_date` actually marked, and the result version that mark produced.

Paid, unpaid, or partial is a `policy_values` row for `leave_pay`, keyed by leave type inside the value payload. This specification stores no pay effect. Which approval outcome may write impacts is a `policy_values` row, not a universal status number.

Leave does not write payroll amounts. Payroll may copy leave facts only when the run declared them.

## 10. Correction and approval schema

Correction preserves four stages in `attendance_correction_values`: original, requested, approved, and final. The header stores reason, requester, timestamps, state, the original result version, and, after application, the new calculation and result version. Approvers are `approval_actions.acting_user_id`, not columns on the correction.

Application is one operation: approval action, approved values, new calculation, new result version, pointer move, and final values. That operation does not update `punch_events` and does not update the original result version.

Approval is generic. A workflow version has an ordered `sequence_no`. Each step has one resolver kind: role, reporting manager, organization approver, or named fallback. The instance copies those steps. Each action stores the user, outcome, time, and optional comment. The number of steps is whatever the company configures. Bypass exists only as `bypass_policy` on a version the company has set. No boss or grand-boss field exists.

`payable_overtime.approval_instance_id` uses the same tables when OT approval is enabled. A punch correction is a different `subject_kind`.

## 11. OT schema

| Figure | Table | Trace |
|---|---|---|
| Calculated OT | `overtime_calculations` | Result version, shift version, formula policy citation |
| Payable OT | `payable_overtime` | Calculated OT row. Approval instance only if enabled. Eligibility, rounding, and limit policy citations |
| Paid OT | `payroll_result_heads` amount and `payroll_result_inputs` copy | Payable OT id in `source_record_id` when OT was declared |

The three quantities may differ. The schema has no eligibility switch, formula, rounding method, minimum, maximum, or rate. Those are empty `policy_values` keys: `ot_eligibility`, `ot_calculation`, `ot_rounding`, `ot_approval`, `ot_rate_payment`.

A later attendance recalculation inserts a new calculated row. It does not update a payable row already copied onto a finalized payroll result. A draft payroll run may cite a newer payable row by inserting a new payroll result version.

## 12. Payroll schema

Grain:

```
payroll_periods
→ payroll_runs                         one version at a time in progress
→ payroll_run_declared_inputs
→ payroll_results                      one employee snapshot per run
→ payroll_result_heads                 earnings, deductions, net is on the parent
→ payroll_result_inputs                copied declared facts
→ payroll_result_optional_facts        only lines the company configured
→ payroll_period_events                close and reopen
```

`payroll_results` stores employee, period, structure assignment, net pay, run version, calculated time, finalization state, finalized actor, and finalized time. Head amounts and deductions are child rows. Declared inputs are named on the run and copied onto the result before finalization. A missing copy blocks finalization.

After `finalization_state = finalized`, attendance recalculation does not update that result, its heads, its inputs, or its optional facts. A new attendance version is invisible to it. The payslip reads this finalized row.

A later reopen requires a `payroll_period_events` row of kind `reopened` after `closed`, with a reason and an actor. The old finalized result stays immutable with `is_current` false. A new draft run may then exist. Who may reopen is not in the schema.

Optional snapshot lines are not selected. `payroll_result_optional_facts` can hold them. BD-D18 chooses whether any row is written.

## 13. Policy schema

`policy_definitions` names the keys in section 4.29. `policy_values` holds company scope, an optional lower scope, precedence, `effective_from`, `effective_to`, and an empty `value_payload`.

For one employee and one `work_date`, resolution walks product, then company, then a lower scope only when that key allows it, and returns one value per key. Two open rows at the same policy, company, scope, and precedence are rejected. The winner is the row whose interval contains the date. Scan order cannot choose a different row.

`policy_citations` attach the winning `policy_values.id` to the attendance calculation or payroll run, so a later policy insert does not change a stored result.

No grace minutes, status outcomes, shift winner, leave pay, absence formula, OT numbers, cutoff dates, correction field list, approval list, or snapshot line list is inserted.

## 14. Audit and history

| Subject | History mechanism |
|---|---|
| Punch ingestion | Insert-only `punch_events` plus an `audit_events` row for acceptance and for withdrawal |
| Attendance recalculation | Append-only calculation, selected punches, result version, and pointer move. `previous_calculation_id` and `previous_version_id` chain them |
| Corrections | Append-only value stages and state. Original stage is never updated. `audit_events` for each transition |
| Approvals | Append-only `approval_actions`. Instance step copy is immutable |
| OT approval | Same approval actions, subject kind `payable_overtime`, plus a new payable version |
| Payroll calculation | Append-only run, declared inputs, result, heads, and copied inputs |
| Payroll finalization | State on the result set once, plus an `audit_events` row with actor and time |
| Payroll reopen or reversal | Append-only `payroll_period_events` with reason, plus `audit_events`. Prior finalized result unchanged |
| Employment, policy, shift candidates, structures | New effective-dated rows. Closed rows unchanged |
| Permission changes | `audit_events` when the deferred security schema exists. User status and ESS binding changes are audited now |

Versioned business rows are the history of those facts. `audit_events` covers transitions and changes that are not themselves a new version. Both are append-only. Neither is hard-deleted.

## 15. Constraints

| Rule | Schema constraint | Decision note |
|---|---|---|
| Company ownership | `company_id` required on operational rows. Child company matches parent company | None |
| Employee code | `UNIQUE (company_id, employee_code)` on every row. Separation does not release the code | Closed by `docs/UNIVERSAL_HRMS_STEP20_FINAL_EMPLOYEE_CODE_DECISION.md` |
| Punch identity | One active `identity_value` per company unless `punch_identity_overlaps` covers the interval | Multiple identities per employee is configuration |
| Source event | One event per company, source, and `source_event_id` when that id is present | Missing source id is valid. No unique key on timestamp plus identity |
| One effective shift | One current resolution per company, employee, and `work_date`, via the current calculation | Which candidate wins is BD-D03 |
| Same-kind assignment overlap | Rejected for the same employee and candidate kind | Default versus roster is allowed |
| Effective-date overlap | Rejected for the tables in section 7 | None |
| Policy overlap | Rejected for the same policy, company, scope, and precedence | Values are BD-D01–BD-D18 and stay empty |
| Correction application | A current-result change requires an approval outcome and writes a new version. One in-progress application per employee and `work_date` | Allowed fields are BD-D16 |
| Approval order | `sequence_no` unique per workflow version and per instance copy | The step list and bypass are BD-D17 |
| Approval actor | A product-native action requires `acting_user_id` | Migration may store unknown |
| Payroll run | One in-progress run per company and period. `run_sequence` unique per period | None |
| Payroll finalization | One current result per employee and period. Finalized children immutable. Declared inputs must be copied | Optional fact lines are BD-D18. Close enablement is BD-D14 |
| OT linkage | Payable row cites calculated row. Paid amount cites payable row when OT was declared. No equality constraint | Formula, eligibility, rounding, approval, and rate are BD-D08–BD-D12 |
| Leave overlap | Not constrained | Not specified by the approved requirements. Not invented |
| Hard delete | No hard delete of punches, result versions, corrections, approval actions, OT versions, finalized results, or audit events | None |

## 16. Index plan

Indexes are conceptual. No engine syntax is specified. Uniqueness indexes are only those in section 15. The others are lookup paths.

| Table | Access path | Index concept |
|---|---|---|
| `punch_events` | Ingestion and shift-window search | Company, punch identity, `event_at` |
| `punch_events` | Idempotent source event | Company, punch source, `source_event_id` where present. Unique |
| `punch_identities` | Map an identity at a time | Company, `identity_value`, `effective_from` |
| `shift_resolutions` | The effective shift for a day | Company, employee, `work_date` |
| `attendance_calculations` | History of a day | Company, employee, `work_date`, `calculated_at` |
| `attendance_day_results` | Current result for reports | Company, employee, `work_date`. Unique |
| `attendance_day_results` | A date range for a company | Company, `work_date` |
| `attendance_day_result_versions` | Versions of a day | Company, employee, `work_date` |
| `attendance_selected_punches` | From calculation to events, and from an event back | Calculation id; punch event id |
| `attendance_corrections` | Open and historical corrections | Company, employee, `work_date` |
| `approval_instances` | By subject | Company, subject kind, subject id |
| `approval_actions` | History of an instance | Instance id, `action_at` |
| `overtime_calculations` | Day figure | Company, employee, `work_date` |
| `payable_overtime` | Current payable figure | Company, employee, `work_date`, state |
| `employment_assignments` | Fact in force on a date | Company, employee, fact kind, `effective_from` |
| `policy_values` | Deterministic lookup | Company, policy, scope kind, scope id, precedence, `effective_from`. Unique for an open interval at that precedence |
| `payroll_runs` | The period’s versions | Company, payroll period, `run_sequence`. Unique on period and sequence |
| `payroll_results` | Payslip and period review | Company, payroll period, employee, `is_current` |
| `payroll_result_inputs` | Reproduce a result | Payroll result id |
| `audit_events` | History of one object | Company, subject kind, subject id, `acted_at` |

A range index on punch time must not be unique. Making it unique would drop a second event in the same second.

## 17. Legacy mapping

No migration procedure is specified.

| Legacy | Target | Transformation | Historical limitation | Validation | Risk |
|---|---|---|---|---|---|
| Company row, not the alias | `companies` | Identity and name. `source_key` holds the legacy id | Alias behavior is dropped | Company boundary | Loading `db_alias` or `previous_db_id` as columns |
| `employees` | `employees`, `employment_episodes`, `employment_assignments` | Code, name, joining, separation, department, designation, group, status become facts | Photo is not loaded as weekly off. Shift id is not an assignment column on the employee | Active code uniqueness | Collapsing history into one employee row |
| `employees.shift_id` | `shift_assignment_candidates` kind `default` | A candidate only | Not the `shift_resolutions` row | BD-D03 before any resolution is marked current | Treating the employee shift as the universal winner |
| `emp_shift_calendar` | `shift_assignment_candidates` kind `roster` | Dated candidates where rows exist | Do not invent dates | Same | Inventing a roster for a company that had none |
| `att_shift` | `shifts`, `shift_versions` | Start, end, window. Next-day becomes `crosses_midnight` | Grace is not copied | Window signs | Copying a grace number into the shift |
| `emp_code_to_punch_card` | `punch_identities` | Identity value and employee | Active duplicates are not merged | Flag duplicates for overlap or retirement | Silent drop of a second card |
| `all_att_log` | `punch_events` | `event_at` from the legacy timestamp. Company from the identity | Middle punches already absent stay absent. `work_date` is not written onto the event | Identity must resolve to one company | Using an empty legacy company column, or interpreting `balid` |
| `area`, `balid` | Optional uninterpreted `source_key` or import note | Not business columns | Meaning is UNKNOWN | Do not validate them as device ids | Promoting them to `source_event_id` |
| `attendance_detail` | `attendance_day_result_versions` and the current pointer | One surviving version | Delete-and-insert removed earlier versions. Leave them unknown | Status token is company-local, not a universal code | Fabricating an original version |
| `hrm_leave_emp_app` | `leave_requests`, `approval_instances` | Range, type, reason, outcome | Numeric status is not stored as a universal code | Map outcome only with a business reading | Treating status 3 as the product’s approved value |
| `leave_emp_annual_leave`, `leave_emp_balance` | No confirmed balance | Source amounts may be retained as a proposed position under LEAVE-CORE-005 | No confirmer is stored. `prev_bal`, `earned`, and `encashment` are not combined | LEAVE-OPENING before any position is confirmed | Copying a stored amount into an approved production balance |
| `attendance_approval_file` | `attendance_corrections`, `attendance_correction_values` | Load stages that exist | Missing original or approved values stay absent. A name is not `acting_user_id` | Do not invent punches | Copying a shift-boundary time back onto `punch_events` |
| `boss_id`, `gboss_id` | Not an approval table. Optional evidence for `reporting_links` | Only if a chain is confirmed | Not steps 1 and 2 of every workflow | BD-D17 | Creating boss columns |
| `employee_overtime` | None | Not loaded as payable OT for the studied months | Empty there | Do not validate it as payable OT | Creating payable rows from an empty legacy table |
| Attendance `ot_hours` | `overtime_calculations` only after unit is known | Not loaded as a universal unit | Seconds and whole hours are different legacy meanings | BD-D09 and BD-D10 | Adding a unit conversion |
| `eligible_for_ot` | None | Not a column | The flag did not match the studied months | BD-D08 | Adding the flag |
| `employee_salary` | `payroll_result_heads` | Amounts only. Inputs marked absent | Cannot reproduce attendance inputs that were not stored | Do not mark those results as having declared attendance | Inventing snapshot inputs |
| `attendance_month_lock` | `attendance_period_events` only after confirmation | Not a direct Yes-to-cutoff load | A Yes flag is not proof of cutoff | BD-D13 | Marking a studied month closed |
| `salary_month_lock` | `payroll_period_events` only after confirmation | Same | A Yes flag is not proof of close | BD-D14 | Marking a studied month closed |

## 18. D01–D18 protection

The schema can store each decision later. It does not store a chosen value. BD-D15 has no policy key and no role list.

| ID | Capability present | Value present |
|---|---|---|
| BD-D01 Grace | `policy_values` key `grace`, shift scope allowed | No |
| BD-D02 Incomplete punch | `incomplete_condition` separate from `status_code`. Policy key `incomplete_punch` | No |
| BD-D03 Shift winner | Candidates and `shift_resolutions`. Policy key `shift_precedence` | No |
| BD-D04 Late start | `late_duration` and policy key `late` | No |
| BD-D05 Early pay | `early_duration` and policy key `early_out` | No |
| BD-D06 Leave pay | `leave_types` and policy key `leave_pay` | No |
| BD-D07 Absence | Policy key `absence_pay`. Optional fact slot | No |
| BD-D08 OT eligibility | Policy key `ot_eligibility` at group scope | No |
| BD-D09 OT formula | `overtime_calculations` plus policy key `ot_calculation` | No |
| BD-D10 OT rounding | Policy key `ot_rounding` | No |
| BD-D11 OT approval | Optional `payable_overtime.approval_instance_id`. Policy key `ot_approval` | No |
| BD-D12 OT rate | Policy key `ot_rate_payment`. Paid amount only as a snapshot head | No |
| BD-D13 Attendance cutoff | `attendance_period_events` | No |
| BD-D14 Payroll close | `payroll_period_events` | No |
| BD-D15 Reopen roles | Event shape exists. Roles do not | Deferred |
| BD-D16 Correction fields | `field_key` on correction values. Policy key `correction_fields` | No |
| BD-D17 Approval sequence | Ordered steps and empty `bypass_policy`. Policy key `approval_sequence` | No |
| BD-D18 Optional snapshot lines | `payroll_result_optional_facts` | No |

Payable-day basis uses policy key `payable_days`. No 30-day value is stored.

## 19. Deferred implementation decisions

Not in this specification:

- SQL, migrations, and physical creation of these tables
- Engine, column types, index implementation, partitioning, replication, backup, sizing, and hosting
- API, UI, and application framework
- Queues, caches, and deployment
- Role, permission, and scope tables
- Legal or company retention durations
- Reuse of `employee_code` after separation is no longer deferred. Codes are not reused inside a company. See `docs/UNIVERSAL_HRMS_STEP20_FINAL_EMPLOYEE_CODE_DECISION.md`
- Leave-request overlap policy
- The punch writer and the meaning of legacy `area` and `balid`
- Any D01–D18 value, including BD-D15

ULID is the identifier recommendation. It is not an engine selection and not a migration.

## 20. Schema readiness

| Item | Assessment |
|---|---|
| Target tables identified from the approved physical model | YES |
| Company ownership path for operational data | YES |
| `work_date` stored apart from punch timestamp | YES |
| Cross-midnight shift window | YES |
| Immutable punches and versioned attendance | YES |
| Correction stages original, requested, approved, final | YES |
| Generic ordered approval without boss columns | YES |
| Calculated, payable, and paid OT in separate stores | YES |
| Finalized payroll result immutable and self-contained | YES |
| Policy mechanism with effective dates and precedence | YES |
| D01–D18 | OPEN. BD-D15 DEFERRED |
| DDL, migrations, and implementation | NOT STARTED |

The specification is ready to guide a later implementation plan. It is not that implementation.

### Verification

| Check | Result |
|---|---|
| SQL executed | No |
| Migration created | No |
| Database changed | No |
| API, UI, or framework implemented | No |
| Business policy selected | No |
| Operational data has a company path | Yes. Section 6 |
| `work_date` separate from punch timestamp | Yes. Sections 4.13 and 8 |
| Cross-midnight supported | Yes. Sections 4.7 and 8 |
| Raw punches immutable | Yes. Section 4.13 |
| Attendance versions preserved | Yes. Sections 4.14 through 4.17 |
| Correction stages preserved | Yes. Section 4.19 |
| Calculated, payable, and paid OT separate | Yes. Section 11 |
| Finalized payroll immutable | Yes. Section 12 |
| Policy configurable | Yes. Section 13 |
| Effective dating supported | Yes. Section 7 |
| Deterministic precedence supported | Yes. Sections 7 and 13 |
| D01–D18 remain open | Yes. Section 18 |

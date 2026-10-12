# Step 20D Employee Lifecycle Integrity Review

Date: 2026-10-10

Read-only. No migration, schema object, configuration, or row was changed. Legacy MySQL was not opened. No probe rows were inserted.

## 1. Documents reviewed

- `docs/UNIVERSAL_HRMS_STEP20B_EMPLOYEE_LIFECYCLE_DESIGN_REVIEW.md`
- `docs/UNIVERSAL_HRMS_STEP20C_EMPLOYEE_LIFECYCLE_IMPLEMENTATION_REPORT.md`
- `docs/UNIVERSAL_HRMS_STEP20A_FOUNDATION_IMPLEMENTATION_REPORT.md`
- `docs/UNIVERSAL_HRMS_STEP20A_FOUNDATION_TIMESTAMP_DECISION.md`
- `docs/UNIVERSAL_HRMS_STEP20_PHYSICAL_IMPLEMENTATION_DECISIONS.md`
- `docs/UNIVERSAL_HRMS_STEP20_FINAL_EMPLOYEE_CODE_DECISION.md`
- `docs/UNIVERSAL_HRMS_TARGET_DATABASE_SCHEMA_SPECIFICATION.md` sections 4.3, 4.4, and 7
- `docs/UNIVERSAL_HRMS_MIGRATION_DATA_CONTINUITY_ARCHITECTURE.md`
- `docs/UNIVERSAL_HRMS_MULTI_COMPANY_MIGRATION_MAPPING_WORKBOOK.md` sections 4 and 5

Catalog evidence is from `proedge_new_hrms` on PostgreSQL 18.6, port 5432. Queries read `information_schema.columns`, `pg_constraint`, `pg_indexes`, `pg_class`, and `public.migrations`.

## 2. Actual schema verification results

`employees` columns, in order, with no column default:

| Column | Type | Null |
| --- | --- | --- |
| `id` | `text` | not null |
| `company_id` | `text` | not null |
| `employee_code` | `text` | not null |
| `display_name` | `text` | not null |
| `source_system` | `text` | null |
| `source_key` | `text` | null |
| `created_at` | `timestamptz` | not null |
| `updated_at` | `timestamptz` | not null |

`employees.status` is absent. The column search returned 0.

`employment_episodes` columns, in order, with no column default:

| Column | Type | Null |
| --- | --- | --- |
| `id` | `text` | not null |
| `company_id` | `text` | not null |
| `employee_id` | `text` | not null |
| `joining_date` | `date` | not null |
| `last_working_date` | `date` | null |
| `episode_status` | `text` | not null |
| `source_system` | `text` | null |
| `source_key` | `text` | null |
| `created_at` | `timestamptz` | not null |
| `updated_at` | `timestamptz` | not null |

Keys and checks on these two tables:

- Primary keys on `id`
- ULID check `id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'`
- `UNIQUE (company_id, employee_code)` with no status predicate
- `UNIQUE (company_id, id)` on `employees`
- `employees.company_id` references `companies(id)`
- `employment_episodes.company_id` references `companies(id)`
- `employment_episodes (company_id, employee_id)` references `employees (company_id, id)`
- `episode_status` is `Open`, `inactive`, or `separated`
- `last_working_date` is null while `episode_status` is `Open`
- Unique index `employment_episodes_one_open_per_employee` on `employee_id` where `episode_status = 'Open'`

This catalog matches the Step 20B approved schema and the Step 20C report. No extra employee or episode index is present.

Migration history:

| id | migration | batch |
| --- | --- | --- |
| 1 | `2026_10_10_100001_create_companies_table` | 1 |
| 2 | `2026_10_10_100002_create_branches_table` | 1 |
| 3 | `2026_10_10_100003_create_location_nodes_table` | 1 |
| 4 | `2026_10_10_100004_create_organization_nodes_table` | 1 |
| 5 | `2026_10_10_100005_create_employees_table` | 2 |
| 6 | `2026_10_10_100006_create_employment_episodes_table` | 3 |

Public relations are those six tables, `migrations`, and `migrations_id_seq`.

## 3. Approved-rule enforcement matrix

| Rule | Class | Evidence |
| --- | --- | --- |
| Required `employee_code` and `display_name` | PASS | Both columns are `NOT NULL` |
| `UNIQUE (company_id, employee_code)` for every row, including separated employees | PASS | Unique index has no `WHERE` clause and does not read `episode_status` |
| Episode company matches the employee company | PASS | Composite foreign key `employment_episodes_company_employee_fkey` |
| At most one `Open` episode per employee | PASS | Partial unique index on `employee_id` where status is `Open` |
| ULID text and alphabet on both primary keys | PASS | Check constraint on each `id` |
| `created_at` and `updated_at` are `timestamptz` | PASS | `udt_name = timestamptz`. No default |
| No `employees.status` | PASS | Column absent |
| Separation does not release the code | PASS | The code unique index is not limited to open episodes |
| Rejoin keeps one identity and adds an episode | PASS | The schema allows another episode after the open one is no longer `Open`. It does not create a second employee for that action |
| No day is processed before `joining_date`, and none is invented after `last_working_date` | PASS | Step 20B records these as processing rules, not columns. Attendance and payroll tables are not in this phase |
| Identities are not merged | PASS | Two rows in one company cannot share `employee_code`. Merging remains a forbidden migration action, not an extra constraint |
| Date-range overlap of episodes | UNRESOLVED | Not in schema section 7. Not implemented. Not a missing approved constraint |
| `last_working_date` on or after `joining_date` | UNRESOLVED | No approved order check |
| Pay effect of `inactive` | UNRESOLVED | No column. D06/D07 remain open |
| Whether `inactive` may store `last_working_date` | UNRESOLVED | The empty-date check applies only to `Open` |
| Trigger that freezes `joining_date` | PASS | Step 20B marked that trigger inferred and did not approve it. It is absent |

## 4. Foundation-table preservation check

`companies`, `branches`, `location_nodes`, and `organization_nodes` still have the column names, types, and nullability from the Step 20A report. Their indexes are unchanged: company and branch partial code indexes, `branches` and `organization_nodes` unique `(company_id, id)`, and the primary keys. Their migration rows are still batch 1. This review did not alter those tables.

## 5. Legacy migration-readiness findings

No source database was queried. No reconciliation was run. No company is migration-approved by this review.

| Topic | Class | Finding |
| --- | --- | --- |
| Stable target identity | PASS | The plan is a new ULID `id`. The legacy employee id is `source_system` plus `source_key`. Workbook section 5 |
| Company of the identity | PASS | Target tenant is catalog company 24, 20, or 10. A salary `company_id` of 5 or 26 is not the tenant. Workbook section 4 |
| Duplicate employee codes | PASS as a rule. NOT VERIFIED as a completed exception list | Duplicates stay exceptions. They are not merged and the code is not reassigned. This review did not count them |
| Empty display name | PASS as a rule. NOT VERIFIED as a count | Workbook section 17 treats an empty name as an exception. No count was taken here |
| Missing joining date | PASS as a rule. NOT VERIFIED as a count | A loadable row with no joining date is an exception. The date is not invented |
| Conflicting active and discontinued flags | PASS as a rule. NOT VERIFIED as a full census | Status is set only when the pair is unambiguous. Atlas code EB9091 is the documented case: both flags, `discontinue_date` 2026-08-25, and later attendance. Proedge and VTL conflict counts are not published in the workbook |
| One episode for the known joining span | PASS as a rule | A single legacy employee row is not a full history. Missing spans stay missing |
| Attendance after `last_working_date` | PASS as a rule | The workbook says the historical day is kept and listed, not deleted |
| Company-wise source-to-target reconciliation | NOT VERIFIED | The mapping rules exist. This review did not compare source counts with target rows |

Workbook section 4 still says “active code uniqueness” in one employee row. Section 5 and the employee-code decision close the rule as `UNIQUE (company_id, employee_code)` on every row. The catalog matches that closed rule.

## 6. Unresolved decisions and blockers

No new mandatory schema blocker was found.

These remain unresolved and were correctly left out of the tables:

- Episode date-range overlap beyond one `Open` episode
- Date-order validation of `last_working_date` against `joining_date`
- Pay effect of `inactive`

Data migration itself is not ready to run. The exception lists and company reconciliations have not been produced. That is separate from the physical schema.

## 7. Recommended next step

Keep these two tables as they are. Do not add the unresolved checks. When a later instruction asks for the next table, that table is `employment_assignments`, not attendance, leave, overtime, payroll, users, or approvals.

REVIEW PASSED

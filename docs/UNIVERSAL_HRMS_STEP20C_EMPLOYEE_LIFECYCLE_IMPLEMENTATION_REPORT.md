# Step 20C Employee Lifecycle Implementation Report

Date: 2026-10-10

Database: `proedge_new_hrms` on PostgreSQL 18.6 at `127.0.0.1:5432`, driver `pgsql`.

## Approved sources reviewed

- `docs/UNIVERSAL_HRMS_STEP20B_EMPLOYEE_LIFECYCLE_DESIGN_REVIEW.md`
- `docs/UNIVERSAL_HRMS_STEP20A_FOUNDATION_IMPLEMENTATION_REPORT.md`
- `docs/UNIVERSAL_HRMS_STEP20A_FOUNDATION_TIMESTAMP_DECISION.md`
- `docs/UNIVERSAL_HRMS_STEP20_PHYSICAL_IMPLEMENTATION_DECISIONS.md`
- `docs/UNIVERSAL_HRMS_STEP20_FINAL_EMPLOYEE_CODE_DECISION.md`
- `docs/UNIVERSAL_HRMS_TARGET_DATABASE_SCHEMA_SPECIFICATION.md` sections 4.3 and 4.4
- `docs/UNIVERSAL_HRMS_MIGRATION_DATA_CONTINUITY_ARCHITECTURE.md`

Only **APPROVED** columns and constraints from the Step 20B review were implemented. No **INFERRED** trigger and no **UNDECIDED** check was added.

## Migration filenames

- `database/migrations/2026_10_10_100005_create_employees_table.php`
- `database/migrations/2026_10_10_100006_create_employment_episodes_table.php`

Commands, after `migrate --pretend` matched the approved SQL:

```text
php artisan migrate --no-interaction --path=database/migrations/2026_10_10_100005_create_employees_table.php
php artisan migrate --no-interaction --path=database/migrations/2026_10_10_100006_create_employment_episodes_table.php
```

Both reported DONE. The four foundation migration files were not edited. `.env`, packages, and installed software were not changed. Legacy MySQL was not opened.

## Implemented columns, keys, and constraints

`employees`: `id text` primary key, `company_id text` not null, `employee_code text` not null, `display_name text` not null, `source_system text` null, `source_key text` null, `created_at timestamptz` not null, `updated_at timestamptz` not null.

There is no `employees.status` column.

Constraints: ULID check on `id`, `UNIQUE (company_id, id)`, `UNIQUE (company_id, employee_code)` with no status predicate, foreign key `company_id` to `companies(id)`.

`employment_episodes`: `id text` primary key, `company_id text` not null, `employee_id text` not null, `joining_date date` not null, `last_working_date date` null, `episode_status text` not null, `source_system text` null, `source_key text` null, `created_at timestamptz` not null, `updated_at timestamptz` not null.

Constraints: ULID check on `id`; `episode_status` in `Open`, `inactive`, `separated`; `last_working_date` is null while status is `Open`; foreign key `company_id` to `companies(id)`; foreign key `(company_id, employee_id)` to `employees(company_id, id)`; unique index on `employee_id` where `episode_status = 'Open'`.

## Validation queries and results

`current_database()` returned `proedge_new_hrms`. `current_setting('server_version')` returned `18.6`.

`public.migrations`:

| id | migration | batch |
| --- | --- | --- |
| 1 | `2026_10_10_100001_create_companies_table` | 1 |
| 2 | `2026_10_10_100002_create_branches_table` | 1 |
| 3 | `2026_10_10_100003_create_location_nodes_table` | 1 |
| 4 | `2026_10_10_100004_create_organization_nodes_table` | 1 |
| 5 | `2026_10_10_100005_create_employees_table` | 2 |
| 6 | `2026_10_10_100006_create_employment_episodes_table` | 3 |

Public relations are the four foundation tables, `employees`, `employment_episodes`, `migrations`, and `migrations_id_seq`. No other application table was created.

Column and constraint metadata came from `information_schema.columns`, `pg_constraint`, and `pg_indexes`. Types match the lists above. Foundation constraint definitions match the Step 20A catalog: the same primary keys, ULID checks, partial code indexes, and composite foreign keys.

A transaction inserted probe rows and then rolled back. Counts before and after were 0 for `companies`, `branches`, `location_nodes`, `organization_nodes`, `employees`, and `employment_episodes`.

| Probe | Result |
| --- | --- |
| Insert employee | Accepted |
| Same `employee_code` in the same company | Rejected, SQLSTATE 23505 |
| Same `employee_code` in another company | Accepted |
| Invalid ULID | Rejected, SQLSTATE 23514 |
| First `Open` episode | Accepted |
| Second `Open` episode for that employee | Rejected, SQLSTATE 23505 |
| `Open` episode with `last_working_date` | Rejected, SQLSTATE 23514 |
| Status token `active` | Rejected, SQLSTATE 23514 |
| `separated` episode with no `last_working_date` | Accepted |
| New `Open` episode after the first episode was set to `separated` | Accepted |
| Episode whose `company_id` does not match the employee | Rejected, SQLSTATE 23503, constraint `employment_episodes_company_employee_fkey` |

One earlier cross-company insert used status `Open` while that employee already had an `Open` episode, so it was rejected by the open-episode index. The repeated probe above, using `separated`, confirmed the composite foreign key.

## Foundation-table preservation

`companies`, `branches`, `location_nodes`, and `organization_nodes` still have the columns, keys, checks, and indexes recorded in the Step 20A report. Their migration rows remain batch 1. Their migration files were not modified. No row was left in them.

## Unresolved decisions intentionally excluded

- Pay effect of `inactive`
- Whether `inactive` may store `last_working_date`
- Whether `last_working_date` must be on or after `joining_date`
- Whether episode date spans must be non-overlapping beyond one `Open` episode
- A trigger that freezes `joining_date`

## Deviations

None. The two new migrations were executed as two commands, so they are batches 2 and 3. That is migration history, not a schema change.

## Next step

Do not start attendance, leave, overtime, payroll, users, roles, or approvals. The next schema candidate, when instructed, is `employment_assignments`.

IMPLEMENTATION COMPLETE

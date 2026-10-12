# Universal HRMS — Step 20O Company Seed Integrity Review

Read-only review of the Step 20N company seed. No row was inserted, updated, or deleted. No company is employee-migration-approved.

## 1. Documents and sources reviewed

- `docs/UNIVERSAL_HRMS_STEP20N_COMPANY_SEED_IMPLEMENTATION_REPORT.md` in both project doc folders. Both copies name the same current ULIDs and the same discarded ids.
- `docs/UNIVERSAL_HRMS_STEP20M_COMPANY_SEED_AND_TENANT_MAPPING_DESIGN.md`
- `docs/UNIVERSAL_HRMS_STEP20K_EMPLOYEE_MIGRATION_APPROVAL_AND_SOURCE_MANIFEST.md` approval fields
- `next-hrms/database/migrations/2026_10_10_100001_create_companies_table.php` through `2026_10_10_100006_create_employment_episodes_table.php`
- `next-hrms/database/seeders/DatabaseSeeder.php`
- `next-hrms/storage/logs/laravel.log`
- One read-only transaction on local `proedge_new_hrms` at `127.0.0.1:5432`. `current_setting('transaction_read_only')` was `on`.

No conflicting Step 20N copy was found. Legacy MySQL and production were not opened. PostgreSQL WAL was not read.

## 2. Read-only catalog validation results

The live `companies` table has exactly three rows.

| `source_key` | Company name | `id` | Status | `company_code` | `source_system` | `created_at` | `updated_at` |
| --- | --- | --- | --- | --- | --- | --- | --- |
| `24` | Pro-edge Associates Limited | `01M4KG2364VXPYVZCM7HZTGPB1` | `Active` | null | null | `2026-10-11 00:08:31.999137+06` | `2026-10-11 00:09:02.843441+06` |
| `20` | Viyellatex Group | `01M4KG2384JEKYAE5SXC927QDF` | `Active` | null | null | `2026-10-11 00:08:31.999137+06` | `2026-10-11 00:09:02.843441+06` |
| `10` | Atlas Copco Bangladesh Ltd. | `01M4KG238KHQSQTQVFAA0YAYVG` | `Active` | null | null | `2026-10-11 00:08:31.999137+06` | `2026-10-11 00:09:02.843441+06` |

`pg_typeof` reports `timestamp with time zone` for both timestamp columns. `updated_at` is after `created_at` on every row. The clock values match the Step 20N insert and the later id correction. Each `source_key` appears once. Rows outside `24`, `20`, and `10` are 0. Rows with `source_key` `5` or `26` are 0.

## 3. Temporary ULID correction assessment

Step 20N records that these ids were inserted and then replaced:

| Discarded id | Replaced by | Catalog |
| --- | --- | --- |
| `01M4KG14X50000000000000000` | `01M4KG2364VXPYVZCM7HZTGPB1` | 24 |
| `01M4KG15450000000000000000` | `01M4KG2384JEKYAE5SXC927QDF` | 20 |
| `01M4KG154M0000000000000000` | `01M4KG238KHQSQTQVFAA0YAYVG` | 10 |

Current public tables are `companies`, `branches`, `location_nodes`, `organization_nodes`, `employees`, `employment_episodes`, and `migrations`. Counts of the discarded ids in `companies.id`, branch and location and organization company keys, `employees`, `employment_episodes`, and `migrations.migration` are all 0. `branches`, `location_nodes`, `organization_nodes`, `employees`, and `employment_episodes` each have 0 rows, so they cannot point at either the discarded ids or the current ids.

File inspection found the discarded ids only in the two Step 20N reports, where they are named as discarded. They are absent from `DatabaseSeeder.php` and from `laravel.log`. No seed file stores the current company ULIDs.

The original insert is not retained as a row image. WAL was not read, so this review cannot replay that insert independently of Step 20N. Current tables, the seeder, and the Laravel log contain no remaining reference to the discarded ids.

`created_at` is the same on all three rows and matches the Step 20N insert time. `updated_at` is the same on all three rows and matches the Step 20N correction time. The correction is consistent across the three catalog keys.

## 4. Tenant-boundary findings

Live foreign keys, from `pg_constraint` in this session:

| Table | Constraint | Definition |
| --- | --- | --- |
| `branches` | `branches_company_id_fkey` | `company_id` references `companies(id)` |
| `location_nodes` | `location_nodes_company_id_fkey` | `company_id` references `companies(id)` |
| `location_nodes` | `location_nodes_company_branch_fkey` | `(company_id, branch_id)` references `branches(company_id, id)` |
| `organization_nodes` | `organization_nodes_company_id_fkey` | `company_id` references `companies(id)` |
| `organization_nodes` | `organization_nodes_parent_fkey` | `(company_id, parent_id)` references `organization_nodes(company_id, id)` |
| `employees` | `employees_company_id_fkey` | `company_id` references `companies(id)` |
| `employment_episodes` | `employment_episodes_company_id_fkey` | `company_id` references `companies(id)` |
| `employment_episodes` | `employment_episodes_company_employee_fkey` | `(company_id, employee_id)` references `employees(company_id, id)` |

The migration files also define `UNIQUE (company_id, id)` on `branches`, `organization_nodes`, and `employees`, and `UNIQUE (company_id, employee_code)` on `employees`. `location_nodes` has the company and branch foreign keys above and has no `UNIQUE (company_id, id)`. The one-open episode index in the migration is on `employee_id` where `episode_status = 'Open'`.

These keys cover the foundation and employee tables that exist now. Attendance, leave, overtime, payroll, and later assignment tables are not in this database. Their tenant safety is not established by the three company rows.

## 5. Employee and episode count confirmation

| Table | Rows |
| --- | ---: |
| `employees` | 0 |
| `employment_episodes` | 0 |

Step 20K still leaves the approval choice, approver, and approval date blank for D1, D2, D3, D4, and D6. No later document records an explicit approval. Those five decisions stay **PROPOSED**. An `Active` company row does not approve an employee load. Proedge, Viyellatex, and Atlas are not employee-migration-approved.

## 6. Remaining blockers

- D1, D2, D3, D4, and D6 are unapproved.
- The exception register is still a template. Approved-exception count is 0.
- Overlap, date order, inactive pay, and the meaning of `dis` stay unresolved.
- `companies.source_system` is null, as the seed required.
- The three company ULIDs exist. That prerequisite no longer blocks a later classification. It does not authorize one.
- Historical proof of the discarded ids rests on Step 20N. Current objects do not contain them. WAL was not read.

## 7. Recommended next step

Leave the company rows unchanged. The next employee step is still an explicit approval or revision of D1, D2, D3, D4, and D6. Do not load employees and do not run the classification dry run from this review.

## 8. Execution statement

The database session was read-only on local `proedge_new_hrms`. No data, schema, migration, configuration, or application file was changed.

INTEGRITY REVIEW PASSED

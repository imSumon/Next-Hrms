# Universal HRMS — Step 20N Company Seed Implementation Report

Local company seed only. Employee rows were not loaded. D1, D2, D3, D4, and D6 were not approved. No company is migration-approved.

## 1. Documents reviewed

Copies in `legacy-reference\hrms\docs` and `D:\Project -All\Proedge HRMS\docs` were the same texts used for the design. The physical table definition was read from `next-hrms/database/migrations/2026_10_10_100001_create_companies_table.php` and was not modified.

- `docs/UNIVERSAL_HRMS_STEP20M_COMPANY_SEED_AND_TENANT_MAPPING_DESIGN.md`
- `docs/UNIVERSAL_HRMS_STEP20L_TARGET_COMPANY_ULID_AND_APPROVAL_VERIFICATION.md`
- `docs/UNIVERSAL_HRMS_STEP20A_FOUNDATION_IMPLEMENTATION_REPORT.md`
- `docs/UNIVERSAL_HRMS_TARGET_DATABASE_SCHEMA_SPECIFICATION.md` sections 4.1 and 5, as cited by Step 20M
- `docs/UNIVERSAL_HRMS_MULTI_COMPANY_MIGRATION_MAPPING_WORKBOOK.md` company crosswalk, as cited by Step 20M

The request names the three companies Proedge, Viyellatex, and Atlas and sets status `Active`. Step 20M stores the catalog legal names. This seed used those legal names.

## 2. Actual database and schema verified

Read-only preflight, then the seed session, both on local PostgreSQL:

| Check | Result |
| --- | --- |
| `current_database()` | `proedge_new_hrms` |
| `inet_server_addr()` | `127.0.0.1/32` |
| Port | `5432` |
| Legacy MySQL or production | Not opened |

`companies` columns, with no defaults: `id text NOT NULL`, `company_code text NULL`, `company_name text NOT NULL`, `status text NOT NULL`, `source_system text NULL`, `source_key text NULL`, `created_at timestamptz NOT NULL`, `updated_at timestamptz NOT NULL`.

Constraints present: primary key on `id`, ULID check `id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'`, status check `Active` or `withdrawn`. Unique index `companies_company_code_unique` applies only where `company_code` is not null. No unique constraint exists on `source_key`. The seed procedure checked duplicates itself. The schema was not changed.

## 3. Preflight results

| Check | Before the seed |
| --- | --- |
| Rows in `companies` | 0 |
| `source_key` `24`, `20`, `10`, `5`, or `26` | 0 |
| Catalog legal names already stored | 0 |
| `employees` | 0 |
| `employment_episodes` | 0 |

The live schema and the empty table matched Step 20M. The seed was allowed to continue.

## 4. Seed operation results

One transaction locked `companies`, rejected `source_key` `5` or `26`, rejected a catalog name owned by a different key, rejected a duplicate catalog key, and rejected an existing catalog row whose name, status, null code, or null `source_system` differed from this seed. It then inserted only missing keys.

The first insert committed three rows. Their random ULID sections were `0000000000000000` because the random-byte call failed and the zeroed buffer was still encoded. No branch, location, organization, employee, or episode row referenced those ids. A second transaction, in this same step, replaced only those three `id` values with ULIDs that have a non-zero random section, and only where `source_key` and the defective `id` still matched. `company_name`, `status`, `source_key`, `company_code`, `source_system`, and `created_at` were left as inserted. `updated_at` is the correction time.

| Catalog `source_key` | Company name stored | Action |
| --- | --- | --- |
| `24` | Pro-edge Associates Limited | Inserted, then `id` corrected |
| `20` | Viyellatex Group | Inserted, then `id` corrected |
| `10` | Atlas Copco Bangladesh Ltd. | Inserted, then `id` corrected |

`company_code` and `source_system` are null. Status is `Active`. Dump-local ids 5 and 26 were not written.

## 5. Target ULIDs and catalog mappings

| Catalog id | Company | `companies.id` | `source_key` |
| ---: | --- | --- | --- |
| 24 | Pro-edge Associates Limited | `01M4KG2364VXPYVZCM7HZTGPB1` | `24` |
| 20 | Viyellatex Group | `01M4KG2384JEKYAE5SXC927QDF` | `20` |
| 10 | Atlas Copco Bangladesh Ltd. | `01M4KG238KHQSQTQVFAA0YAYVG` | `10` |

`created_at` on all three rows is `2026-10-11 00:08:31.999137+06`. `updated_at` on all three rows is `2026-10-11 00:09:02.843441+06`.

The discarded zero-random ids were `01M4KG14X50000000000000000`, `01M4KG15450000000000000000`, and `01M4KG154M0000000000000000`. They are not stored.

## 6. Validation results

A later read-only session on `127.0.0.1:5432`, database `proedge_new_hrms`, returned:

| Check | Result |
| --- | --- |
| One row each for `source_key` `24`, `20`, and `10` | Pass |
| Names Pro-edge Associates Limited, Viyellatex Group, Atlas Copco Bangladesh Ltd. | Pass |
| Each `id` matches the ULID check, and the last 16 characters are not all zeros | Pass |
| `status` = `Active` | Pass |
| `company_code` and `source_system` are null | Pass |
| Duplicate catalog keys | 0 |
| Other company rows | 0 |
| `source_key` `5` or `26` | 0 |
| `employees` and `employment_episodes` | 0 and 0 |
| `branches`, `location_nodes`, `organization_nodes` | 0, 0, and 0 at the correction check |

## 7. Conflict or deviation

The stored names are the Step 20M catalog names. The request’s short labels Proedge, Viyellatex, and Atlas were not written into `company_name`.

`updated_at` is later than `created_at` because the zero-random ids were replaced before this step closed. No dependent row pointed at the discarded ids.

No schema object, migration file, `.env`, or application file was changed.

## 8. Employee data

No employee row and no employment episode was inserted. The employee migration dry run was not executed. D1, D2, D3, D4, and D6 remain proposed. Proedge, Viyellatex, and Atlas are not migration-approved. These three company rows are tenant records only.

COMPANY SEED COMPLETE

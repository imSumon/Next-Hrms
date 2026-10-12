# Step 20A Foundation Implementation Report

Validation date: 2026-10-10. This pass was read-only. No migration, schema, data, configuration, or environment change was made. Credentials were not printed. Legacy MySQL was not opened.

## 1. Environment

| Item | Evidence |
| --- | --- |
| Project | `D:\Project -All\Proedge HRMS\next-hrms` |
| `.env` driver | `DB_CONNECTION=pgsql` |
| Host and port | `DB_HOST=127.0.0.1`, `DB_PORT=5432` |
| Database name in `.env` | `DB_DATABASE=proedge_new_hrms` |
| Username | `postgres` |
| Password | Present in `.env`. Value not recorded |
| Connected database | `current_database() = proedge_new_hrms` |
| Server | PostgreSQL 18.6 |
| Server address | `127.0.0.1/32`, port `5432` |

Query:

```sql
SELECT current_database(), current_setting('server_version'), inet_server_addr(), inet_server_port();
```

## 2. Target Database

`proedge_new_hrms` on local PostgreSQL 18.6. The session used the `pgsql` driver only.

## 3. Tables Created

Public relations:

| Name | Kind |
| --- | --- |
| `companies` | table |
| `branches` | table |
| `location_nodes` | table |
| `organization_nodes` | table |
| `migrations` | table |
| `migrations_id_seq` | sequence for `migrations.id` |

Application tables from this phase: `companies`, `branches`, `location_nodes`, `organization_nodes`. `migrations` and `migrations_id_seq` are Laravel migration metadata. No other relation exists outside `pg_catalog` and `information_schema`.

## 4. Columns and Data Types

`companies`: `id text` not null, `company_code text` null, `company_name text` not null, `status text` not null, `source_system text` null, `source_key text` null, `created_at timestamptz` not null, `updated_at timestamptz` not null.

`branches`: `id text` not null, `company_id text` not null, `code text` null, `name text` not null, `status text` not null, `source_system text` null, `source_key text` null, `created_at timestamptz` not null, `updated_at timestamptz` not null.

`location_nodes`: the branch columns, plus required `branch_id text`. No `parent_id`. No unit, floor, or line column.

`organization_nodes`: `id text` not null, `company_id text` not null, `parent_id text` null, `source_system text` null, `source_key text` null, `created_at timestamptz` not null, `updated_at timestamptz` not null. No name, code, status, or node-type column.

`migrations`: `id int4` not null, `migration varchar` not null, `batch int4` not null.

## 5. Constraints

Primary keys: `companies_pkey (id)`, `branches_pkey (id)`, `location_nodes_pkey (id)`, `organization_nodes_pkey (id)`, `migrations_pkey (id)`.

ULID checks, identical on each foundation `id`:

```text
CHECK ((id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'::text))
```

Status checks on `companies`, `branches`, and `location_nodes`:

```text
CHECK ((status = ANY (ARRAY['Active'::text, 'withdrawn'::text])))
```

PostgreSQL stores the approved `IN ('Active', 'withdrawn')` predicate in that form. PostgreSQL 18 also lists each `NOT NULL` column as a constraint. Those entries match the column nullability above.

Company-scoped uniqueness is enforced by partial unique indexes in section 7. `branches` and `organization_nodes` also have `UNIQUE (company_id, id)`.

## 6. Foreign Keys

| Constraint | Definition |
| --- | --- |
| `branches_company_id_fkey` | `FOREIGN KEY (company_id) REFERENCES companies(id)` |
| `location_nodes_company_id_fkey` | `FOREIGN KEY (company_id) REFERENCES companies(id)` |
| `location_nodes_company_branch_fkey` | `FOREIGN KEY (company_id, branch_id) REFERENCES branches(company_id, id)` |
| `organization_nodes_company_id_fkey` | `FOREIGN KEY (company_id) REFERENCES companies(id)` |
| `organization_nodes_parent_fkey` | `FOREIGN KEY (company_id, parent_id) REFERENCES organization_nodes(company_id, id)` |

`location_nodes` has no self-parent. Its company boundary is the composite branch key. `organization_nodes.parent_id` is nullable, and a parent in another company fails the composite key. No path or closure object exists.

## 7. Indexes

| Index | Definition |
| --- | --- |
| `companies_pkey` | unique btree (`id`) |
| `companies_company_code_unique` | unique btree (`company_code`) WHERE `company_code IS NOT NULL` |
| `branches_pkey` | unique btree (`id`) |
| `branches_company_id_id_unique` | unique btree (`company_id`, `id`) |
| `branches_company_code_unique` | unique btree (`company_id`, `code`) WHERE `code IS NOT NULL` |
| `location_nodes_pkey` | unique btree (`id`) |
| `location_nodes_company_code_unique` | unique btree (`company_id`, `code`) WHERE `code IS NOT NULL` |
| `organization_nodes_pkey` | unique btree (`id`) |
| `organization_nodes_company_id_id_unique` | unique btree (`company_id`, `id`) |
| `migrations_pkey` | unique btree (`id`) |

No other public index exists. No partition is present.

## 8. Migration Results

`public.migrations`, batch 1:

| id | migration |
| --- | --- |
| 1 | `2026_10_10_100001_create_companies_table` |
| 2 | `2026_10_10_100002_create_branches_table` |
| 3 | `2026_10_10_100003_create_location_nodes_table` |
| 4 | `2026_10_10_100004_create_organization_nodes_table` |

This validation did not run `php artisan migrate`. The rows above are the recorded history.

## 9. Validation Results

| Check | Result |
| --- | --- |
| Active database `proedge_new_hrms`, driver `pgsql` | Pass |
| Four foundation migrations recorded | Pass |
| Only those four application tables, plus Laravel `migrations` metadata | Pass |
| Columns and types | Pass |
| Primary keys and ULID checks | Pass |
| Company-scoped code uniqueness | Pass |
| Branch-to-company foreign key | Pass |
| Location company and branch composite key; no location self-parent | Pass |
| Organization company key and same-company parent key | Pass |
| `created_at` and `updated_at` are `timestamptz` | Pass |
| Indexes limited to the approved keys | Pass |

Catalog queries used: `pg_class` and `pg_namespace` for relations; `information_schema.columns` for types; `pg_constraint` for keys, checks, and foreign keys; `pg_indexes` for indexes; `public.migrations` for history.

## 10. Legacy Safety Verification

This validation opened one PostgreSQL session to `127.0.0.1:5432`, database `proedge_new_hrms`. It issued `SELECT` catalog queries only. Legacy MySQL was not accessed. No production connection was made. No data was imported. No table was dropped, truncated, or altered.

## 11. Deviations From Approved Specification

None. The `ANY (ARRAY[...])` status text and the PostgreSQL 18 `NOT NULL` constraint entries are the server's catalog form of the approved definitions.

## 12. Step 20B Readiness

The four foundation tables are present and match the approved foundation shape. Step 20B was not started.

STEP 20A COMPLETE

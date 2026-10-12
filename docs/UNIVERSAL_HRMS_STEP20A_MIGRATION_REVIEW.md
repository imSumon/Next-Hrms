# Step 20A Migration Review

Review only. The four migration files were not edited. No database connection, migration, installation, or cleanup was performed.

Updated 2026-10-10 after `docs/UNIVERSAL_HRMS_STEP20A_FOUNDATION_TIMESTAMP_DECISION.md`. That document is a new decision. It does not change the original schema text.

Files:

- `D:\Project -All\Proedge HRMS\next-hrms\database\migrations\2026_10_10_100001_create_companies_table.php`
- `D:\Project -All\Proedge HRMS\next-hrms\database\migrations\2026_10_10_100002_create_branches_table.php`
- `D:\Project -All\Proedge HRMS\next-hrms\database\migrations\2026_10_10_100003_create_location_nodes_table.php`
- `D:\Project -All\Proedge HRMS\next-hrms\database\migrations\2026_10_10_100004_create_organization_nodes_table.php`

Sources: `docs/UNIVERSAL_HRMS_TARGET_DATABASE_SCHEMA_SPECIFICATION.md` sections 4, 5, and 6; `docs/UNIVERSAL_HRMS_STEP20_PHYSICAL_IMPLEMENTATION_DECISIONS.md` sections 2, 6, 7, and 12; `docs/UNIVERSAL_HRMS_STEP20_FINAL_DOMAIN_DECISIONS.md` sections 6 and 8; `docs/UNIVERSAL_HRMS_STEP20A_FOUNDATION_TIMESTAMP_DECISION.md`.

## ULID storage and alphabet

Passes. Each `id` is PostgreSQL `text` with check `id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'`.

That class is exactly 26 characters from `0123456789ABCDEFGHJKMNPQRSTVWXYZ`. `I`, `L`, `O`, and `U` are excluded. The type is not `character(26)`, `uuid`, `bigint`, or `bytea`.

Source: Step 20 physical decision section 2. The check applies to primary keys. `company_id`, `branch_id`, and `parent_id` reference those primary keys.

## Fields

`companies` has `id`, optional `company_code`, required `company_name`, required `status`, optional `source_system`, optional `source_key`, `created_at`, and `updated_at`. Status values are `Active` and `withdrawn`.

`branches` and `location_nodes` have `company_id`, optional `code`, required `name`, required `status`, the same source columns, and both timestamps. `location_nodes.branch_id` is required. Unit, floor, and line are absent.

`organization_nodes` has `id`, `company_id`, nullable `parent_id`, the source columns, and both timestamps. It has no name, code, status, or node-type column.

`source_system` and `source_key` are the optional source identifier on every table. Schema section 5. `updated_at` is present because these tables are not specified as insert-only. Schema section 4: `created_at` is required, and insert-only tables have no `updated_at`. Company, branch, and location name or status changes are audited. Schema sections 4.1 and 4.2.

`parent_id` is the organization-node parent column. The domain decision says that name is fixed when the table is created, and the composite-key decision names the child column `parent_id`.

String columns use `text`. No length was approved, and no length was added.

## Company-scoped uniqueness

Passes.

- `companies_company_code_unique` is unique on `company_code` where it is present. Schema section 4.1.
- `branches_company_code_unique` and `location_nodes_company_code_unique` are unique on (`company_id`, `code`) where `code` is present. Schema section 4.2.
- Step 20 physical decision section 12 names those partial unique indexes.

`organization_nodes` has no `code`, so it has no code index.

## Composite foreign keys and relationships

Passes.

- `branches.company_id` references `companies.id`. `companies` is the tenant root, so this reference is not a composite pair.
- `branches` has unique (`company_id`, `id`).
- `location_nodes.company_id` references `companies.id`.
- `location_nodes (company_id, branch_id)` references `branches (company_id, id)`.
- `organization_nodes.company_id` references `companies.id`.
- `organization_nodes` has unique (`company_id`, `id`).
- `organization_nodes (company_id, parent_id)` references `organization_nodes (company_id, id)`.

Source: schema sections 4.2 and 6, and Step 20 physical decision section 7. A nullable `parent_id` uses PostgreSQL `MATCH SIMPLE`, so a root row has no parent. A parent in another company fails the composite key.

`location_nodes` has no `parent_id`. The approved location hierarchy is `branch_id`, not a self-parent. Domain decision section 6. Company boundary for a location node is the composite branch key.

`organization_nodes` has no path string and no closure table. Domain decision section 6. No cycle trigger was added. None is specified.

No employment, approval, user, or audit foreign key is present.

## Indexes

Passes. The indexes are the primary keys, the partial code indexes, and the unique (`company_id`, `id`) keys required by the composite foreign keys. Schema section 16 does not add a lookup index for these four tables. No partition is declared.

## Order and PostgreSQL 18

Passes. File order is `companies`, then `branches`, then `location_nodes`, then `organization_nodes`. Each foreign key points at a table created earlier, and the organization parent points at its own table.

`text`, `timestamptz`, partial unique indexes, composite foreign keys, self-referential foreign keys, and POSIX checks are PostgreSQL 18 features. No excluded syntax is used.

## Timestamp type

The first review found one open type.

Files: all four migration files.

Columns: `created_at` and `updated_at`, declared `timestamptz NOT NULL`.

The target schema requires the columns and does not name a PostgreSQL type. Step 20 physical decision section 6 assigns `timestamptz` only to `event_at`, `ingested_at`, `acted_at`, `calculated_at`, `finalized_at`, `started_at`, `action_at`, and `boundary_at`. It states that this is not `timestamptz` on every temporal column. `created_at` and `updated_at` are absent from that list and from the `date` and `time` lists.

That finding stays historical. It is not a claim that the original schema already specified `timestamptz` for these columns.

`docs/UNIVERSAL_HRMS_STEP20A_FOUNDATION_TIMESTAMP_DECISION.md` now assigns `timestamptz` to `created_at` and `updated_at` on `companies`, `branches`, `location_nodes`, and `organization_nodes`. The existing migration text matches that decision. The migration files were not rewritten. Section 6 of the frozen temporal decision is unchanged and still governs the columns named there.

No database default was added.

## Result

APPROVED FOR EXECUTION

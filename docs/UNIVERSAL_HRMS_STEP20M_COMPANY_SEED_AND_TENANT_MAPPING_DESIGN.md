# Universal HRMS — Step 20M Company Seed and Tenant Mapping Design

Design only. No company row was inserted. No ULID was generated. No database connection was opened. No company is migration-approved.

Classes:

- **APPROVED** — an existing approved document states the rule.
- **VERIFIED** — read from the migration file or from the Step 20L query record.
- **PROPOSED** — a procedure rule in this design. Not an authorization to run it.
- **UNRESOLVED** — required before execution, and not selected here.

## 1. Documents reviewed

These copies match between `legacy-reference\hrms\docs` and `D:\Project -All\Proedge HRMS\docs`. No conflicting version was found.

- `docs/UNIVERSAL_HRMS_STEP20L_TARGET_COMPANY_ULID_AND_APPROVAL_VERIFICATION.md`
- `docs/UNIVERSAL_HRMS_STEP20K_EMPLOYEE_MIGRATION_APPROVAL_AND_SOURCE_MANIFEST.md`
- `docs/UNIVERSAL_HRMS_STEP20J_EMPLOYEE_MIGRATION_DRY_RUN_DESIGN.md`
- `docs/UNIVERSAL_HRMS_STEP20A_FOUNDATION_IMPLEMENTATION_REPORT.md`
- `docs/UNIVERSAL_HRMS_STEP20A_MIGRATION_REVIEW.md`
- `docs/UNIVERSAL_HRMS_TARGET_DATABASE_SCHEMA_SPECIFICATION.md` sections 4.1 and 5
- `docs/UNIVERSAL_HRMS_MULTI_COMPANY_MIGRATION_MAPPING_WORKBOOK.md` section 3 and the company crosswalk

The physical definition is `next-hrms/database/migrations/2026_10_10_100001_create_companies_table.php`. That file exists once, in the Laravel tree. It was read and not modified.

Step 20L verified `companies` has 0 rows in local `proedge_new_hrms`. This step did not reconnect.

## 2. Approved company identity model

The tenant is the catalog company. The target primary key is a new ULID in `companies.id`. The legacy catalog id is an external reference.

| Catalog id | Legal name to copy | Alias, not loaded | Source database | `previous_db_id`, not the key | Employee `source_system`, for later employee rows |
| ---: | --- | --- | --- | ---: | --- |
| 24 | Pro-edge Associates Limited | `proedge` | `proedgea_newproedgehrm` | 5 | `legacy_proedge` |
| 20 | Viyellatex Group | `vtl` | `proedgea_vtlhrm` | 5 | `legacy_vtlhrm` |
| 10 | Atlas Copco Bangladesh Ltd. | `atlas` | `proedgea_atlas` | 26 | `legacy_atlas` |

Names and `previous_db_id` values are the Step 20I reading of the local catalog dump, consistent with the workbook. Catalog 5 is UL VS Bangladesh Ltd. Catalog 26 is Grameen Telecom Trust (GTT) Non-Clinical. Those two catalog rows are outside this seed.

Rules already approved:

- One target company per catalog id. `companies.source_key` stores that catalog id. Workbook and Step 20E.
- `companies.id` is a new ULID. It is not catalog 24, 20, or 10, and it is not dump-local 5 or 26.
- `db_alias` is not a target column and does not select behavior.
- `previous_db_id` is not the tenant. The value 5 is shared by catalog 24, catalog 20, and other catalog rows. It cannot be the lookup key.
- Salary `company_id` and `employees.emp_unit` are not tenants.
- A matching or similar name is not a second identity check. The catalog id is the identity. The name is copied from that catalog row.

## 3. Current target schema capability assessment

`2026_10_10_100001_create_companies_table.php` defines:

| Column | Nullability | Constraint |
| --- | --- | --- |
| `id` | Required | Primary key. Check `id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'` |
| `company_code` | Optional | Unique where not null (`companies_company_code_unique`) |
| `company_name` | Required | None beyond `NOT NULL` |
| `status` | Required | `Active` or `withdrawn` |
| `source_system` | Optional | None |
| `source_key` | Optional | None |
| `created_at`, `updated_at` | Required | `timestamptz` |

Schema section 4.1 and section 5 already place the legacy company id in `source_key`. The workbook places catalog 24, 20, and 10 there. No extra column and no mapping table are required for these three companies.

The approved schema does not enforce one row per `source_key`. Two rows can share `24` unless the establishment procedure refuses the second insert. That gap does not block storing the reference. A new unique index would be a schema change. This design does not add one. The procedure below is the control for these three keys.

`company_code` stays null. The workbook does not load the alias, and this design does not put the catalog id into `company_code`.

## 4. Proposed external-reference mapping method

**PROPOSED** as the seed method. The place it writes, `source_key`, is already **APPROVED**.

| Target column | Value for this seed |
| --- | --- |
| `id` | One new ULID, created at execution time, once per company |
| `company_name` | The catalog name in section 2 |
| `source_key` | Text `24`, `20`, or `10` |
| `company_code` | Null |
| `source_system` | Null until a named value is approved |
| `status` | The approved seed status. No value is selected in this document |
| `created_at`, `updated_at` | Transaction timestamp at insert |

Lookup key for a repeat run is `source_key` in `24`, `20`, `10`. Lookup is never `previous_db_id`, dump-local `companies.id`, salary `company_id`, or `emp_unit`.

## 5. Idempotent company establishment procedure

This procedure is specified for a later authorized execution. It is not run here.

Preconditions, all required:

1. The session is local `proedge_new_hrms` at `127.0.0.1:5432`, confirmed before any write.
2. The seed status for each of the three companies is an explicit approved choice of `Active` or `withdrawn`.
3. The three names and catalog ids in section 2 are the approved identity set.
4. D1–D6 remain irrelevant to this procedure. Employee approval is not a precondition, and this procedure does not grant it.

Steps:

1. Read `id`, `company_code`, `company_name`, `status`, `source_system`, and `source_key` from `companies`.
2. Stop if any row has `source_key` `5` or `26`.
3. For catalog `24`, `20`, and `10`, classify the existing rows:
   - Zero rows: the company is missing and may be inserted.
   - One row whose `company_name`, `status`, null `company_code`, and null `source_system` match the approved seed: keep that `id`. Do not generate another ULID.
   - One row that differs on any of those fields: stop. Insert nothing.
   - More than one row: stop. Insert nothing.
4. Stop if some other row already uses one of the three catalog names with a different `source_key`.
5. In one transaction, insert only the missing companies. Generate one ULID per inserted row inside that transaction. Set `source_key` to the catalog id, `company_name` to the catalog name, `company_code` and `source_system` to null, and `status` to the approved seed status. Set both timestamps to the transaction time.
6. If any insert fails, roll back the transaction.
7. After commit, run the read-only checks in section 6.
8. A later repeat starts again at step 1. When all three rows already match, the transaction inserts nothing and the existing ULIDs stay in place.

A partial set is allowed only when every row that already exists matches. A conflicting row stops the batch, including the companies that are still missing.

## 6. Validation and failure conditions

After a successful execution, a read-only query must show exactly these results:

| Check | Required result |
| --- | --- |
| Rows with `source_key` `24`, `20`, and `10` | Exactly one each |
| `id` values | Three distinct values, each matching the ULID check |
| `company_name` | Pro-edge Associates Limited, Viyellatex Group, Atlas Copco Bangladesh Ltd., each on its catalog id |
| `company_code` and `source_system` | Null, while those seed values remain the approved ones |
| `status` | The approved seed status |
| `source_key` `5` or `26` | Zero rows |
| Employee and episode rows | Unchanged by this procedure. Step 20L did not recount them. A later execution must record their counts before and after and show no change |

The procedure stops with no committed insert when any of these are true:

- The connection is not local `proedge_new_hrms`.
- Seed status is still unapproved.
- A catalog key has two rows, or one row whose name, status, code, or `source_system` differs from the approved seed.
- A row uses `source_key` `5` or `26`.
- A different `source_key` already carries one of the three catalog names.
- The generated `id` fails the ULID check.
- The post-check counts disagree with the table above.

Stopping on conflict leaves the conflicting row for a person to resolve. The procedure does not update it and does not delete it.

## 7. Unresolved questions and required approvals

| Item | Status | Effect |
| --- | --- | --- |
| Seed `status` for catalog 24, 20, and 10 | **UNRESOLVED**. Allowed stored values are `Active` and `withdrawn`. Neither is selected | Blocks execution. The column is required |
| `companies.source_system` | **UNRESOLVED**. The column may stay null | Does not block a seed that writes null. A later named value becomes part of the match rule before it is written |
| Unique index on `source_key` | Not in the approved schema | Not required for these three rows while section 5 is the only writer. A database constraint would be a separate schema decision |
| D1, D2, D3, D4, D6 | **PROPOSED** in Steps 20I and 20K. Approver fields are blank | Unchanged by company setup |
| Overlap, date order, inactive pay, meaning of `dis` | **UNRESOLVED** | Outside this seed |
| Target ULIDs | Absent. Step 20L counted 0 company rows | Created only by a later execution of section 5 |

## 8. Recommended next step

Approve one seed status, `Active` or `withdrawn`, for catalog 24, 20, and 10. Leave `source_system` null unless a separate decision names it. After that approval, a later step may run section 5 once against local `proedge_new_hrms` and then run the section 6 read-only checks.

That later step still must not approve D1–D6, must not load employees, and must not mark Proedge, Viyellatex, or Atlas migration-approved. Company identity and employee migration authorization stay separate gates.

## 9. Execution statement

No database connection was opened. No row was inserted. No ULID was generated. No migration, schema, dump, `.env`, or application file was changed.

BUSINESS DECISION REQUIRED

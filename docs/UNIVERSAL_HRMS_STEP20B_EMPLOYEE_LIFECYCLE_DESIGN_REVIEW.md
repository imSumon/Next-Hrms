# Step 20B Employee Lifecycle Design Review

Date: 2026-10-10

Status: read-only design review. No migration, database object, configuration, or foundation table was changed.

Labels used below:

- **APPROVED** — the approved specification or a frozen decision states it.
- **INFERRED** — a technical consequence that the documents do not state as a requirement.
- **UNDECIDED** — the documents do not choose it. It must not be added until a decision exists.

## 1. Documents reviewed

Read in `docs/`:

- `UNIVERSAL_HRMS_REQUIREMENTS.md` — EMP-CORE-001 through EMP-CORE-004, EMP-CONF-001, LIFE-CORE-001 through LIFE-CORE-008
- `UNIVERSAL_HRMS_ARCHITECTURE_ASSESSMENT.md` — present; employee identity and episode rules used here come from the requirements and the schema, not from a separate architecture rule
- `UNIVERSAL_HRMS_LOGICAL_DATA_ARCHITECTURE_ASSESSMENT.md` — episode ownership, rejoin, duplicate-code integrity
- `UNIVERSAL_HRMS_PHYSICAL_DATA_ARCHITECTURE_ASSESSMENT.md` — episode shape and employee-code uniqueness
- `UNIVERSAL_HRMS_TARGET_DATABASE_SCHEMA_SPECIFICATION.md` — sections 4 shared rules, 4.3, 4.4, 5, 6, 7, and 15
- `UNIVERSAL_HRMS_MIGRATION_DATA_CONTINUITY_ARCHITECTURE.md` — employee code and employment-history mapping
- `UNIVERSAL_HRMS_MULTI_COMPANY_MIGRATION_MAPPING_WORKBOOK.md` — code, name, joining, leaving, and status-flag mapping
- `UNIVERSAL_HRMS_STEP20_PHYSICAL_IMPLEMENTATION_DECISIONS.md` — ULID text, temporal types, composite company foreign keys, partial unique indexes
- `UNIVERSAL_HRMS_STEP20_FINAL_EMPLOYEE_CODE_DECISION.md`
- `UNIVERSAL_HRMS_STEP20_FINAL_DOMAIN_DECISIONS.md` — location and organization parents stay off these two tables
- `UNIVERSAL_HRMS_STEP20A_FOUNDATION_TIMESTAMP_DECISION.md`
- `UNIVERSAL_HRMS_STEP20A_FOUNDATION_IMPLEMENTATION_REPORT.md` — foundation tables are already migrated and are not altered

No legacy MySQL connection was opened. No audit count was re-queried.

## 2. Proposed `employees` schema

The row is the stable identity inside one company. It is not an employment period. There is no `employees.status` column.

| Column | Type and nullability | Label | Source |
| --- | --- | --- | --- |
| `id` | `text` not null, primary key | **APPROVED** | Schema section 4. ULID storage is Step 20 physical decision section 2 |
| `company_id` | `text` not null | **APPROVED** | Schema 4.3. One company per employee. EMP-CORE-002 |
| `employee_code` | `text` not null | **APPROVED** | Schema 4.3. Required business code. Not the primary key and not the punch identity |
| `display_name` | `text` not null | **APPROVED** | Schema 4.3. The only approved name column |
| `source_system` | `text` null | **APPROVED** | Schema section 4 shared rule and section 5. Legacy employee id is not the primary key |
| `source_key` | `text` null | **APPROVED** | Same |
| `created_at` | `timestamptz` not null | **APPROVED** column. Type follows the Step 20A instant convention as frozen for this step | Schema section 4 requires `created_at`. The Step 20A timestamp decision names the four foundation tables. This review applies that same `timestamptz` convention to this creation instant. It does not edit that decision file |
| `updated_at` | `timestamptz` not null | **APPROVED** column, same type note | The table is retained and a code change is audited. It is not an insert-only table, so schema section 4 includes `updated_at` |

No length is specified for `text` columns. No length is added.

Unsupported on `employees`, and not proposed:

- `status`. The employee-code decision states there is no `employees.status` column.
- Weekly off, shift, pay amount, photo, `db_alias`, `previous_db_id`, boss, and grand-boss. Schema sections 4 and 4.3.
- Department, designation, group, branch, and location. Those are effective-dated facts on `employment_assignments`, which is out of scope. EMP-CORE-004 and LIFE-CORE-002 through LIFE-CORE-005.
- Any other column copied from the legacy wide `employees` table. EMP-CONF-001 leaves extra attributes as later company configuration. The list is not approved.

## 3. Proposed `employment_episodes` schema

One row is one joining-to-separation span. Rejoin inserts another row. The previous row remains.

| Column | Type and nullability | Label | Source |
| --- | --- | --- | --- |
| `id` | `text` not null, primary key | **APPROVED** | Schema section 4 and ULID decision |
| `company_id` | `text` not null | **APPROVED** | Schema 4.4. DIRECT company scope |
| `employee_id` | `text` not null | **APPROVED** | Schema 4.4. The person |
| `joining_date` | `date` not null | **APPROVED** | Schema 4.4. LIFE-CORE-001. Step 20 temporal decision lists `joining_date` as `date` |
| `last_working_date` | `date` null | **APPROVED** | Schema 4.4. Optional. LIFE-CORE-007. Same temporal decision lists `last_working_date` as `date` |
| `episode_status` | `text` not null | **APPROVED** | Schema 4.4. Values are `Open`, `inactive`, and `separated`. EMP-CORE-003 says active, inactive, and separated. The approved stored token for the active case is `Open`, not `active` |
| `source_system` | `text` null | **APPROVED** | Schema section 4 shared rule and section 5 |
| `source_key` | `text` null | **APPROVED** | Same |
| `created_at` | `timestamptz` not null | **APPROVED** column, same timestamp note as `employees` | Schema section 4 |
| `updated_at` | `timestamptz` not null | **APPROVED** column, same timestamp note | Status and last working date are audited, so the row is not insert-only. Append-only in schema 4.4 means dates are not rewritten to erase the episode. It does not remove `updated_at` |

The pay effect of `inactive` is not a column. Schema 4.4 and LIFE-CORE-006 leave it unchosen.

## 4. Keys, constraints, and foreign-key review

| Rule | Label | Source |
| --- | --- | --- |
| `employees.id` and `employment_episodes.id` use the 26-character check `^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$` | **APPROVED** | Step 20 physical decision section 2 |
| `employees.company_id` references `companies.id` | **APPROVED** | Schema 4.3. `companies` is the tenant root, so this foreign key is not a composite pair |
| `UNIQUE (company_id, id)` on `employees` | **APPROVED** | Step 20 physical decision section 7. The episode foreign key needs that parent key |
| `UNIQUE (company_id, employee_code)` on every `employees` row, with no status predicate | **APPROVED** | Schema 4.3 and 15. Employee-code decision. Separation does not release the code |
| `employment_episodes.company_id` references `companies.id` | **APPROVED** | Schema 4.4 and section 6 |
| `employment_episodes (company_id, employee_id)` references `employees (company_id, id)` | **APPROVED** | Schema 4.4: the employee is in the same company. Physical decision section 7 |
| One open episode per employee | **APPROVED** | Schema 4.4 uniqueness. Physical form is a unique index on `employee_id` where `episode_status = 'Open'`. That matches the approved “unique when a condition holds” pattern. It is not an active-only employee-code index |
| `episode_status` is one of `Open`, `inactive`, `separated` | **APPROVED** | Schema 4.4. The check uses those exact tokens |
| `last_working_date` is empty while `episode_status` is `Open` | **APPROVED** | Schema 4.4 field purpose |
| `last_working_date` may be empty when the episode is `separated` | **APPROVED** | Employee-code decision: store the date when the source has one. Workbook: leave it empty when the source has none |
| Access path (`company_id`, `employee_code`) and (`company_id`, `id`) on employees | **APPROVED** as access, already covered by the unique key and primary key | Schema 4.3. No extra index |
| Access path (`company_id`, `employee_id`, `joining_date`) | **APPROVED** as an access description | Schema 4.4. Section 16 does not add a separate lookup index. No extra index is proposed |
| No change to `companies`, `branches`, `location_nodes`, or `organization_nodes` | **APPROVED** | Those tables already exist. These two tables reference `companies` and `employees` only |

`location_nodes` and `organization_nodes` are not parents of these two tables.

## 5. Rejoining and separation lifecycle rules

| Rule | Label | Source |
| --- | --- | --- |
| Separation keeps the `employees` row and `employee_code` | **APPROVED** | Employee-code decision. LIFE-CORE-007 |
| Separation sets `episode_status` to `separated` and stores `last_working_date` when the source has one | **APPROVED** | Employee-code decision |
| No attendance or pay day is created before `joining_date` | **APPROVED** as a processing rule | Schema 4.4. LIFE-CORE-001. It is not a new column |
| No day is invented after `last_working_date` | **APPROVED** as a processing rule | Schema 4.4. LIFE-CORE-007 |
| Rejoin uses the same `employees.id` and the same `employee_code`, and inserts a new episode | **APPROVED** | Employee-code decision. LIFE-CORE-008. Schema 4.4 |
| The previous episode remains | **APPROVED** | Same |
| A new person does not receive a code already used in that company | **APPROVED** | Employee-code decision |
| Two identities are never merged because their codes match | **APPROVED** | Employee-code decision |
| `inactive` stops attendance processing and pay according to company policy | **APPROVED** as a requirement | LIFE-CORE-006 |
| The pay effect of `inactive` | **UNDECIDED** | Schema 4.4. LIFE-CORE-006 points at D06/D07. No amount or pay flag is added |
| Whether `inactive` may store `last_working_date` | **UNDECIDED** | The empty-date sentence is written for `Open` only |
| Whether `last_working_date` must be on or after `joining_date` | **UNDECIDED** | No order check is specified |
| Whether two episodes for one employee may overlap in calendar dates | **UNDECIDED** | Schema 4.4 requires one open episode. Section 7’s non-overlap list does not include `employment_episodes`. “Overlapping employment facts” in the logical assessment is one value per fact type on a date, which is `employment_assignments`, not episode spans |
| A database trigger that freezes `joining_date` | **INFERRED** and not proposed | Schema 4.4 says dates are not rewritten to erase the episode. It does not name a trigger |

## 6. Migration and reconciliation implications

Legacy MySQL was not queried. The following comes from the migration architecture and the mapping workbook.

| Case | Target handling | Label |
| --- | --- | --- |
| One legacy employee row | New `employees.id`. Legacy id in `source_system` and `source_key`. Code text copied to `employee_code`. Name copied to `display_name` | **APPROVED** |
| Known joining and leaving dates | One episode for that known span. Dates copied. Missing history is not invented | **APPROVED** |
| Active and not discontinued | `episode_status = 'Open'` when that pair is unambiguous | **APPROVED** by the workbook |
| Discontinued, not active, and a leaving date exists | `episode_status = 'separated'` | **APPROVED** by the workbook |
| Both flags true | Do not invent a status word. Atlas employee code EB9091 is the documented case: `discontinue_date` 2026-08-25 and later attendance. Quarantine for manual resolution | **APPROVED** exception path |
| Duplicate `employee_code` inside one company | Explicit migration exception. Do not merge identities. Do not reassign or delete a code to satisfy `UNIQUE (company_id, employee_code)` | **APPROVED** |
| Empty display name, or a loadable row with no joining date | Exception. Do not fabricate the value | **APPROVED** by the workbook |
| Attendance on a day after `last_working_date` | Keep the historical day and list it. Do not delete it to make the episode look clean | **APPROVED** by the workbook |
| Department, designation, location, and shift history | Not columns on these two tables. A single legacy employee row is not a full assignment history | **APPROVED** |
| Punch card equal to the employee code | Still a punch identity, not `employee_code` | **APPROVED** |

No company is migration-approved. This review does not authorize a load.

## 7. Unresolved business decisions

These stay out of the two tables:

1. Pay effect of `inactive`. D06/D07. No column.
2. Whether an `inactive` episode may have `last_working_date`.
3. Whether `last_working_date` must be on or after `joining_date`.
4. Whether episode date spans must be non-overlapping beyond the approved rule of one `Open` episode per employee.

None of these is a reason to add a column. None of them authorizes an exclusion constraint or a date-order check.

## 8. Exact changes required before implementation

No approved field is missing, and no approved constraint has two conflicting forms.

Before a migration is written, the implementation must use only the **APPROVED** rows in sections 2 through 5. It must not add the **UNDECIDED** checks. It must not alter the four foundation tables.

The Step 20A timestamp decision file still names only `companies`, `branches`, `location_nodes`, and `organization_nodes`. This review does not rewrite that file. `created_at` and `updated_at` on these two tables use `timestamptz` because this step freezes that instant convention for them.

## 9. Recommended next step

Write Laravel migrations for `employees` and then `employment_episodes` only, against `proedge_new_hrms`, after a separate execution instruction. Do not include assignments, attendance, leave, overtime, payroll, users, or approvals.

## Result

APPROVED FOR IMPLEMENTATION

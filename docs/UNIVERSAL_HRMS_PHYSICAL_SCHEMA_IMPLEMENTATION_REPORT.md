# Universal HRMS Physical Schema Implementation Report

Final status: **FAIL**

No tables were created. No migration files were written. `new_proedge_hrms` was not created. No legacy data was imported. D01–D18 were not decided. No company is migration-approved.

Step 17’s 5,353 unexplained rows are not lost data. They were counted because that rehearsal loaded nothing. This step does not repeat that count as a data loss.

---

## 1. Objective

Implement the approved Universal HRMS target schema as reproducible, source-controlled migrations, apply them to a clean local database named `new_proedge_hrms`, and prove the physical result matches the specification.

The objective excludes migration, ETL, application code, APIs, UI, payroll calculation, attendance calculation, and production deployment.

## 2. Source specifications reviewed

Read before any database change:

| Document | What it authorizes for this step |
|---|---|
| `docs/UNIVERSAL_HRMS_TARGET_DATABASE_SCHEMA_SPECIFICATION.md` | Table names, fields, required or optional, company scope, uniqueness concepts, history rules. Section 19 defers SQL, engine, column types, and index implementation |
| `docs/UNIVERSAL_HRMS_PHYSICAL_DATA_ARCHITECTURE_ASSESSMENT.md` | One schema, `company_id` tenancy, ULID recommendation. Section 21 defers engine, identifier storage format, and DDL |
| `docs/UNIVERSAL_HRMS_LOGICAL_DATA_ARCHITECTURE_ASSESSMENT.md` | Logical entities. Not tables |
| `docs/UNIVERSAL_HRMS_ARCHITECTURE_ASSESSMENT.md` | Principles and ADR-001–ADR-017. No DDL |
| `docs/UNIVERSAL_HRMS_REQUIREMENTS.md` | Capabilities and open D01–D18 |
| `docs/UNIVERSAL_HRMS_MIGRATION_DATA_CONTINUITY_ARCHITECTURE.md` | Load rules for a later migration. Not a schema |
| `docs/UNIVERSAL_HRMS_MULTI_COMPANY_MIGRATION_MAPPING_WORKBOOK.md` | Source-to-target mapping. Not a schema |
| `docs/UNIVERSAL_HRMS_PROEDGE_AUGUST_2026_DRY_RUN_REPORT.md` | Prior rehearsal failed because this physical schema did not exist |

The logical model is specified. The physical encoding is not.

## 3. Target database

| Check | Result |
|---|---|
| Name requested | `new_proedge_hrms` |
| Present before this step | No. `SHOW DATABASES` returned no match |
| Created by this step | No |
| Inspected for pre-existing structures | Nothing to inspect. The database is absent, so it was not dropped |
| Legacy databases | Not opened for writes. Not imported |
| Production | Not connected |

The local MySQL server used for earlier read-only audits is Laragon MySQL 8.4. That server is evidence of the workstation. The approved architecture does not select it as the Universal HRMS engine.

## 4. Migration files created

None.

### Project location

The step requires the existing next-hrms project and its migration layout. Searched:

- `D:\Project -All\Proedge HRMS` and two levels beneath it
- `D:\Project -All`
- `D:\laragon\www` and `C:\laragon\www`
- `C:\Users\ASUS\Documents`

No `next-hrms` directory exists. No `database/migrations` tree exists under the Proedge HRMS folders. The legacy application in this workspace is Yii2. It is the source system, not the target project.

Creating a new Laravel or Yii application would introduce a framework. The specification defers the framework, and this step forbids introducing an ORM or a database technology without approval.

### Why SQL files were not written anyway

A standalone `.sql` file would still have to choose an engine, a ULID storage type, money scale, duration storage, and a mechanism for overlap and immutability. Those choices are the gaps in section 20. Writing them would be a substitute physical schema.

## 5. Table inventory

Specified required and optional tables, from schema specification section 3. None were created.

| Disposition | Tables |
|---|---|
| Required | `companies`, `branches`, `departments`, `designations`, `employee_groups`, `employees`, `employment_episodes`, `employment_assignments`, `reporting_chains`, `reporting_links`, `users`, `user_employee_bindings`, `shifts`, `shift_versions`, `shift_assignment_candidates`, `shift_resolutions`, `weekly_off_rules`, `holiday_calendars`, `public_holidays`, `punch_sources`, `punch_identities`, `punch_identity_overlaps`, `punch_events`, `attendance_calculations`, `attendance_selected_punches`, `attendance_day_result_versions`, `attendance_day_results`, `leave_types`, `leave_requests`, `leave_day_impacts`, `attendance_corrections`, `attendance_correction_values`, `approval_workflows`, `approval_workflow_versions`, `approval_workflow_steps`, `approval_instances`, `approval_instance_steps`, `approval_actions`, `overtime_calculations`, `payable_overtime`, `salary_heads`, `salary_structures`, `employee_structure_assignments`, `salary_structure_heads`, `employee_structure_head_values`, `payroll_periods`, `payroll_period_events`, `payroll_runs`, `payroll_run_declared_inputs`, `payroll_results`, `payroll_result_heads`, `payroll_result_inputs`, `payroll_result_optional_facts`, `attendance_periods`, `attendance_period_events`, `policy_definitions`, `policy_values`, `policy_citations`, `audit_events`, `extension_catalog`, `company_extension_enablements` |
| Optional, specified | `location_nodes`, `public_holiday_shifts` |
| Merged, correctly absent | `organization_units`, punch-source-reference table, leave policy table, overtime approval table, paid-OT table, `payroll_snapshots`, payslip amount table, policy-scope table |
| Deferred, correctly absent | Role, permission, and scope tables. Attendance calculation batch table. Generic legacy-key table |

Count of tables that would be created once the gaps are closed: 64 required plus 2 optional.

## 6. Relationship inventory

Specified, not created. Parents and children are those in specification section 4. Every DIRECT and INDIRECT operational table carries `company_id` except `companies` itself. GLOBAL tables are `policy_definitions`, `extension_catalog`, and product-scope `policy_values`.

Relationships that must stay distinct, and that this step did not collapse:

| Boundary | Specified stores |
|---|---|
| Raw punch versus attendance result | `punch_events` versus `attendance_day_result_versions` |
| Employee versus punch identity | `employees.employee_code` versus `punch_identities.identity_value` |
| Punch versus correction | `punch_events` versus `attendance_corrections` |
| Request, approval, and final result | `attendance_correction_values.value_stage` plus a new result version |
| Calculated, payable, and paid OT | `overtime_calculations`, `payable_overtime`, `payroll_result_heads` |
| Attendance versus payroll | Day results versus `payroll_results` |
| Run versus finalized snapshot | `payroll_runs` versus finalized `payroll_results` and children |
| Policy versus extension | `policy_values` versus `extension_catalog` and `company_extension_enablements` |
| Hidden UI versus authorization | No permission tables. Roles are deferred |

`work_date` is specified on the resolution, calculation, selected punch, result version, current pointer, and correction. It is specified as absent from `punch_events`.

## 7. Index inventory

Specification section 16 lists conceptual access paths. It states that no engine syntax is specified. No indexes were created. Uniqueness concepts that a later unique index must express are in section 8 of this report, taken from specification section 15.

The punch time path is specified as non-unique. That rule is recorded. It was not implemented.

## 8. Constraint inventory

Specified rules, from specification section 15. None were created.

| Rule | Specified constraint | Physical form in the specification |
|---|---|---|
| Surrogate primary key | `id` ULID on every table | Storage format deferred |
| Company ownership | `company_id` required, child company matches parent | Match mechanism not stated |
| Active employee code | One active code per company. No all-history unique key | “Active” predicate not given as SQL |
| Active punch identity | One active value per company unless an overlap row exists | Partial uniqueness mechanism not stated |
| Source event | Unique on company, source, and `source_event_id` only when the id is present | Partial unique index mechanism not stated |
| No unique punch timestamp | Same-second events may both exist | Stated |
| One current day result | Unique company, employee, `work_date` on `attendance_day_results` | Stated as uniqueness |
| Effective-date overlap | Rejected for the tables in specification section 7 | Rejection mechanism not stated |
| One in-progress payroll run | Per company and period | Stated |
| `run_sequence` | Unique per period | Stated |
| One current payroll result | Per company, period, and employee | Stated |
| Finalized children immutable | No update after finalization | Enforcement mechanism not stated |
| Leave request overlap | Not constrained | Confirmed absent |
| Hard delete | Forbidden for punches, versions, corrections, approval actions, OT versions, finalized results, audit events | Enforcement mechanism not stated |

## 9. Company-boundary validation

Not executed. No tables exist.

The specification requires one shared schema, `company_id` on operational rows, and no cross-company foreign key. It does not state whether the match is a composite foreign key on `(id, company_id)` or another check. A ULID primary key alone stops a dangling parent. It does not stop a child row from storing a different `company_id` than its parent. That enforcement choice is open. It was not invented.

No `proedge_employees`, `vtl_employees`, or `atlas_employees` tables were created. No database-per-company tenant model was created.

## 10. Effective-dating validation

Not executed. No tables exist.

The specification requires half-open intervals, `[effective_from, effective_to)`, and rejection of overlap at the same precedence. It does not specify the SQL or trigger that rejects the overlap. MySQL 8.4 has no general exclusion constraint. Choosing a trigger, a generated column, or application-only checks would be a silent decision.

No grace, late, early-out, leave-pay, absence, OT, cutoff, or approval values were stored. There is no database in which they could have been stored.

## 11. Attendance model validation

Not executed. No tables exist.

The specified path remains the one in specification section 8:

```
punch_events
→ attendance_calculations (original)
→ attendance_day_result_versions
→ attendance_day_results
→ attendance_corrections
→ attendance_correction_values (original, requested)
→ approval_actions
→ attendance_correction_values (approved)
→ attendance_calculations (recalculation)
→ attendance_day_result_versions (new)
→ attendance_day_results (pointer moves)
→ attendance_correction_values (final)
```

`punch_events` has no `work_date`. That separation is specified and was not implemented.

## 12. Correction model validation

Not executed. No tables exist.

Four stages are specified: `original`, `requested`, `approved`, `final`. The raw punch is not a parent that gets updated. `original_result_version_id` is required on the correction header in specification section 4.19. The migration workbook already records that some legacy rows have no pre-image. That is a later load limitation, not a change made here. The required column was not relaxed.

## 13. Approval model validation

Not executed. No tables exist.

Specified resolver kinds are `role`, `reporting_manager`, `organization_approver`, and `named_fallback`. `sequence_no` starts at 1. `bypass_policy` stays empty until a company sets it. No boss, grand-boss, first-boss, or second-boss column is in the specification, and none was added.

Role and permission tables stay deferred. `resolver_role_key` is the specified placeholder.

## 14. OT model validation

Not executed. No tables exist.

| Figure | Specified table | Created |
|---|---|---|
| Calculated OT | `overtime_calculations` | No |
| Payable OT | `payable_overtime` | No |
| Paid OT | `payroll_result_heads` plus `payroll_result_inputs` | No |

No equality constraint is specified. None was added. No eligibility flag, formula, rounding method, or rate column was added.

## 15. Payroll snapshot validation

Not executed. No tables exist.

`payroll_results` is the snapshot. A separate `payroll_snapshots` table is specified as merged and was not created. Finalized content is specified as immutable. The specification does not say whether that immutability is a database trigger, a privilege, or an application rule. That mechanism was not chosen.

The specified snapshot contents are employee, period, structure assignment, head amounts, net pay, run version, actor, timestamps, and finalization state. Optional facts are a table with no required fact key. BD-D18 stays open. No optional fact was seeded.

## 16. Cross-midnight work_date validation

Not executed. No tables exist.

The specification stores the capability on `shift_versions.crosses_midnight`, `start_time`, `end_time`, `punch_window_start`, and `punch_window_end`. The illustration is a window from 2026-10-09 09:00 through 2026-10-10 09:00 with `work_date` 2026-10-09, while a punch at 2026-10-10 02:00 keeps that timestamp on `event_at`.

No sample shift and no sample punch were inserted. Schema tests that need rows were not run, because creating those rows would require the missing column types and would be demonstration data.

## 17. Reproducibility test

Not run.

There is no migration to apply to `new_proedge_hrms`, and no second disposable database was created. A clean replay cannot pass until the migration files exist.

## 18. Automated validation results

Not run. The fourteen structural tests were not written.

| Requested test | Result |
|---|---|
| Company-scoped identity uniqueness | Not run. Blocked |
| Foreign-key integrity | Not run. Blocked |
| Employee and punch identity separation | Not run. Blocked |
| Cross-company reference prevention | Not run. Mechanism unspecified |
| Effective-date overlap | Not run. Mechanism unspecified |
| Raw punch immutability | Not run. Mechanism unspecified |
| Attendance version relationship | Not run. Blocked |
| Correction history relationship | Not run. Blocked |
| Approval-step ordering | Not run. Blocked |
| Calculated, payable, and paid OT separation | Not run. Blocked |
| Payroll snapshot relationship | Not run. Blocked |
| Finalized payroll protection | Not run. Mechanism unspecified |
| Cross-midnight `work_date` representation | Not run. Blocked |
| Audit and history references | Not run. Blocked |

## 19. Specification-to-implementation matrix

| Specification area | Implemented | Verified | Gap | Notes |
|---|---|---|---|---|
| Table inventory, section 3 | No | No | Blocked | Names are specified. No database |
| Field list, section 4 | No | No | Blocked | Required versus optional is specified. SQL types are not |
| ULID primary keys, section 5 | No | No | Blocked | Recommendation exists. Storage format is deferred in physical architecture section 21 |
| `company_id` placement, section 6 | No | No | Blocked | Placement is specified. Parent-child company match mechanism is not |
| Half-open effective dates, section 7 | No | No | Blocked | Interval rule is specified. Enforcement mechanism is not |
| `work_date` apart from `event_at`, section 8 | No | No | Blocked | Column placement is specified |
| Cross-midnight shift window, section 8 | No | No | Blocked | Flag and clock columns are specified |
| Leave types, requests, impacts, section 9 | No | No | Blocked | Pay effect correctly omitted |
| Correction stages, section 10 | No | No | Blocked | Four stages are specified |
| Ordered approval, section 10 | No | No | Blocked | No boss columns specified |
| Three OT figures, section 11 | No | No | Blocked | Separate tables are specified |
| Payroll snapshot, section 12 | No | No | Blocked | `payroll_results` is the snapshot. Immutability mechanism is not specified |
| Policy keys without values, section 13 | No | No | Blocked | Key list is specified. `value_payload` physical type is not |
| Audit events, section 14 | No | No | Blocked | Append-only rule is specified. Enforcement mechanism is not |
| Uniqueness rules, section 15 | No | No | Blocked | Concepts are specified. Partial-unique SQL is not |
| Conceptual indexes, section 16 | No | No | Blocked | Section 16 says no engine syntax is specified |
| Deferred role tables, section 19 | Not implemented, correctly | Not applicable | None for this omission | Deferred on purpose |
| Merged tables | Not implemented, correctly | Not applicable | None for this omission | Must stay absent |
| D01–D18 values | Not stored, correctly | Not applicable | None | Values stay empty |
| Legacy columns `db_alias`, `previous_db_id`, `emp_unit`, `photo`, `boss`, `balid`, `area` | Not added, correctly | Not applicable | None | Forbidden unless the specification adds them. It does not |
| Client extensions as core tables | Not added, correctly | Not applicable | None | Atlas Bills, bank files, JV, letters, and NMS stay behind `extension_catalog` |
| Engine | No | No | Blocked | Physical architecture section 21 and schema section 19 |
| Migration home | No | No | Blocked | No next-hrms project |

## 20. Gaps and blockers

These are the precise reasons implementation stopped. None of them was closed by a guess.

1. **No next-hrms project.** There is no framework configuration and no migration directory. The legacy Yii2 tree is not the target project.
2. **Database engine is deferred.** Schema specification section 19 and physical architecture section 21. Laragon MySQL 8.4 is the local audit server. It is not an approved product engine.
3. **Column types are deferred.** The same two sections. Required-versus-optional is specified. `CHAR` versus binary ULID, decimal scale for `net_pay` and head `amount`, and the type of `late_duration`, `early_duration`, and `value_payload` are not.
4. **ULID storage format is deferred.** Physical architecture section 21: “identifier storage format beyond the ULID recommendation.”
5. **Overlap rejection has no physical mechanism.** Specification section 7 says overlapping same-precedence rows are rejected and lookup must not depend on query order. It does not specify an exclusion constraint, trigger, or other enforcement. MySQL 8.4 cannot express a general exclusion constraint as a declarative constraint.
6. **Active-code uniqueness has no SQL predicate.** “One active holder” is specified. Employee status lives on `employment_episodes`, not on `employees`. The specification also refuses an all-history unique key because reuse after separation is undecided. A partial unique index needs a predicate the specification does not give.
7. **Parent and child `company_id` match has no mechanism.** Specification section 6 requires the match. A foreign key on `id` alone does not enforce it.
8. **Insert-only and finalized immutability have no mechanism.** Punches, result versions, correction stages, approval actions, and finalized payroll children must not be updated or hard-deleted. The specification does not choose a trigger, privilege, or application rule. This step asked for protection “where represented by schema constraints.” Those constraints are not represented as SQL.
9. **Unknown-actor marker has no physical form.** The specification allows the marker for migrated history and forbids a fabricated user. It does not say whether the marker is a null, a sentinel row, or another token.
10. **Index syntax is explicitly withheld.** Specification section 16.

Items that are specified and were not treated as gaps: table names, field names, required and optional intent, merged and deferred tables, the policy key list, empty D01–D18 values, and the absence of legacy and client-extension core tables.

## 21. Final status

**FAIL**

The logical schema is specified. The physical schema was not implemented, because doing so required choices the approved documents defer and this step forbids making silently. No validation could pass.

Unblock requires an explicit decision on items 1 through 10 in section 20, including the project path and the engine. Those decisions are not made here.

### Safety statement

| Item | Result |
|---|---|
| Production modified | NO |
| Legacy source modified | NO |
| Legacy databases imported | NO |
| Legacy data migrated | NO |
| `new_proedge_hrms` created | NO |
| `legacy_proedge` data loaded into target | NO |
| Schema validation passed | NO |
| Business decisions D01–D18 changed | NO |
| Migration approval granted | NO |

### Verification

| Check | Result |
|---|---|
| Specification read before database changes | Yes |
| Substitute logical schema invented | No |
| Legacy schema copied | No |
| Legacy tables converted one-to-one | No |
| Legacy-only columns added | No |
| D01–D18 values inserted | No |
| Client-extension tables added to the core | No |
| Migration or ETL executed | No |
| Application, API, or UI written | No |
| Company declared migration-approved | No |

# Universal HRMS Target Technology and Physical Implementation Decision

Current approved target database: **PostgreSQL 18.x**. MySQL is the legacy production source only. It is read-only for this architecture. PostgreSQL is the physical implementation target. No production database, schema, or table is to be created or changed. The local development server has been validated at PostgreSQL 18.6. See section 22.

Historical status of sections 1–21: Step 19 assessment and recommendation only. No database, table, migration, application, or data load was created by that assessment. A recommendation in those sections was not, by itself, an approved decision. Later approval, recorded in `docs/UNIVERSAL_HRMS_TECHNICAL_DECISION_APPROVAL_AND_ENVIRONMENT_READINESS.md` and narrowed to PostgreSQL 18.x in section 22, covers only the items section 22 lists.

---

## 1. Executive Summary

The logical model is decided. The physical encoding is not.

Approved documents already fix one product schema, `company_id` as the tenant boundary, ULID as the logical primary key, business codes as attributes, `work_date` separate from punch time, and the separations among punch, attendance, correction, the three overtime figures, and the payroll snapshot. ADR-016 leaves the application framework, the database engine, and physical table design deferred. The schema specification leaves engine, column types, ULID storage format, and index SQL deferred.

This step recommends PostgreSQL as the engine, Laravel as the backend and migration home, `CHAR(26)` ASCII text as the ULID storage form, database exclusion constraints for effective-date overlap, and database triggers plus append-only writes for immutability. Each of those is a recommendation that still needs project approval.

The project is **NOT READY FOR STEP 20**.

Step 17’s 5,353 rows are not lost. They were never loaded. Step 18 correctly refused to invent a physical schema. This document does not reopen either failure as a data problem.

## 2. Current Project State

| Item | State |
|---|---|
| Workspace | Legacy Yii2 application at `legacy-reference/hrms`. Design documents under `docs/` |
| Target application | No `next-hrms` project. No `database/migrations` tree |
| `new_proedge_hrms` | Does not exist. Not created here |
| Step 17 | FAIL. Dry run stopped before a physical schema |
| Step 18 | FAIL. Stopped because engine, types, and a migration home were not selected |
| D01–D18 | Open. BD-D15 deferred |
| Any company migration-approved | No |

## 3. Documents Reviewed

| Document | Decision status used here |
|---|---|
| `docs/UNIVERSAL_HRMS_ARCHITECTURE_ASSESSMENT.md` | AP-01–AP-10 and ADR-001–ADR-015 accepted. ADR-016 defers framework, engine, and tables |
| `docs/UNIVERSAL_HRMS_LOGICAL_DATA_ARCHITECTURE_ASSESSMENT.md` | Logical entities. No framework or SQL |
| `docs/UNIVERSAL_HRMS_PHYSICAL_DATA_ARCHITECTURE_ASSESSMENT.md` | One schema and `company_id`. ULID recommended. Engine, storage format, and DDL deferred in section 21 |
| `docs/UNIVERSAL_HRMS_TARGET_DATABASE_SCHEMA_SPECIFICATION.md` | Table and field intent. Section 19 defers SQL, engine, column types, and index implementation |
| `docs/UNIVERSAL_HRMS_REQUIREMENTS.md` | Capabilities. Section 5 places Laravel, React, Flutter, APIs, and tables out of that document. That is not a selection and not a ban |
| `docs/UNIVERSAL_HRMS_MIGRATION_DATA_CONTINUITY_ARCHITECTURE.md` | Company-wise path and the continuity equation. No migration code |
| `docs/UNIVERSAL_HRMS_MULTI_COMPANY_MIGRATION_MAPPING_VALIDATION.md` | Mapping validation. No engine |
| `docs/UNIVERSAL_HRMS_MULTI_COMPANY_MIGRATION_MAPPING_WORKBOOK.md` | Field mapping for three companies. No DDL |
| `docs/UNIVERSAL_HRMS_PROEDGE_AUGUST_2026_DRY_RUN_REPORT.md` | Baseline confirmed. Load did not run |
| `docs/UNIVERSAL_HRMS_PHYSICAL_SCHEMA_IMPLEMENTATION_REPORT.md` | The ten physical gaps this step answers with recommendations |

The workspace was inspected. It contains the legacy Yii2 tree, local runtime copies, legacy data folders, and the design documents. It does not contain a target application.

## 4. Database Engine Decision

Classification: **B. Recommended. C. Approval required.** Not already decided. ADR-016 and schema section 19 defer the engine. The legacy product’s use of MySQL is evidence of the current server, not a target decision.

| Candidate | Advantages | Risks | Fit | Decision |
|---|---|---|---|---|
| PostgreSQL, current stable major | Transactions, foreign keys, indexes, JSON (`jsonb`), range types, and exclusion constraints that reject overlapping intervals without depending on query order. Laravel supports it. Row-level checks can express “child `company_id` equals parent `company_id`” | Not installed on this workstation. The operators know MySQL from the legacy system. A new operational skill | Strongest fit for the overlap rule and for company-match checks | Recommended. Approval required |
| MySQL 8.4 InnoDB | Already installed (8.4.3). Transactions, foreign keys, indexes, JSON, Laravel support. Familiar to this project | No exclusion constraints. Overlap and immutability become triggers. A trigger is easier to bypass or to write incorrectly under concurrency. Selecting it because the legacy application uses it would violate this step | Adequate if the project explicitly accepts trigger enforcement | Not recommended as the default. Remains an approvable alternative |
| MariaDB | Similar to MySQL for this workload | Same overlap gap as MySQL, plus a second MySQL-family variant with no approved reason | No better fit than MySQL 8.4 | Not recommended |
| SQLite | Simple local file | Weak concurrency and a poor match for multi-company payroll transactions | Unfit for the target | Not recommended |
| Database per company | Matches the legacy layout | Contradicts ADR-002 and the physical architecture. Alias behavior returns | Unfit | Rejected by the approved architecture. Not a candidate |

**Recommendation:** PostgreSQL.

**Approval:** required before Step 20. Until that approval, the engine stays deferred.

Infrastructure that stays undecided, and is not needed to create the first schema: one server versus many, replication, partitioning, sharding, backup product, and cloud host. The physical architecture already says a later split may place the same schema on more than one server, with `company_id` still on every operational row.

## 5. Application Framework Decision

No target framework exists in the workspace. ADR-016 defers it. The requirements document lists Laravel and React as out of scope of that document, which means they were not chosen there. It does not choose Yii2 either. The legacy application is Yii2. Continuing Yii2 would carry the alias-selected engines the architecture rejects.

| Layer | Candidate | Recommendation | Reason | Approval needed |
|---|---|---|---|---|
| Backend | Laravel on PHP 8.3 | Laravel | PHP 8.3 and Composer are already on this machine. Laravel migrations, ULID keys, queues, and database transactions match the later build. Yii2 stays the legacy system | Yes |
| Backend alternative | Symfony, or staying on Yii2 | Not recommended | Symfony is capable and was not requested. Yii2 is the system being replaced | Yes, if someone rejects Laravel |
| Database | PostgreSQL | PostgreSQL | Section 4 | Yes |
| Frontend | React | React, later | Admin, ESS, and reports need a UI. React is the candidate the project asked to evaluate. It is not required to create tables | Yes, and not blocking for schema-only Step 20 |
| Frontend alternative | Flutter | Not in the first build | The requirements document names it only as out of scope of that document. No approved mobile scope | Yes, if mobile is pulled forward |
| API | Laravel HTTP API, company on every request | Same backend | AP-07: the server authorizes the action. No separate API framework | Yes, with the backend choice |
| Authentication and authorization | `users` and `user_employee_bindings` as specified. Role tables stay deferred. `resolver_role_key` remains a key until a security schema exists | Do not invent RBAC tables in Step 20 | The schema specification defers role and permission tables. AP-07 still requires server-side checks once those tables exist | The deferred security schema needs its own approval later. Not a Step 20 table set |
| Background jobs | Laravel queue with the database as the driver | Database queue first | Attendance recalculation and payroll runs are long transactions. The architecture defers Redis, queues-as-infrastructure, and vendors. A database queue uses the same engine and adds no new product | Broker choice: yes. Redis is not recommended now |
| Migration tooling | Laravel migrations in the future project | Laravel migrations | Reproducible, ordered, source-controlled. A hand-written SQL file is the fallback only if Laravel is rejected | Yes, with the backend choice |

**Recommendation:** Laravel for the backend and for schema migrations. React for a later UI. No Flutter, no Redis, and no second ORM in the first implementation.

**Approval:** required. This document does not create the application.

## 6. ULID Physical Storage Decision

**Logical requirement, already decided (A).** Every table’s primary key is `id`, a ULID. UUID is acceptable only where creation-time order is unnecessary. Business codes are not primary keys. This is schema specification sections 2 and 5. The physical architecture recommends ULID and defers the storage format.

**Physical storage, not decided (B + C).**

| Form | Advantages | Risks | Recommendation |
|---|---|---|---|
| `CHAR(26)` / text, ASCII Crockford base32 | Readable in SQL, exception registers, and migration logs. Sorts in creation order. Matches Laravel’s `ulid` column. 26 bytes, not a wide Unicode `CHAR` | Larger indexes than 16 bytes. Must be constrained to the ULID alphabet so a free string cannot be stored | Recommended |
| `BINARY(16)` or `bytea` | Smaller indexes. Correct if the 128 bits keep the ULID timestamp in the high bytes | Opaque in queries and in reconciliation spreadsheets. Every tool must encode and decode. Easier to damage during a dry run | Not recommended for the first schema |
| Database `uuid` with a rearranged byte order | 16-byte native type on PostgreSQL | PostgreSQL `uuid` ordering is not ULID time order unless the bytes are deliberately mapped. That mapping is a private convention | Not recommended |

**Recommendation:** store `id` as 26-character ASCII text with a check that the value is a Crockford base32 ULID. Do not use a UTF-8 `CHAR(26)` that widens the column by the character set.

**Approval:** required. The logical ULID rule does not need re-approval. The storage form does.

## 7. Primary Key Strategy

Already decided for the logical model (A). Repeated here so Step 20 does not reopen it.

| Area | Primary key | Business identifier | Company-scoped uniqueness, separate from the primary key |
|---|---|---|---|
| Company | `companies.id` ULID | `company_code` when present | `company_code` unique among companies when present |
| Employee | `employees.id` | `employee_code` | `UNIQUE (company_id, employee_code)`. Separation does not release the code. Closed by `docs/UNIVERSAL_HRMS_STEP20_FINAL_EMPLOYEE_CODE_DECISION.md` |
| Organization | `id` on branch, department, designation, group, location node | `code` when present | (`company_id`, `code`) when code is present |
| Shift | `shifts.id` | `shift_code` when present | Code per company when present |
| Shift window | `shift_versions.id` | None | One version of a shift contains a date |
| Roster and default | `shift_assignment_candidates.id` | None | Same employee and same `candidate_kind` do not overlap. Default and roster may both cover a date |
| Punch source | `punch_sources.id` | `source_name` | `source_name` unique per company |
| Punch identity | `punch_identities.id` | `identity_value` | One active value per company unless `punch_identity_overlaps` authorizes the interval |
| Punch event | `punch_events.id` | None | Unique (`company_id`, `punch_source_id`, `source_event_id`) only when a source event id exists. Not unique on timestamp |
| Attendance result | `id` on calculation, version, and current pointer | None | One current pointer per company, employee, and `work_date` |
| Correction | `attendance_corrections.id` | None | One in-progress application per employee and `work_date` |
| Approval | `id` on workflow, version, instance, step, action | Workflow `name` | `sequence_no` unique inside a version and inside an instance copy |
| OT | `id` on calculated and payable rows | None | One calculated row per result version. One current payable row per employee and `work_date` |
| Payroll | `id` on period, run, result | Period `label` plus bounds | `run_sequence` unique per period. One current result per company, period, and employee |
| Payroll snapshot | The finalized `payroll_results` row and its children. No second snapshot table | None | Same as the result |
| Audit | `audit_events.id` | None | None beyond the surrogate key. Lookup is by subject |

Employee code, punch card number, and payroll head code are attributes. They are not universal surrogate primary keys.

Legacy `source_system` and `source_key` stay optional trace columns. They are not the primary key.

## 8. Constraint Strategy

The rule is already specified (A). The mechanism below is the recommendation (B) and needs approval (C).

| Rule | Database constraint | Unique or lookup index | Trigger | Application service | Validation process | Recommendation |
|---|---|---|---|---|---|---|
| Foreign keys | Yes | Supporting index | No | No | Load rejects missing parents | Declarative foreign keys |
| Child `company_id` matches parent | Composite foreign key `(parent_id, company_id)` where the parent unique key includes both | Yes | Only if a composite key cannot be declared on that parent | Repository still filters by company | Migration check | Composite foreign keys. Application filters are additional, not a substitute |
| Active employee code | Partial unique index for the active holder | Yes | No | Reject a second active code | Extract lists duplicates as exceptions | Partial unique index. No all-history unique index |
| Punch identity | Partial unique index for one active value per company | Yes | No | Overlap insert is explicit | Duplicate cards are exceptions until an overlap row is approved | Partial unique index plus `punch_identity_overlaps` |
| Effective-date overlap | PostgreSQL exclusion constraint, half-open range | The exclusion constraint is the index | MySQL trigger only if PostgreSQL is rejected | Check before insert so the user sees a clear error | Migration overlap is an exception, not a silent pick | Exclusion constraint. Query order is not the rule |
| One effective shift per employee and `work_date` | Unique current pointer on the day result, which cites one resolution | Yes | No | Resolution writer inserts one row per calculation | Historical load cites the shift stored on the day. It does not decide BD-D03 | Unique current pointer. Candidates may be plural |
| Attendance versions | Foreign keys along `previous_calculation_id` and `previous_version_id`. Insert only | Lookup by company, employee, `work_date` | Trigger refuses update of a version body | Service inserts the next version | — | Append-only versions plus trigger |
| Correction history | Unique (`correction`, `value_stage`, `field_key`). Foreign key to the original version | Yes | Trigger refuses update of an existing value row | Service appends stages | Missing legacy pre-image stays a migration limitation | Unique stage key plus append-only trigger |
| Approval order | Unique (`version`, `sequence_no`) and the same on the instance copy | Yes | Trigger refuses a change to a copied step | Service copies steps at start | No seeded Proedge or VTL step list | Declarative uniqueness |
| Payroll snapshot immutability | No | — | Trigger refuses update or delete of a finalized result and of its children | Service has no update method for finalized rows | Reconciliation compares stored amounts | Trigger plus service. Service alone is not enough |
| Raw punch immutability | No | Non-unique `(company_id, punch_identity_id, event_at)` | Trigger refuses update or delete of `event_at` and identity. Withdrawal is a status update of `withdrawal_status` only, if the specification’s withdrawal column is treated as the one allowed change | Ingest inserts. Correction never updates the event | — | Trigger. Withdrawal is the specified status, not a delete |
| Finalized payroll protection | Same as snapshot immutability | Unique current result | Same trigger | Attendance recalculation does not open a finalized result | — | Trigger. No cascade from attendance |

Leave-request date overlap stays unconstrained. The specification says that constraint is not invented.

`policy_values` overlap uses the same exclusion constraint as other effective-dated rows: one open row per policy, company, scope, and precedence.

## 9. Immutability Strategy

Already decided as a rule (A): raw punches are immutable, and a finalized payroll snapshot does not change when attendance is later recalculated. ADR-008 and AP-05. The mechanism is not decided.

| Mechanism | What it actually stops | Limit |
|---|---|---|
| Database privileges | A role that lacks `UPDATE` and `DELETE` | A migration role with those privileges can still write. Privileges are not a row rule |
| Triggers | Any session that updates a punch body or a finalized snapshot | Must list the one allowed change: punch `withdrawal_status`, and the pointer move on `attendance_day_results` |
| Append-only table design | The application inserts a new version instead of editing the old one | A second writer can still issue `UPDATE` unless a trigger exists |
| Application service | The normal product path | It is not equivalent to database immutability. This decision does not accept it alone |
| Audit row | Records that a change happened | It does not prevent the change |

**Recommendation:** combination.

1. Insert-only tables have no `updated_at`, as the schema specification already says.
2. A trigger rejects `UPDATE` and `DELETE` on `punch_events` except a change to `withdrawal_status`.
3. A trigger rejects `UPDATE` and `DELETE` on result versions, correction value rows, approval actions, and on `payroll_results` plus children once `finalization_state` is `finalized`.
4. The application services only insert. That is the normal path. It is not the control.
5. `audit_events` records ingestion, withdrawal, finalization, and reopen. The audit row does not replace the trigger.

**Approval:** required.

Punch `withdrawal_status` is the specified way to retire an event without deleting it. The trigger must allow that column and refuse every other change. That is an implementation of the specification, not a new business rule.

## 10. Effective-Date Strategy

Already decided (A): intervals are half-open, `[effective_from, effective_to)`. An open interval has no `effective_to`. Overlap at the same precedence is rejected. Lookup returns the single containing interval. Scan order is not a rule. Specification sections 7 and 13.

**Recommendation (B + C):**

- Store `effective_from` as an inclusive date and `effective_to` as an exclusive date.
- In PostgreSQL, add an exclusion constraint on the identity columns plus a half-open `daterange`.
- The application checks the same predicate inside the transaction before insert, so the error can name the conflicting row.
- The transaction takes a row lock on the parent employee, shift, or policy key before inserting a new interval, so two concurrent requests cannot both pass the check.
- A closed interval is not updated. A change inserts the next interval.

If the approved engine is MySQL instead, the exclusion constraint is unavailable. The substitute is a trigger that locks the parent key and rejects overlap. That substitute is valid only if MySQL is the approved engine. It is not the recommendation.

No grace, cutoff, or other policy date is stored by this strategy.

## 11. Company Isolation Strategy

Already decided (A):

- One logical schema for every company.
- `company_id` on operational rows.
- A separate database per client is not the target tenancy model.
- `db_alias` and `previous_db_id` are not target columns.
- No `proedge_employees`, `vtl_employees`, or `atlas_employees` tables.
- UI filtering is not the isolation mechanism.

| Layer | Recommendation | Class |
|---|---|---|
| Database | `company_id` not null on DIRECT and INDIRECT tables. Composite foreign keys so a child cannot cite a parent from another company. Global tables remain `policy_definitions`, `extension_catalog`, and product-scope `policy_values` | Rule is A. Composite key technique is B + C |
| Repository | Every operational query takes a company id and applies it in the SQL predicate, including queries that also join on a surrogate id | B + C |
| Service | A write refuses a payload whose company id differs from the authorized company. Cross-company ids are not repaired | B + C |
| Authorization | The server checks the company before the action. Role tables stay deferred, so Step 20 does not invent them. Until they exist, company membership still has to be a real check in the service, using `users` and the binding table | A for the principle. The later role schema is its own approval |

A future deployment may put the same schema on more than one server. That placement does not remove `company_id`.

## 12. Migration Implementation Strategy

The path is already decided (A) in the migration architecture:

```
READ-ONLY SOURCE
→ EXTRACT
→ TRANSFORM
→ VALIDATE
→ LOAD
→ RECONCILE
→ APPROVE
→ FINAL DELTA
→ CUTOVER
```

`SOURCE COUNT = LOADED + APPROVED EXCEPTIONS`. One company at a time. Local audit databases are not the production baseline. Historical payroll is not recalculated. Punch timestamps stay unchanged. Historical `work_date` is the stored attendance date.

The code location is not decided, because there is no project (B + C).

| Topic | Recommendation |
|---|---|
| Staging | A `staging` schema in the same PostgreSQL database, emptied per run. Not a legacy copy inside the core tables. Not a second product schema |
| Code location | A migration module in the future Laravel project, separate from HTTP controllers |
| Traceability | Every loaded core row that came from a legacy row carries `source_system` and `source_key`. A run table records snapshot id, mapping version, and company |
| Run identifier | One id per company per attempt. A repeat of that version replaces that version’s loaded rows for that company |
| Exception register | A staging table with company, source reference, reason, severity, disposition, resolver, and time. Empty disposition does not satisfy the equation |
| Repeatability | Same snapshot id and mapping version produce the same counts. No hand edits in the target |
| Rollback before cutover | Discard that company’s loaded version and rerun. Do not update the legacy source |
| Company execution | Company 24, 20, and 10 are separate runs. Sibling aliases are not included |
| Baseline and delta | Production cutover uses a new read-only snapshot. Freeze length stays undecided. The delta uses the same mapping version |

No migration code is written here. No legacy database is connected. No rows are loaded.

The first rehearsal after Step 20 remains Proedge, company 24, August 2026, and only after the physical schema exists. That order was a rehearsal recommendation. It is not a template decision.

## 13. Project Structure

Not created.

**Recommendation (B + C):** a new project root beside the legacy tree, not inside it.

```
D:\Project -All\Proedge HRMS\next-hrms\
  backend\                 Laravel application
    database\migrations\   the only place Step 20 creates tables
    database\seeders\      empty of company data
    tests\                 schema constraint tests
  frontend\                React, added when the UI is approved
  migration\               extract, transform, reconcile, exception register
  extensions\              enablement only. Atlas Bills and NMS are not core modules here
  config\                  environment files. No production credentials
  docs\                    link or copy of the approved Universal HRMS documents
```

The legacy Yii2 tree stays the read-only source. Design documents may remain in the current `docs` folder until the new root exists. Step 20 should not put Laravel migrations inside `legacy-reference/hrms`.

Deployment configuration is a later decision. This step does not choose a host.

## 14. Development Environment

| Tool | Current environment on this workstation | Target requirement | Class |
|---|---|---|---|
| PHP | 8.3.30 | 8.3 or newer for the Laravel candidate | Current is sufficient. Target version is B + C with the framework approval |
| Composer | 2.8.8 | Composer 2 | Current is sufficient |
| Node.js | 22.22.3 | Current LTS, only when the React UI starts | Not blocking for Step 20 |
| npm | 12.0.0 | npm with that Node | Not blocking for Step 20 |
| pnpm | Not installed | Not required | Do not add it unless the UI approval chooses it |
| Git | 2.49.0 | Git | Current is sufficient |
| MySQL | 8.4.3, in Laragon | Not the recommended target engine | Current legacy and audit server. Do not point the new schema at a legacy database |
| PostgreSQL | Not installed. No `psql` and no Windows service found | Current stable PostgreSQL, if the engine recommendation is approved | D until installed. Blocking for Step 20 if PostgreSQL is approved |
| Laravel | Not installed | The current Laravel release that supports PHP 8.3 | B + C. Version pin waits for approval |
| Test database | None for the new product | A second PostgreSQL database, named so it cannot be mistaken for `legacy_proedge` or production | B + C |
| Local application database | `new_proedge_hrms` does not exist | Created only in Step 20, after approval, by migrations | Not created now |

Local tools do not become architecture decisions by being installed. MySQL 8.4 is the audit engine. It is not selected as the product engine by this document.

## 15. Versioning Strategy

Already decided as a rule (A): a historical result is explainable from source facts, the effective policy, and the calculation version. No physical store was designed in the architecture assessment. The schema specification then named the tables. This step says how software uses them. It does not implement them.

| Subject | Representation | Where |
|---|---|---|
| Database migrations | Ordered Laravel migration files in Git. One migration run builds an empty database | Future `backend/database/migrations` |
| Policy and configuration | Effective-dated `policy_values`. A later insert does not change an old row | `policy_citations` on the calculation or payroll run store the winning value id |
| Attendance calculation logic | `attendance_calculations.generation_kind` and `calculated_at`. A code release is identified on the audit event for a product-native run | New calculation row. Old version remains |
| Payroll calculation logic | `payroll_runs.run_sequence`. A finalized result stores the run id | New run. Old finalized result remains |
| Payroll snapshot | `payroll_results` with `finalization_state` and `is_current` | Children hold the copied amounts and inputs |
| Historical reproducibility | Loaded history keeps source tokens, source amounts, and source `work_date`. Go-forward runs cite policy versions. They do not rewrite loaded rows | Mapping workbook plus schema |

Calculation code itself is not versioned by copying policy numbers into code. A release may change a future run. It cites the policy row it used. D01–D18 values stay empty until a business decision fills `policy_values`.

## 16. D01–D18 Governance

No business value is chosen.

| ID | Subject | Class | This step |
|---|---|---|---|
| BD-D01 | Grace length | Business decision | Open. Storage is a policy value, not a column of minutes |
| BD-D02 | Incomplete-punch outcome | Business decision | Open. `incomplete_condition` stays separate from `status_code` |
| BD-D03 | Which shift wins | Business decision | Open. Candidates load. The winner value stays empty |
| BD-D04 | When late measurement starts | Business decision | Open |
| BD-D05 | Early-out pay | Business decision | Open |
| BD-D06 | Leave pay | Business decision | Open. Pay class stays empty |
| BD-D07 | Absence pay | Business decision | Open |
| BD-D08 | OT eligibility | Business decision | Open. `eligible_for_ot` is not a column |
| BD-D09 | OT formula | Business decision | Open |
| BD-D10 | OT rounding | Business decision | Open |
| BD-D11 | Separate OT approval | Business decision | Open. The approval link stays optional |
| BD-D12 | OT rate and payment | Business decision | Open. Paid money is a snapshot amount |
| BD-D13 | Attendance cutoff | Business decision | Open. Period events exist. None are seeded |
| BD-D14 | Payroll close | Business decision | Open. Finalized historical results are not a close policy |
| BD-D15 | Who may reopen | Deferred | Remains deferred. No reopen role |
| BD-D16 | Which correction fields may change | Business decision | Open. `field_key` is not a fixed list |
| BD-D17 | Approval sequence | Business decision | Open. No boss columns and no seeded steps |
| BD-D18 | Optional snapshot lines | Business decision | Open. The optional-fact table has no required key |

Technical choices in this document, such as an exclusion constraint or a trigger, are implementation mechanisms. They do not set grace, leave pay, absence pay, an OT formula, a rate, rounding, a hierarchy, or a cutoff.

## 17. Decision Register

| ID | Decision | Current status | Source | Recommendation | Approval required | Blocking? |
|---|---|---|---|---|---|---|
| TD-01 | One schema, `company_id` tenant, no database-per-company product | A. Decided | ADR-002, physical architecture section 3 | Keep | No | No. Already binding |
| TD-02 | ULID logical primary key. Business codes are attributes | A. Decided | Schema sections 2 and 5 | Keep | No | No |
| TD-03 | Database engine | B. Recommended | ADR-016 deferred it | PostgreSQL | Yes | Yes |
| TD-04 | Backend framework and migration tool | B. Recommended | ADR-016 deferred it. No project exists | Laravel | Yes | Yes |
| TD-05 | Frontend framework | B. Recommended | Requirements section 5 did not select it | React, after the schema | Yes | No for Step 20 |
| TD-06 | ULID physical storage | B. Recommended | Physical architecture section 21 deferred the format | 26-character ASCII text | Yes | Yes |
| TD-07 | Money and duration column types | D. Needs a type, not a business rule | Schema names `amount`, `net_pay`, `late_duration`, `early_duration` and no SQL type | `numeric` for money and an integer count of seconds for durations, labeled as storage, not as BD-D01 or BD-D10 | Yes | Yes |
| TD-08 | `value_payload` physical type | B. Recommended | Schema says the payload exists and stays empty of decisions | `jsonb` | Yes | Yes |
| TD-09 | Timestamp type | B. Recommended | `event_at` must keep the real timestamp | `timestamptz` | Yes | Yes |
| TD-10 | Foreign keys | A for the rule. B for declarative SQL | Schema section 6 | Declarative foreign keys | Yes for the SQL form | Yes |
| TD-11 | Company match on child rows | B. Recommended | Schema section 6 requires the match and does not name the mechanism | Composite foreign keys | Yes | Yes |
| TD-12 | Effective-date overlap | A for the rule. B for the mechanism | Schema section 7 | PostgreSQL exclusion constraints, plus a locking check in the transaction | Yes | Yes |
| TD-13 | Punch and finalized-payroll immutability | A for the rule. B for the mechanism | ADR-008, AP-05 | Triggers. Application services are not a substitute | Yes | Yes |
| TD-14 | Active employee-code uniqueness | A for the rule | Schema section 15. Reuse after separation is open | Partial unique index. No all-history unique index | Yes for the index predicate | Yes |
| TD-15 | Queue broker | B. Recommended | Architecture defers Redis and vendors | Database queue. No Redis | Yes | No for Step 20 |
| TD-16 | Role and permission tables | A. Deferred | Schema section 19 | Omit from Step 20 | Later, separate approval | No |
| TD-17 | Project root `next-hrms` beside the legacy tree | B. Recommended | Step 18 found no project | Create only after approval, and not in this step | Yes | Yes |
| TD-18 | PostgreSQL installed for development | D. Absent locally | This workstation has MySQL 8.4.3 and no `psql` | Install only after TD-03 is approved | Yes | Yes if TD-03 is approved |
| TD-19 | Migration staging schema and exception register | B. Recommended | Migration architecture specifies the register fields and does not specify tables | Staging schema, not core tables | Yes | No for empty schema creation. Yes before a data rehearsal |
| TD-20 | D01–D18 values | A. Open. D15 deferred | All approved documents | Do not fill `policy_values` | Not available to this step | Must stay empty |
| TD-21 | Unknown actor | A. Decided as a marker, not a fabricated user | Schema section 2. `acting_user_id` optional for migration | Null actor on migrated rows. No sentinel user invented here | No new column | No |
| TD-22 | Leave-request overlap | A. Not a constraint | Schema section 15 | Do not add one | No | No |

## 18. Open Questions

| Question | Why it is still open |
|---|---|
| Will the project approve PostgreSQL rather than MySQL 8.4? | TD-03. Familiarity favors MySQL. The overlap rule favors PostgreSQL |
| Exact `numeric` scale for money | TD-07. A scale is storage. It must not become a rounding policy |
| Whether duration integers are seconds | TD-07. The Proedge audit stored seconds. That evidence is not a universal unit |
| Laravel release number | TD-04. Pin the release when the project is created |
| Who approves TD-03, TD-04, TD-06, TD-12, and TD-13 | No approver role is named in the architecture |
| Production hosting, backups, and replication | Deferred on purpose |
| Freeze length for cutover | Migration architecture leaves it undecided |

## 19. Approval Requirements

Before Step 20, a project approver needs to accept or replace each blocking recommendation:

1. TD-03 engine.
2. TD-04 Laravel as the migration home.
3. TD-06 ULID text storage.
4. TD-07 money and duration storage types.
5. TD-08 `jsonb` payload.
6. TD-09 `timestamptz`.
7. TD-11 composite company foreign keys.
8. TD-12 exclusion constraints.
9. TD-13 immutability triggers.
10. TD-14 partial unique index for active employee codes.
11. TD-17 project root.
12. TD-18 install PostgreSQL if TD-03 stays PostgreSQL.

Approval of this file is the act that turns a recommendation into a decision. This file does not perform that act.

Frontend, Redis, role tables, and staging tables can wait until after the empty schema exists. They do not unblock or block the first migration files, except that the migration files need a project root and an engine.

## 20. Implementation Readiness Gate

**NOT READY FOR STEP 20**

Step 20 may create `new_proedge_hrms` and the approved tables only after section 19 is approved and, if PostgreSQL remains the choice, after PostgreSQL is installed. Until then, creating tables would repeat the Step 18 failure: a physical encoding chosen without approval.

D01–D18 do not have to be decided before the empty schema. They must remain empty in that schema.

## 21. Recommended Next Step

Record an explicit approval, or a written rejection, of the blocking rows in section 17. Then start Step 20 as schema implementation only: create the project root, add Laravel migrations, apply them to an empty database, and repeat them on a disposable database.

Do not migrate Proedge, VTL, or Atlas in that step. Do not seed employees. Do not decide D01–D18.

### Safety statement

| Item | Result |
|---|---|
| Production modified | NO |
| Legacy databases modified | NO |
| Legacy data imported | NO |
| `new_proedge_hrms` created | NO |
| Tables created | NO |
| Migration code executed | NO |
| Application created | NO |
| D01–D18 business decisions changed | NO |
| Migration approval granted | NO |

### Verification

| Check | Result |
|---|---|
| Recommendations distinguished from approved decisions | Yes. Section 17 |
| Engine not selected merely because the legacy system uses MySQL | Yes. Section 4 |
| ULID logical rule separated from storage format | Yes. Section 6 |
| Business codes are not primary keys | Yes. Section 7 |
| Immutability is not left to the application alone | Yes. Section 9 |
| Overlap does not depend on query order | Yes. Section 10 |
| Separate company databases are not the target model | Yes. Section 11 |
| D01–D18 unchanged. D15 deferred | Yes. Section 16 |
| Readiness is exactly one gate value | NOT READY FOR STEP 20, as of this Step 19 assessment. The current engine approval is section 22. The current Step 20 gate is `docs/UNIVERSAL_HRMS_STEP20_PHYSICAL_IMPLEMENTATION_READINESS.md` |
| Database, tables, migrations, and application created | No |

## 22. Current approved database target

Recorded after the technical-direction approval and the local PostgreSQL 18.6 validation. This section does not add a schema rule, a column type, a business value, or a migration.

| Item | Current decision |
|---|---|
| Approved target database | PostgreSQL 18.x |
| Physical implementation target | PostgreSQL |
| Legacy production database | MySQL, read-only source of truth for the current target architecture |
| Production changes | Not authorized |
| HRMS target database or tables on production | Not authorized |
| Local development server | PostgreSQL 18.6, service `postgresql-x64-18`, port 5432. `psql` login and a PHP 8.3.30 PDO connection were confirmed. This section does not create a database |

Items this section does not approve, because they were not part of the later approval list: money precision and scale, duration storage unit, JSON versus JSONB, `timestamptz` versus other time types, composite foreign keys, the active employee-code index predicate, index SQL, and partitioning. Step 19 left those as recommendations or explicit deferrals. They stay that way.

The Step 19 gate in section 20 remains the historical result of that assessment. It is not the current Step 20 gate.

# Universal HRMS — Step 20 Physical Implementation Readiness

Date: 2026-10-09

Status: review only. No database, table, migration, application, or data load was created.

## 1. Technology confirmation

| Area | Current approved decision |
|---|---|
| Target database | PostgreSQL 18.x. Physical implementation target |
| Legacy production database | MySQL, read-only source of truth. No production change. No HRMS database or table on production |
| Backend | Laravel |
| Web | React + Vite |
| First web implementation | Employee Web / ESS |
| Later web areas | Admin, HR, Manager |
| Architecture | Universal HRMS, multi-company. The backend and database are not ESS-only |
| Mobile | Flutter for Android and iOS, later |
| Nree | Reference only. Not the Universal HRMS mobile client |
| ULID | 26-character ASCII text |
| Effective-date overlap | PostgreSQL exclusion constraints |
| Raw punch immutability | Database triggers |
| Finalized payroll immutability | Database triggers |
| Migration tooling | Laravel migrations plus controlled migration tooling |

These approvals are recorded in `docs/UNIVERSAL_HRMS_TECHNICAL_DECISION_APPROVAL_AND_ENVIRONMENT_READINESS.md` and section 22 of `docs/UNIVERSAL_HRMS_TARGET_TECHNOLOGY_PHYSICAL_IMPLEMENTATION_DECISION.md`. This review does not add another architecture decision.

The referenced migration document on disk is `docs/UNIVERSAL_HRMS_MIGRATION_DATA_CONTINUITY_ARCHITECTURE.md`.

## 2. Environment validation summary

Re-checked on this workstation on 2026-10-09. No database was created. No password was read or written.

| Check | Result |
|---|---|
| PostgreSQL version | 18.6 |
| Service | `postgresql-x64-18`, running, Automatic |
| Port | 5432, listening |
| `psql` | `C:\Program Files\PostgreSQL\18\bin\psql.exe`, reports PostgreSQL 18.6. The directory is not on PATH. Login was already confirmed and was not repeated |
| `pg_config` | Present. Reports PostgreSQL 18.6 |
| PHP | 8.3.30. Configuration file `D:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.ini` |
| PDO PostgreSQL | `pdo_pgsql` and `pgsql` are loaded |
| PHP to PostgreSQL | Already confirmed OK. Not repeated in this review |

`new_proedge_hrms` was not created. No HRMS role was created.

## 3. Approved physical decisions already frozen

These are already decided. This review does not reopen them.

| Decision | Where it is frozen |
|---|---|
| One product schema, `company_id` tenancy, no database-per-company product | Architecture and physical data architecture |
| ULID logical primary key. Business codes are attributes | Schema sections 2 and 5. Storage form approved as 26-character ASCII text |
| `work_date` stored apart from punch `event_at`. Cross-midnight supported | Schema sections 4 and 8 |
| Half-open effective intervals. Overlap rejected by the database, not by query order | Schema section 7. Mechanism approved as PostgreSQL exclusion constraints |
| Raw punch row immutable. Withdrawal does not delete it | Schema section 4.13. Mechanism approved as a database trigger |
| Finalized payroll content immutable. A later attendance change does not rewrite it | Schema section 4.28 and section 12. Mechanism approved as a database trigger |
| Child `company_id` matches the parent company. No operational foreign key crosses companies | Schema section 6. The SQL mechanism is not frozen. See section 4 |
| One active employee code per company. No all-history unique key | Schema section 15. The index predicate is not frozen. See section 4 |
| Same-second punches are not unique on timestamp plus identity | Schema section 15 |
| `source_event_id` unique per company and source only when the source provides one | Schema section 15 |
| Leave-request overlap is not a constraint | Schema section 15 |
| Role and permission tables omitted | Schema section 19 |
| Unknown migrated actor is an empty actor reference, not a fabricated user | Schema section 2 |
| D01–D18 values empty. BD-D15 deferred | Schema section 18 |
| Conceptual access paths in schema section 16 | The paths are named. The index SQL is not. See section 4 |

## 4. Remaining physical decisions requiring explicit approval

The schema specification section 19 still defers engine configuration, column types, index implementation, and partitioning. The physical architecture section 21 still defers identifier storage format, partitioning, and DDL. Later approval closed the engine, the ULID text form, the overlap mechanism, and the immutability mechanism. It did not close the items below. No value is chosen here.

| Item | What the documents say | Why DDL cannot invent it |
|---|---|---|
| Money precision and scale | `amount` and `net_pay` are named. No SQL type, precision, or scale. Step 19 TD-07 recommended `numeric` and left the scale open | A scale would be written into every money column |
| Duration storage unit | `late_duration` and `early_duration` are measured durations. `quantity_unit_key` is a label, not a universal seconds-or-hours choice. TD-07 recommended integer seconds and said that unit is not approved | Legacy Proedge seconds are evidence, not the product unit |
| JSON versus JSONB | `value_payload` exists and stays empty. TD-08 recommended `jsonb` and it was not approved | The payload column cannot be created without a type |
| Timestamp, date, and time types | `event_at`, `ingested_at`, `acted_at`, `calculated_at`, `finalized_at`, `work_date`, `effective_from`, `effective_to`, and shift clock fields are named. TD-09 recommended `timestamptz` and it was not approved. Schema section 19 defers column types | Choosing `timestamptz`, `timestamp`, `date`, or `time` is a physical decision |
| Copied and audit value types | `copied_value`, `previous_value`, and `new_value` have no SQL type | Same deferral as the other column types |
| ULID storage | Frozen as 26-character ASCII text | No remaining choice for the primary-key form. Listed so it is not reopened |
| Composite foreign keys | Schema section 6 requires the company match and does not name the mechanism. TD-11 recommended composite foreign keys and it was not approved | A simple foreign key does not, by itself, prove the child’s `company_id` equals the parent’s |
| Active-code unique predicate | One active code per company. Reuse after separation is open. Status is on the employment episode. TD-14 says a partial unique index still needs its predicate | The predicate would decide reuse, which the schema leaves open |
| Effective-date exclusion SQL | The non-overlap rule and the table list are in schema section 7. The approved mechanism is a PostgreSQL exclusion constraint. The constraint expressions are not written | Writing them is implementation of an approved rule, after the date type above is named. The date type is still open, so the constraint SQL is blocked with it |
| Raw-punch trigger column list | The event body is immutable. `withdrawal_status` is a required field and withdrawal does not delete the row. The approval says “database triggers” and does not list which columns an update may change | The trigger has to allow withdrawal and forbid every other change. That column list is not an approved SQL statement yet |
| Finalized-payroll trigger column list | Finalized content is immutable. Schema section 12 says the prior finalized row stays immutable and `is_current` becomes false when a later result exists | A trigger that blocks every update would also block the current-pointer change the schema already describes. The allow-list is not written |
| Required index SQL | Schema section 16 lists access paths and says no engine syntax is specified. Section 19 defers index implementation | B-tree versus another method, and the partial predicates for “where present”, “open interval”, and “current row”, are not approved SQL |
| Partitioning | Physical architecture section 21 and schema section 19 defer it. No partition key is named | Do not add partitions in order to start. Also do not treat “deferred” as permission to choose a key |

Also still not approved, and not required to invent in order to name the blockers above:

| Item | State |
|---|---|
| Declarative foreign-key SQL form (TD-10) | The reference rule is in the schema. The Step 19 register still marks the SQL form as needing approval |
| Queue broker (TD-15) | Recommendation only. Not required for an empty schema |
| Project directory for the Laravel tree (TD-17) | Recommended earlier as `next-hrms`. Not approved. Not created |
| Staging schema for a future data rehearsal (TD-19) | Not part of the empty product schema |
| Replication, backup, sizing, hosting | Deferred. Not a local DDL choice |
| Employee-code reuse after separation | Open in schema section 19 |
| Leave-request overlap | Explicitly not a constraint |
| D01–D18 | Open. BD-D15 deferred |

## 5. Conflicts between existing documents

| Older statement | Later statement | How to read it |
|---|---|---|
| ADR-016, schema section 19, and physical architecture section 21 defer the database engine | PostgreSQL 18.x is the approved target | The later approval decides the engine. Those older sections were not rewritten |
| Schema section 19 and physical section 21 defer ULID storage format | 26-character ASCII text is approved | The later approval decides primary-key storage. UUID is not an approved primary-key form. Schema section 2 still says UUID is acceptable where creation-time order is unnecessary; that sentence was not applied to primary keys |
| Schema section 19 defers the application framework | Laravel, React + Vite, and later Flutter are approved | The later approval decides the clients and the backend. It does not create them |
| Step 19 classified overlap and immutability mechanisms as recommendations | Exclusion constraints and database triggers are approved | The rules were already in the schema. The later approval selects the mechanism |
| Step 19 TD-07, TD-08, TD-09, TD-11, and TD-14 recommend `numeric`, seconds, `jsonb`, `timestamptz`, composite foreign keys, and a partial unique index | The approval document says those recommendations were not approved | A recommendation is not a default. Schema section 19 still defers the types |
| Step 19 and Step 19A gates say NOT READY because PostgreSQL was absent | PostgreSQL 18.6 is installed and PDO is loaded | The environment blocker in those gates is closed. The type and predicate blockers are not |
| Step 19 section 20 says Step 20 may create `new_proedge_hrms` after approval | This review does not authorize that database | No database is created here, including on the local server |

No document conflict was removed by editing the schema specification, the architecture assessment, or the requirements. D01–D18 were not edited.

## 6. Proposed implementation order

This order starts only after the open items in section 4 that block DDL are explicitly approved. It is not authority to start now.

1. Approve money precision and scale, the duration unit, JSON or JSONB, and the date, time, and timestamp types.
2. Approve the company-match mechanism, the active-code predicate without deciding reuse unless that question is explicitly closed, the index SQL, and the trigger column lists for punch withdrawal and payroll `is_current`.
3. Leave partitioning, replication, backup, hosting, role tables, and D01–D18 uncreated.
4. Only then create the Laravel project outside the legacy tree and add migrations for the approved tables.
5. Apply those migrations to an empty local PostgreSQL 18 database that is not production and is not a legacy database.
6. Do not load Proedge, VTL, Atlas, or any other company in that step.

## 7. Risks and blockers

| Blocker | Effect |
|---|---|
| Money scale, duration unit, payload type, and time types are unnamed | The first migration would have to choose them |
| Company-match SQL and the active-code predicate are unnamed | Foreign keys and the employee-code index would enforce a rule the documents have not written as SQL |
| Punch and payroll triggers have no approved column allow-list | A blanket no-update trigger would conflict with withdrawal and with `is_current` |
| Index SQL is deferred | Section 16 paths cannot be copied into `CREATE INDEX` without choosing method and predicates |
| `psql` is not on PATH | Operational inconvenience. The binary exists. Not the schema blocker |
| Laravel project does not exist | Migrations have nowhere to live. Creating the project was out of scope for this review |

Production MySQL stays read-only. Creating the target schema on production is not an available shortcut.

## 8. Status

**NOT READY FOR STEP 20**

PostgreSQL 18.6, port 5432, `psql`, PHP 8.3.30, and PDO PostgreSQL are in place. The physical schema is not ready to implement because money scale, duration unit, JSON or JSONB, timestamp and date types, the company-match mechanism, the active-code predicate, index SQL, and the immutability trigger column lists are still unresolved. Choosing them inside a migration would be a silent physical decision.

No `new_proedge_hrms` database is authorized by this result.

## Safety statement

Production modified: NO

Legacy databases modified: NO

Legacy data imported: NO

Nree modified: NO

new_proedge_hrms created: NO

HRMS database created: NO

HRMS tables created: NO

HRMS migrations executed: NO

HRMS application created: NO

Application source code modified: NO

D01–D18 changed: NO

Migration approval granted: NO

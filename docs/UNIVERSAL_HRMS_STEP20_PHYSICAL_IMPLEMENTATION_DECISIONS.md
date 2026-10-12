# Universal HRMS — Step 20 Physical Implementation Decisions

Date: 2026-10-09

Status: decision record only. No migration, database, table, or application was created.

This document records the physical encoding baseline for the approved target schema. It does not change D01–D18. A business value that the schema leaves empty stays empty.

## 1. Database

**Decision.** The physical database is PostgreSQL 18.x. The validated local server is PostgreSQL 18.6.

**Rationale.** Exclusion constraints and immutability triggers are PostgreSQL behavior. Legacy production MySQL remains the read-only source. It is not the target engine.

**Implementation consequence.** Later migrations target PostgreSQL 18. They are not written in this step. No target database is created on production or on the local server.

**Affected decisions.** Technical approval and section 22 of the technology decision. Schema section 19 had deferred the engine. That deferral is closed for the engine only.

**Unresolved.** None for the engine choice.

## 2. Identifiers

**Decision.** Every primary key is a ULID stored as 26-character ASCII text. The column type is PostgreSQL `text` with a check that the value is exactly 26 characters from the ULID alphabet `0123456789ABCDEFGHJKMNPQRSTVWXYZ`.

**Rationale.** The approved identifier is a ULID in its text form. `uuid`, `bigint`, `integer`, and `bytea` would be a different identifier.

**Implementation consequence.** `character(26)` is not used, because PostgreSQL blank-pads it. Business codes stay attributes. `employee_code`, `company_code`, `head_code`, and the other business codes are not primary keys.

**Affected decisions.** Schema sections 2 and 5. The earlier sentence that UUID is acceptable where creation-time order is unnecessary does not apply to primary keys.

**Unresolved.** None for primary-key storage.

## 3. Money

**Decision.** Monetary values use PostgreSQL `numeric(19,4)`. Floating-point types are not used for money. Display and report rounding follow company currency rules and are not stored as a second money type.

**Rationale.** Money must round on a fixed scale. A binary float cannot be that store. The scale is storage. It is not BD-D10 and not an overtime rate.

**Implementation consequence.** `numeric(19,4)` applies to monetary columns named by the schema: `employee_structure_head_values.amount`, `payroll_result_heads.amount`, and `payroll_results.net_pay`. No sample amounts are inserted.

**Affected decisions.** Schema sections 4.24 and 4.28. Step 19 TD-07 recommended `numeric` and left the scale open. This step closes the scale at 19 digits with 4 decimal places.

**Unresolved.** `calculated_quantity`, `payable_quantity`, and `copied_value` are not declared monetary columns. They do not inherit `numeric(19,4)` from this decision. See the unresolved list.

## 4. Duration

**Decision.** Persisted duration is PostgreSQL `integer`, counted in seconds. Floating-point hours are not a persisted duration. Screens and reports may show hours and minutes.

**Rationale.** The schema names `late_duration` and `early_duration` as measured durations and leaves grace, late start, and early-out pay empty. The unit is storage. It does not choose BD-D01, BD-D04, BD-D05, or BD-D10.

**Implementation consequence.** `attendance` result fields `late_duration` and `early_duration` are `integer` seconds and remain optional. A day-scale duration fits in a 32-bit integer.

**Affected decisions.** Schema sections 4.17 and 18. Step 19 TD-07 had recommended seconds and marked the unit unapproved. This step approves seconds for those duration fields.

**Unresolved.** `overtime_calculations.calculated_quantity` and `payable_overtime.payable_quantity` keep `quantity_unit_key` because the schema says the unit is the company’s formula policy, not a universal seconds-or-hours choice. This duration decision does not convert them to integer seconds.

## 5. JSON

**Decision.** PostgreSQL `jsonb` is used where the schema explicitly defines a flexible payload. That column is `policy_values.value_payload`. Ordinary relational columns stay relational columns.

**Rationale.** Policy values differ by key, and the schema stores that shape in `value_payload` while leaving every value empty. A JSON column is not a place to hide a department, an amount, a punch, or a work date.

**Implementation consequence.** `value_payload` is `jsonb` and stays empty of D01–D18 choices. No other column is converted to `jsonb` by this decision.

**Affected decisions.** Schema section 4.29. Step 19 TD-08 is approved only for that payload.

**Unresolved.** `bypass_policy`, `copied_value`, `previous_value`, `new_value`, and `weekday_set` are not specified as flexible payloads. Their types remain open.

## 6. Temporal types

**Decision.**

| Role | PostgreSQL type | Schema fields |
|---|---|---|
| Real event and audit instants | `timestamptz` | `event_at`, `ingested_at`, `acted_at`, `calculated_at`, `finalized_at`, `started_at`, `action_at`, `boundary_at` |
| Business, work, payroll, and effective dates | `date` | `work_date`, `roster_work_date`, `holiday_date`, `joining_date`, `last_working_date`, `period_start`, `period_end`, `effective_from`, `effective_to` |
| Wall-clock shift schedule times | `time` | `start_time`, `end_time`, `punch_window_start`, `punch_window_end` |

Punch `event_at` and attendance `work_date` stay different columns. A punch is not rewritten onto the business date.

**Rationale.** The schema already separates the calendar instant, the business date, and the shift clock. PostgreSQL types now follow that split. Half-open effective intervals stay half-open: `effective_from` is included and `effective_to` is excluded. An open interval has no `effective_to`.

**Implementation consequence.** Exclusion constraints over effective dates use a half-open `daterange`. Shift clocks do not carry a time zone. Event instants do.

**Affected decisions.** Schema sections 4, 7, and 8. Step 19 TD-09 is approved as this three-way split, not as `timestamptz` on every temporal column.

**Unresolved.** None for the fields listed in the table.

## 7. Company boundary

**Decision.** `company_id` is mandatory on every table the approved model marks as company-scoped. Where two rows must belong to the same company, the foreign key is composite: the parent has a unique key `(company_id, id)`, and the child references `(company_id, parent_id)`. Laravel validation is not the integrity rule.

**Rationale.** Schema section 6 already requires the child company to match the parent and forbids an operational foreign key that crosses companies. A foreign key on `id` alone does not enforce that match.

**Implementation consequence.** Global rows stay without `company_id`: `policy_definitions`, `extension_catalog`, and product-scope `policy_values`. `companies` is the tenant root. Every other same-company reference uses the composite key. Polymorphic references are not given an invented single-table foreign key. See the unresolved list.

**Affected decisions.** Schema section 6. Step 19 TD-11 is approved as this composite foreign-key pattern.

**Unresolved.** `employment_assignments.fact_reference_id` points at a branch, department, designation, group, location node, or shift, and for status it stores a status token. `organization_reference_id` is likewise kind-dependent. One composite foreign key cannot name several parent tables. The schema does not choose a physical form for those references.

## 8. Employee code

**Decision.** Closed by `docs/UNIVERSAL_HRMS_STEP20_FINAL_EMPLOYEE_CODE_DECISION.md`.

- `employee_code` stays on one employee identity inside a company.
- Separation does not release the code.
- Rejoin keeps the same `employees` row, the same code, and a new episode.
- The target constraint is `UNIQUE (company_id, employee_code)` on every row.
- Historical rows stay. They are not deleted to free a code.
- Two identities never share a code in one company.

**Rationale.** The employee row survives separation, and rejoin uses that same identity. An active-only predicate is unnecessary because the code is never released.

**Implementation consequence.** The unique constraint does not read `episode_status`. No `WHERE episode_status` predicate is required.

**Affected decisions.** Schema sections 4.3, 4.4, and 15. LIFE-CORE-008.

**Unresolved.** None for this constraint. A legacy duplicate is an explicit migration exception, not a silent reassignment.

## 9. Effective-dated records

**Decision.** Where schema section 7 says one interval contains a date, the database rejects an overlap with a PostgreSQL exclusion constraint. User-interface checks are not the rule. Half-open dates from section 6 of this document are the range.

**Rationale.** Schema section 7 already lists the grains and says query order is not a rule. The approved mechanism is an exclusion constraint.

**Implementation consequence.** For a closed grain that has no exception table, the constraint is an exclusion on the identifying columns plus `daterange(effective_from, effective_to, '[)')`. A null `effective_to` is an open end. The grains in section 7 are:

| Table | Exclusion grain already specified |
|---|---|
| `employment_assignments` | employee, episode, `fact_kind` |
| `reporting_chains` | employee |
| `shift_versions` | shift |
| `weekly_off_rules` | scope |
| `leave_types` | `type_code` |
| `approval_workflow_versions` | workflow |
| `salary_heads` | `head_code` |
| `employee_structure_assignments` | employee |
| `salary_structure_heads` | structure and head |
| `employee_structure_head_values` | employee and head |
| `policy_values` | policy, company, scope, precedence |
| `company_extension_enablements` | company and extension |
| `user_employee_bindings` | user |

`shift_assignment_candidates` and `punch_identities` are listed in section 7 and are not given a finished exclusion expression below, because their rules contain a gap.

**Affected decisions.** Schema section 7. The overlap mechanism approval.

**Unresolved.**

- `shift_assignment_candidates`: section 7 says the same employee and the same `candidate_kind` do not overlap, while the field text also says “the same kind and precedence.” No precedence column is specified. Default and roster may both cover a date. The exclusion grain is not one statement.
- `punch_identities`: one active identity value per company unless `punch_identity_overlaps` authorizes that interval. A plain exclusion constraint would also reject the authorized overlap. The schema does not name the constraint shape that allows the exception.
- `punch_identity_overlaps`: rows for the same identity must not contradict each other. “Contradict” is not defined as a column predicate.

## 10. Raw punch immutability

**Decision.** After insert, `punch_events` rejects `DELETE` and rejects `UPDATE`, except one transition: `withdrawal_status` may change from accepted to withdrawn. No other column may change in that statement. Withdrawal does not delete the row. A return from withdrawn to accepted is not specified and is rejected.

**Rationale.** The schema says the punch is immutable and never hard-deleted, and that withdrawal keeps the row. The event body is the history. This step allows only that stated transition. Anything else is mutation and is rejected by the trigger. Application code is not the substitute for the trigger.

**Implementation consequence.** Before migrations, the trigger behavior is fixed as follows.

| Operation | Result |
|---|---|
| `INSERT` | Allowed |
| `DELETE` | Rejected |
| `UPDATE` of any column other than `withdrawal_status` | Rejected |
| `UPDATE` of `withdrawal_status` from accepted to withdrawn, with every other column unchanged | Allowed |
| `UPDATE` of `withdrawal_status` from withdrawn to accepted | Rejected |
| `UPDATE` that changes `withdrawal_status` and any other column | Rejected |

**Affected decisions.** Schema section 4.13. The immutability-trigger approval.

**Unresolved.** None for this trigger’s column list.

## 11. Finalized payroll immutability

**Decision.** A finalized payroll snapshot cannot be altered or deleted. The database trigger enforces that. Draft payroll remains mutable until it is finalized. Recalculation of a draft replaces or updates draft rows through the workflow. It does not update a finalized snapshot.

**Rationale.** Schema section 4.28 and section 12 already say finalized content is immutable and that a later attendance change does not rewrite it. The same sections say the prior finalized result remains and its `is_current` flag becomes false when a newer result is current. That flag move is the one finalized-row update the schema describes.

**Implementation consequence.**

| Row | Trigger |
|---|---|
| `payroll_results` with `finalization_state = finalized` | `DELETE` rejected. `UPDATE` rejected except `is_current` changing from true to false, with every other column unchanged |
| `payroll_result_heads`, `payroll_result_inputs`, `payroll_result_optional_facts` whose result is finalized | `UPDATE` and `DELETE` rejected |
| `payroll_results` with `finalization_state = draft`, and children of a draft result | Mutable until finalization |
| `payroll_runs` with `run_state` of `finalized` or `superseded` | `UPDATE` and `DELETE` rejected. The schema calls a completed run immutable and does not describe a later edit from `finalized` to `superseded` |
| `payroll_runs` with `run_state` of `in_progress` or `draft` | Mutable until completion |

Reopen stays BD-D15. This trigger does not authorize a person to reopen a period. A `payroll_period_events` row does not update finalized amounts.

**Affected decisions.** Schema sections 4.27, 4.28, and 12.

**Unresolved.** None for the finalized snapshot trigger. Who may reopen remains deferred and is not a column default.

## 12. Indexes

**Decision.** The lookup indexes are the access paths in schema section 16, and no others. Company scoping stays in those paths where the schema already includes the company. A column does not receive an index only because it exists.

**Rationale.** Section 16 is the approved access plan. Section 19 deferred index SQL. This step adopts that plan as the index list and does not add a speculative list.

**Implementation consequence.** Each section 16 path becomes one PostgreSQL index. Uniqueness is added only where section 15 or section 16 already requires it.

| Table | Columns | Uniqueness already required |
|---|---|---|
| `punch_events` | company, punch identity, `event_at` | No. A second punch in the same second must be storable |
| `punch_events` | company, punch source, `source_event_id` | Unique where `source_event_id` is present |
| `punch_identities` | company, `identity_value`, `effective_from` | The overlap rule is section 9 of this document, not a second unique index |
| `shift_resolutions` | company, employee, `work_date` | The schema’s current-resolution rule |
| `attendance_calculations` | company, employee, `work_date`, `calculated_at` | No extra uniqueness |
| `attendance_day_results` | company, employee, `work_date` | Unique for the current result |
| `attendance_day_results` | company, `work_date` | No |
| `attendance_day_result_versions` | company, employee, `work_date` | No |
| `attendance_selected_punches` | calculation id; and punch event id | No |
| `attendance_corrections` | company, employee, `work_date` | No |
| `approval_instances` | company, subject kind, subject id | No |
| `approval_actions` | instance id, `action_at` | No |
| `overtime_calculations` | company, employee, `work_date` | No |
| `payable_overtime` | company, employee, `work_date`, state | One current row per employee and `work_date` is already a schema rule |
| `employment_assignments` | company, employee, fact kind, `effective_from` | Overlap is the exclusion constraint |
| `policy_values` | company, policy, scope kind, scope id, precedence, `effective_from` | Open-interval uniqueness is the exclusion constraint |
| `payroll_runs` | company, payroll period, `run_sequence` | Unique on period and sequence |
| `payroll_results` | company, payroll period, employee, `is_current` | One current result per employee and period |
| `payroll_result_inputs` | payroll result id | No |
| `audit_events` | company, subject kind, subject id, `acted_at` | No |

Exclusion constraints bring the GiST index PostgreSQL uses to enforce them. That index is the constraint, not an extra reporting index. Employee-code uniqueness is not in this list until section 8 has a predicate.

Partial unique indexes already specified elsewhere in the schema, and included for the same reason, are: `companies.company_code` when present, and organization `code` when present, each inside its company.

**Affected decisions.** Schema sections 15 and 16.

**Unresolved.** The employee-code predicate. The `shift_assignment_candidates` and `punch_identities` exclusion grains in section 9.

## 13. Partitioning

**Decision.** Partitioning is deferred. Step 20 does not partition any table. The primary key, `company_id`, and the business date or event instant stay present on the rows that already have them, so a later partition scheme can be considered from scale evidence.

**Rationale.** Schema section 19 and the physical architecture defer partitioning. No partition key has been approved.

**Implementation consequence.** Tables are ordinary PostgreSQL tables. This decision does not name a partition key, a range, or a list.

**Affected decisions.** Schema section 19. Physical architecture section 21.

**Unresolved.** The future partition key, by design. It is not required to create unpartitioned tables, and it is not chosen here.

## 14. Remaining physical ambiguities

These items are still unresolved. A migration would have to invent them. They are not invented here.

| Item | Why it is still open |
|---|---|
| Active employee-code predicate | Closed. `UNIQUE (company_id, employee_code)`. See `docs/UNIVERSAL_HRMS_STEP20_FINAL_EMPLOYEE_CODE_DECISION.md` |
| `shift_assignment_candidates` exclusion grain | Section 7 and the field text disagree, and no precedence column exists |
| `punch_identities` overlap exception | A plain exclusion constraint would reject an overlap the authorization table exists to allow |
| `punch_identity_overlaps` contradiction rule | “Contradict” has no column predicate |
| `fact_reference_id` and `organization_reference_id` | Polymorphic targets, including a status token. No composite foreign-key parent is named |
| `calculated_quantity` and `payable_quantity` | Schema forbids treating the unit as universal seconds or hours. Money scale and duration seconds do not apply |
| `copied_value` | Required on payroll inputs and optional facts. Not defined as money, seconds, or `jsonb` |
| `previous_value` and `new_value` | Audit fields. Not defined as `jsonb` |
| `weekday_set` | Named on `weekly_off_rules`. No SQL type |
| `bypass_policy` | Present and empty until BD-D17. Not defined as `jsonb` |

D01–D18 values, BD-D15, leave-request overlap, role tables, and partitioning stay out of the schema on purpose. They are not new gaps in the encodings above. They remain empty.

## 15. Final status

**NOT READY FOR STEP 20**

PostgreSQL 18.x, ULID text, `numeric(19,4)`, integer seconds for `late_duration` and `early_duration`, `jsonb` for `value_payload`, the temporal-type split, composite company foreign keys for ordinary parent links, punch-trigger behavior, finalized-payroll trigger behavior, the section 16 index list, and deferred partitioning are recorded.

The approved target schema still cannot be implemented without choosing the items in section 14. Those choices are not made in this document.

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

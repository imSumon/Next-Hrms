# Universal HRMS — Step 20 Final Physical Clarification

Date: 2026-10-09

Status: clarification only. No migration, database, table, or application was created.

This note answers the shift-candidate, punch-identity, polymorphic-reference, and remaining-type questions from the approved documents. It does not choose a D01–D18 value.

## 1. Evidence reviewed

| Document | What it contributes |
|---|---|
| `docs/UNIVERSAL_HRMS_REQUIREMENTS.md` | SHIFT-CORE-002, SHIFT-CORE-003, SHIFT-CONF-001, BD-D03, LIFE-CORE-005, PUNCH-CORE-009, PUNCH-CONF-004, DI-CORE-005, HOL-CORE-001, HOL-CONF-001, APP-CONF-001, BD-D17 |
| `docs/UNIVERSAL_HRMS_ARCHITECTURE_ASSESSMENT.md` | ADR-005. One effective shift per employee and work date after roster/default resolution. Winner remains BD-D03 |
| `docs/UNIVERSAL_HRMS_LOGICAL_DATA_ARCHITECTURE_ASSESSMENT.md` | Section 4.7. Candidates may be many. Resolution yields one assignment. Same-precedence overlap is rejected or deterministically resolved. The winner is not decided |
| `docs/UNIVERSAL_HRMS_PHYSICAL_DATA_ARCHITECTURE_ASSESSMENT.md` | Section 8 and section 9. Same kind and same precedence do not overlap. Default and roster are different kinds. Uniqueness of the resolved row is not uniqueness of candidates |
| `docs/UNIVERSAL_HRMS_TARGET_DATABASE_SCHEMA_SPECIFICATION.md` | Sections 4.8, 4.9, 7, and 15. Field list, the section 7 grain, and the section 15 constraint |
| `docs/UNIVERSAL_HRMS_STEP20_PHYSICAL_IMPLEMENTATION_DECISIONS.md` | The prior encoding baseline and the open list this note narrows |
| `docs/UNIVERSAL_HRMS_STEP20_PHYSICAL_IMPLEMENTATION_READINESS.md` | The earlier gate. This note does not reopen frozen encodings |

## 2. Shift candidate intended grain

`shift_assignment_candidates` is one proposed shift for one employee. It is not the effective shift.

One row is either:

| `candidate_kind` | Grain |
|---|---|
| `default` | One employee, one shift version, one half-open effective interval |
| `roster` | One employee, one shift version, one roster `work_date`. The schema stores that date as `roster_work_date`, with `effective_from` on that date and `effective_to` on the next date |

The dimensions that identify a candidate are `company_id`, `employee_id`, `candidate_kind`, the shift version, and the interval. Competing sources named by the approved documents are only those two kinds. SHIFT-CONF-001 names the employee default and the dated roster. No approved document adds a group shift, a company shift, or an employee-over-group-over-company ladder on this table.

`shift_resolutions` is a different row. It records the one candidate that won for one employee and one `work_date` inside one calculation. Candidates may be plural. Current resolutions may not.

`employment_assignments.fact_kind = shift_default` is a separate employment fact in the schema. This note does not merge it into `shift_assignment_candidates`. Legacy `employees.shift_id` maps to a candidate of kind `default`, not to the resolution row.

## 3. Shift precedence and resolution rule

The supported overlap rule is **(b): same employee, same source/grain, overlapping period.** The source/grain is `candidate_kind`.

| Option | Result |
|---|---|
| (a) Same employee and same period, ignoring kind | Rejected by the documents. Default and roster may both cover one date |
| (b) Same employee, same `candidate_kind`, overlapping period | Supported by schema section 7 and section 15 |
| (c) Employee, work date, and a precedence number | Not supported. Resolution is per `work_date`, but the candidate table has no precedence column and no work-date grain for a default interval |
| (d) Another hierarchy | Not supported. Employee, group, and company are weekly-off scopes (HOL-CONF-001). They are not shift-candidate levels |

A precedence column is not required on `shift_assignment_candidates`.

The field list in schema section 4.8 has no precedence column. Schema section 7 and section 15 state the constraint with `candidate_kind` only: same-kind overlap is rejected, and default versus roster is allowed. The sentences that say “same kind and same precedence” do not define a column, a number, or a hierarchy. The precedence that is actually specified elsewhere is `policy_values.precedence` and the policy key `shift_precedence`. That key is BD-D03. It chooses between the default source and the roster source. It is not a sort order among two defaults.

No employee, group, or company winner ladder is added here.

## 4. Legitimate overlap versus contradiction

Illustration only. E001 is not a loaded employee, and 2026-01-25 is not a company calendar.

**Legitimate change over time.** A default interval that ends on 2026-01-25 and a later default that starts on 2026-01-25 are adjacent half-open intervals. The earlier row does not contain 2026-01-25. LIFE-CORE-005 is this effective-dated change. One default contains the date.

**Intentionally overlapping sources.** A default whose interval contains 2026-01-25 and a roster row for 2026-01-25 may both exist. They are different kinds. The schema allows that. Neither row is invalid because the dates overlap. The calculation writes one `shift_resolutions` row for the candidate that wins. Which kind wins is SHIFT-CONF-001 and BD-D03. The policy value is empty. This clarification does not choose the employee default or the roster.

**Genuinely conflicting assignments.** Two `default` rows for E001 whose intervals both contain 2026-01-25, or two `roster` rows for E001 that both cover 2026-01-25, contradict the same-kind rule. That state is rejected. It is not a second precedence to be sorted at read time.

If both kinds cover the date and the company has no `shift_precedence` value, the stored candidates can still be valid. The winner is **UNKNOWN / BUSINESS DECISION REQUIRED** (BD-D03). The calculation must not pick one by query order.

If only one kind covers the date, that candidate is the only source the schema provides. SHIFT-CONF-001 applies when both a default and a roster exist. It does not, by itself, make a lone covering candidate ambiguous.

## 5. Recommended database enforcement

Use a PostgreSQL exclusion constraint on `shift_assignment_candidates` for `company_id`, `employee_id`, `candidate_kind`, and the half-open `daterange(effective_from, effective_to, '[)')`.

Do not use an exclusion constraint that ignores `candidate_kind`. That would reject the default-plus-roster overlap the schema allows.

Do not resolve the cross-kind winner in the database. The winner is the empty BD-D03 policy value, cited later by `shift_resolutions.policy_citation_id` when a company sets it. `shift_resolutions` keeps its own rule: one current resolution per company, employee, and `work_date`.

## 6. Punch identity overlap rule

One punch identity may be active for more than one employee in the same company only when a `punch_identity_overlaps` row covers that interval.

| Question | Evidence |
|---|---|
| When | PUNCH-CORE-009 and DI-CORE-005. Two active uses at the same time are rejected unless the company records an explicit overlap |
| Authorization record | `punch_identity_overlaps`: `company_id`, `identity_value`, `effective_from`, `effective_to`, and a required `reason`. The schema says rows exist only when a company records one. The row does not name the employees |
| Different question | PUNCH-CONF-004. How many identities one employee may hold is company configuration. It is not this overlap table |
| Specified contradiction | Two `punch_identities` rows in one company with the same `identity_value` and overlapping active intervals, and no authorization row whose interval covers that overlap |
| Unspecified contradiction | Schema section 7 says overlap-authorization rows for the same identity must not contradict each other. No document defines that contradiction as a column test. **UNKNOWN / BUSINESS DECISION REQUIRED** |

A plain exclusion constraint on `punch_identities` is the wrong tool. It would also reject the overlap the authorization table exists to allow.

The safe database rule for the specified case is a constraint trigger in the same transaction: reject a new or changed `punch_identities` interval when another row for that `company_id` and `identity_value` overlaps it, unless a `punch_identity_overlaps` interval covers the overlap. Closed identity rows stay immutable, which the schema already requires.

No constraint is defined here for “authorization rows must not contradict,” because that test is not specified. Application code must not invent one. The service transaction is the same rule as the trigger: the check runs before commit, and a missing authorization rejects the second active mapping.

## 7. Polymorphic reference decision

Both columns are polymorphic. Neither receives a foreign key.

### `fact_reference_id`

`fact_kind` is the discriminator. The schema lists department, designation, location, group, status, and shift_default. The reference points at a branch, department, designation, group, location node, or shift, except status, which is a status token and not a parent row.

| `fact_kind` | Reference |
|---|---|
| `department` | Department row in the same company |
| `designation` | Designation row in the same company |
| `group` | Employee-group row in the same company |
| `shift_default` | Shift row in the same company |
| `status` | Status token. Not a foreign key |
| `location` | **UNKNOWN.** The prose names both a branch and a location node. No document chooses the parent table |

Physical form: `fact_kind` plus `fact_reference_id` stored as text. Entity references use the 26-character ULID text. The status value stays a token. The service transaction checks that the parent exists in the table selected by `fact_kind` and that `company_id` matches. Status has no parent lookup. The approved access path remains (`company_id`, `employee_id`, `fact_kind`, `effective_from`). No additional index is added for the reference column.

### `organization_reference_id`

It is set when `resolver_kind` is `organization_approver`. No document names the organization table or a second discriminator for branch, department, or another node.

Physical form: optional text, no foreign key. `resolver_kind` shows why the column is present. It does not name the parent. A validation lookup and an index on this column are **UNKNOWN** until that parent exists in an approved document. Schema section 16 does not list an access path on this column, so none is added.

## 8. Remaining physical data types

The proposals below are not adopted. They are also not replaced with a different type.

| Proposal | Result | Why |
|---|---|---|
| Overtime quantity as `numeric(19,4)`, read through `quantity_unit_key` | Not confirmed | `quantity_unit_key` is supported. The schema says the unit is the company’s formula policy and is not a universal seconds-or-hours choice. `numeric(19,4)` is the approved money scale. The schema does not give overtime quantities that scale. Scale is **UNKNOWN** |
| `copied_value` as `jsonb` | Not confirmed | The schema’s flexible payload is `policy_values.value_payload`. `copied_value` is a copied fact. Named facts include days, leave, absence, and overtime figures. No document calls that column a JSON payload. Type is **UNKNOWN** |
| Audit `previous_value` as `jsonb` | Not confirmed | The field is the old value when the subject is not itself a version. It is not specified as `jsonb`. Type is **UNKNOWN** |
| Audit `new_value` as `jsonb` | Not confirmed | Same as `previous_value`. Type is **UNKNOWN** |
| `weekday_set` as `smallint[]` values 1–7 | Not confirmed | HOL-CORE-001 requires a weekly-off rule. No document numbers the weekdays or chooses an array. A 1–7 code would decide which number is Monday. **UNKNOWN / BUSINESS DECISION REQUIRED** |
| `bypass_policy` as `boolean NOT NULL DEFAULT false` | Rejected | Schema section 4.20 says the column stays empty until BD-D17, and that empty is not a “no bypass” rule. A not-null false default would store that rule. Type remains **UNKNOWN**. BD-D17 stays open |

## 9. Decisions confirmed

| Topic | Confirmed decision |
|---|---|
| Candidate grain | One employee, one `candidate_kind` (`default` or `roster`), one shift version, one half-open interval. A roster row also stores `roster_work_date` |
| Same-kind overlap | Rejected for the same employee |
| Cross-kind overlap | Allowed. Default and roster may cover the same date |
| Precedence column on candidates | Not part of the table |
| Cross-kind winner | BD-D03, policy key `shift_precedence`. Not chosen here |
| Candidate constraint | Exclusion on company, employee, `candidate_kind`, and the half-open date range |
| Resolved shift | One current `shift_resolutions` row per company, employee, and `work_date` |
| Punch overlap | Allowed only while a `punch_identity_overlaps` row covers the interval |
| Punch constraint | Constraint trigger, not a plain exclusion on `punch_identities` |
| `fact_reference_id` | Polymorphic text. Discriminator `fact_kind`. No foreign key. Status is a token |
| `organization_reference_id` | Polymorphic optional text. No foreign key. Parent entity not named |

## 10. Decisions still requiring business approval

| Item | Mark |
|---|---|
| Which source wins when a default and a roster both cover E001 on 2026-01-25 | UNKNOWN / BUSINESS DECISION REQUIRED. BD-D03 |
| Whether an `inactive` episode still holds `employee_code` | UNKNOWN. Unchanged from the encoding baseline |
| What makes two `punch_identity_overlaps` rows contradict | UNKNOWN |
| Parent table for `fact_kind = location` | UNKNOWN |
| Parent entity for `organization_reference_id` | UNKNOWN |
| Overtime quantity precision and scale | UNKNOWN |
| `copied_value` type | UNKNOWN |
| Audit `previous_value` and `new_value` types | UNKNOWN |
| `weekday_set` representation and weekday numbering | UNKNOWN / BUSINESS DECISION REQUIRED |
| `bypass_policy` type | UNKNOWN. BD-D17. Boolean default false is rejected |
| D01–D18 values other than the winner rule named above | Unchanged and open. BD-D15 deferred |

## 11. Final Step 20 readiness status

**NOT READY FOR STEP 20**

The shift-candidate exclusion grain and the punch-identity trigger rule are now specified. The schema still cannot be created while the items in the blocking list have no approved SQL type or predicate.

## Blocking list

These are the decisions that still block physical schema implementation:

1. Active employee-code predicate.
2. Overtime quantity precision and scale.
3. `copied_value` type.
4. Audit `previous_value` and `new_value` types.
5. `weekday_set` representation.
6. `bypass_policy` representation.
7. Parent table for a location employment fact.
8. Parent entity for `organization_reference_id`.

BD-D03 blocks choosing a winner when both shift sources exist. It does not block storing the candidate rows or the empty policy key.

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

D01–D18 changed: NO

Migration approval granted: NO

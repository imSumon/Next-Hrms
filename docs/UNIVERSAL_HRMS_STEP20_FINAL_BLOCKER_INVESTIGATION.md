# Universal HRMS — Step 20 Final Blocker Investigation

Date: 2026-10-10

The employee-code finding in section 1 is closed by `docs/UNIVERSAL_HRMS_STEP20_FINAL_EMPLOYEE_CODE_DECISION.md`. Codes are not reused inside a company. The investigation text below is the evidence from before that decision.

Status: investigation only. No migration, database, table, or application was created.

D01–D18 are unchanged. BD-D15 stays deferred.

## 1. Employee-code reuse

### Evidence reviewed

Schema section 4.3. `employees` has `company_id`, `employee_code`, and `display_name`. It has no status column. Identity is immutable. The row is retained after separation. Uniqueness is one active holder of `employee_code` per company. Reuse after separation is not decided, so there is no all-history unique constraint. Code change is an audited event.

Schema section 4.4. `employment_episodes` is a joining-to-separation span. `episode_status` is Open, inactive, or separated. Rejoin inserts another episode on that employee. One open episode per employee. Episodes are retained.

EMP-CORE-003 requires active, inactive, and separated as separate states. LIFE-CORE-006 is the inactive or suspended state. LIFE-CORE-007 records separation and the last working date. LIFE-CORE-008 requires rejoining as a new episode without erasing the previous episode. The logical model says the employee master survives separation.

Migration architecture, employee-code row: preserve the code text, check active uniqueness, and do not merge two people because a code was reused until the business says so. Reuse itself is not decided.

### Finding

Employee identity and employment episode are separate. Identity is the `employees` row and its ULID `id`. Separation, inactivity, and an open spell are `employment_episodes.episode_status`, not a column on `employees`.

The migration architecture does not explicitly permit reuse. It leaves reuse undecided and forbids merging two people when a code was reused.

The decision under consideration, “a code may be reused after separation, and the internal identity is never reused or merged,” agrees with the ban on merging. It is not compatible with the approved lifecycle as a uniqueness rule that can be implemented now.

`employee_code` is stored on the identity row, and that row survives separation. LIFE-CORE-008 then adds a new episode to that same identity. One active holder per code is already required. If a second identity has received the code while the first identity was separated, the first identity’s rejoin produces two active holders of one code. The approved documents do not say which side yields: the rejoin, or the second person’s code. They also do not move `employee_code` off the surviving identity onto the episode.

Inactive is not separation. LIFE-CORE-006 keeps that person in the company. A uniqueness rule that freed the code on inactive would contradict that split. The considered decision does not ask for that.

### Recommended physical representation

None is approved.

A safe rule, if a later business decision both allows reuse and states what happens on rejoin, would be:

- `employees.id` remains the identity. It is never updated to another person and never merged.
- Historical `employees` rows stay, including their stored code.
- There is no unique constraint on `(company_id, employee_code)` across every row.
- At most one identity in the company that is not separated holds a given code.
- “Not separated” cannot be read from `employees`. It is the absence of a fully separated episode state: an episode with status Open or inactive, or no episode yet, still holds the code.
- That rule needs a constraint that can see `employment_episodes`. A partial unique index on `employees` alone cannot see it.

That rule is not adopted here, because rejoin after the code had been issued to a second identity is unresolved.

### Migration impact

Source codes stay text. Two people who share a code are not merged. Active-code collisions stay exceptions. A load must not invent a reuse or a rejoin winner.

### Business impact

Whether a separated person’s code may be issued to someone else remains open. Rejoin of the same person remains required. Those two are not yet jointly defined.

### Status

BUSINESS DECISION REQUIRED

## 2. weekday_set

### Evidence reviewed

HOL-CORE-001 requires a weekly-off rule. HOL-CONF-001 allows that rule to vary by group and, as an exception, by person. Schema section 4.10 stores the days in `weekday_set` and does not number them. The logical model refuses to keep a legacy photo attribute as the weekly-off design and says a photo text becomes a rule only after its meaning is confirmed. No approved requirement, architecture, logical model, physical model, or target schema states a different weekday numbering, including Sunday as 1 or zero-based numbers.

### Finding

No approved document contradicts ISO weekday numbers. The numbering is storage for the days a rule names. It does not choose which days a company has off.

### Recommended physical representation

`weekly_off_rules.weekday_set` is a PostgreSQL `smallint[]`. Each element uses ISO numbering: 1 Monday, 2 Tuesday, 3 Wednesday, 4 Thursday, 5 Friday, 6 Saturday, 7 Sunday. A check constraint rejects an element outside 1–7. No other numbering is introduced.

Confirming that a legacy photo or `weekly_holiday` text means Friday and Saturday is still a migration confirmation. It is not a second storage convention. Friday is 5 and Saturday is 6 when that text is confirmed.

### Migration impact

Unconfirmed weekday text is still not loaded. Confirmed names map onto these numbers. This investigation does not load them.

### Business impact

No company weekly-off policy is chosen. D01–D18 are unchanged.

### Status

APPROVED — ISO weekday numbering 1–7

## 3. Location employment fact

### Evidence reviewed

EMP-CORE-004 stores location as an effective-dated employment fact, with department, designation, group, and shift default. LIFE-CORE-002 records a transfer of location as an effective-dated change.

ORG-CORE-004 requires a branch or site under a company. ORG-CONF-001 says the company chooses location-tree depth. Unit, floor, and line are optional depth, not a universal model.

Schema section 4.2 defines `branches`. `location_nodes` is optional, has the same base fields, and requires `branch_id`. It exists for extra depth. Schema section 4.5 sets `fact_kind` to department, designation, location, group, status, or shift_default. There is no `branch` fact kind. `fact_reference_id` is described as the branch, department, designation, group, location node, or shift cited by that kind.

The physical model lists branch as the site and the optional location node as extra depth under the branch. The employment fact’s relationship is “organization node or shift definition.”

### Finding

`branches` and `location_nodes` are hierarchical, not two names for one entity. A location node, when the company uses that depth, belongs to a branch.

`fact_kind = location` is the employment fact. The approved schema does not say whether `fact_reference_id` for that kind points at `branches` or at `location_nodes`. The reference sentence names both. No foreign key can be created without choosing one of them.

### Recommended physical representation

No parent table is selected. No foreign key is created. `fact_kind` remains the discriminator, and `fact_reference_id` remains unresolved for `location`.

### Migration impact

Legacy site or factory values are not pointed at `branches` or `location_nodes` by this investigation.

### Business impact

A location transfer is still required. The row it cites is not identified.

### Status

UNKNOWN

## 4. organization_reference_id

### Evidence reviewed

APP-CORE-002 allows a step to be a role, a reporting-line manager, an organizational approver, or a named fallback. APP-CORE-003 records the acting user on the approval action. That actor is a user, not this column.

Schema section 4.20:

| `resolver_kind` | Column that is set |
|---|---|
| `role` | `resolver_role_key`. The role table is deferred |
| `reporting_manager` | `reporting_depth` into the reporting chain. This is not a boss column |
| `organization_approver` | `organization_reference_id` |
| `named_fallback` | `fallback_user_id` |

`organization_reference_id` is optional and is set when the kind is organization approver. No document names its parent as company, department, employee, position, location, role, or any other table. `company_id` on the step is the tenant, not this reference. Role and user already have their own columns. Reporting already uses depth, not this id.

### Finding

The column is intentionally without a declared parent. It is polymorphic in the sense that the schema names the step kind and does not name the row the id cites. It is not evidence that the parent is the company table. A foreign key cannot be added, because a foreign key needs one parent table and the approved documents do not provide one.

### Recommended physical representation

Keep `organization_reference_id` optional and without a foreign key. Populate it only when `resolver_kind` is `organization_approver`. Do not add `organization_reference_type`. Do not point the column at `companies`, `departments`, `branches`, `location_nodes`, `employees`, `users`, or a role table.

### Migration impact

Boss and grand-boss values are not loaded into this column. BD-D17 stays open.

### Business impact

An organizational-approver step can be stored. It cannot yet be checked against an organization row.

### Status

UNKNOWN

## STEP 20 READINESS

The blockers listed below were the state of this investigation. They are closed by later decisions:

- Employee-code reuse: `docs/UNIVERSAL_HRMS_STEP20_FINAL_EMPLOYEE_CODE_DECISION.md`
- Location fact and `organization_reference_id`: `docs/UNIVERSAL_HRMS_STEP20_FINAL_DOMAIN_DECISIONS.md`
- `weekday_set`: ISO 1–7, recorded in this investigation

READY FOR STEP 20 PHYSICAL IMPLEMENTATION

Historical finding at the time of the investigation:

**NOT READY FOR STEP 20**

`weekday_set` is no longer a blocker. ISO numbering 1–7 is approved as storage.

These blockers remain:

1. Employee-code reuse after separation. BUSINESS DECISION REQUIRED. The considered rule, code reused and identity never merged, collides with LIFE-CORE-008 because the code stays on the surviving identity and rejoin reactivates that identity.
2. Parent table for the location employment fact. UNKNOWN. `branches` and `location_nodes` are a hierarchy, and the schema does not choose which one `fact_kind = location` cites.
3. Parent table for `organization_reference_id`. UNKNOWN. The column is an organizational-approver reference with no declared parent, so no foreign key is safe.

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

BD-D15 changed: NO

Migration approval granted: NO

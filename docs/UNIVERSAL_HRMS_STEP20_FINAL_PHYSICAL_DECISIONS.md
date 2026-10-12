# Universal HRMS — Step 20 Final Physical Decisions

Date: 2026-10-10

Status: decision review only. No migration, database, table, or application was created.

D01–D18 are unchanged. BD-D15 stays deferred. A proposal is approved here only when the approved documents already require it, or when it fixes a physical encoding without contradicting those documents.

## 1. Employee code

Closed by `docs/UNIVERSAL_HRMS_STEP20_FINAL_EMPLOYEE_CODE_DECISION.md`. The current rule is `UNIQUE (company_id, employee_code)`. Separation does not release the code. The review below records why an earlier “never reuse” proposal was not yet approved. That approval is now recorded.

**Current schema statement.** One active employee in a company holds a given `employee_code`. Reuse after separation is not decided. The schema does not add a unique constraint across all history. Historical employee rows are retained. Rejoining is a new episode and does not erase the previous episode (LIFE-CORE-008).

**Evidence.** Schema sections 4.3, 15, and 19. Physical architecture section 8. Logical integrity: two active employees in one company do not share a code, and reuse after separation is not decided. Migration architecture: preserve the code text, enforce active uniqueness, and do not merge two people because a code was reused until the business says so.

**Proposed physical representation.** Unique `(company_id, employee_code)` on every employee row. No active or inactive predicate. No reuse after separation.

**Business impact.** The same person can rejoin under the same code by adding an episode. A different person cannot receive that code after separation. That second rule is the open decision the documents refuse to close.

**Migration impact.** A source that used one code for two people would fail a full-history unique constraint. The migration rule says to keep that case visible until the business decides. It does not say to reject the second person or to merge the two people.

**Final status.** BUSINESS DECISION REQUIRED

The proposal conflicts with the approved statement that reuse is not decided and that an all-history unique constraint is not added until that choice exists.

## 2. Overtime quantity

**Current schema statement.** `calculated_quantity` and `payable_quantity` are the figures. `quantity_unit_key` is a label from the company’s formula policy. The schema says this is not a universal seconds-or-hours choice. No formula is stored as a number on the quantity row.

**Evidence.** Schema sections 4.22 and 4.23. BD-D09 and BD-D10 stay empty. The approved money type `numeric(19,4)` applies to monetary columns, not to these quantities, unless this record extends it as storage only.

**Proposed physical representation.** `numeric(19,4)`. The unit remains `quantity_unit_key`. The value is not stored as integer seconds and not stored as money.

**Business impact.** Four decimal places are storage capacity. They do not choose the overtime formula, the rounding rule, or the rate. A company unit that needs more than four decimal places is not described in the approved documents.

**Migration impact.** Legacy overtime is not converted into seconds or into a pay amount by this type. Historical rows still wait for a unit reading before they are loaded. This record does not load them.

**Final status.** APPROVED

The type does not contradict the rule that the unit comes from policy. It does not fill BD-D09, BD-D10, or BD-D12.

## 3. copied_value

**Current schema statement.** `payroll_result_inputs.copied_value` is required. A declared input with no copied value blocks finalization. `payroll_result_optional_facts.copied_value` stores a fact only when the company’s policy says to store that fact. The fact list is not fixed (BD-D18).

**Evidence.** Schema section 4.28. The only column the schema describes as a flexible payload is `policy_values.value_payload`. `copied_value` is a copied fact, and the named facts are not all one scalar.

**Proposed physical representation.** `jsonb`. SQL NULL is the absence of a copy. A JSON value, including a JSON null, is a stored copy. Those two states stay distinct. A declared payroll input row that is finalized has a JSON value. SQL NULL on that required copy continues to block finalization.

**Business impact.** The column can hold a number, a duration reading, or a structured fact without choosing which optional facts exist. BD-D18 stays empty.

**Migration impact.** Historical payroll that declared no attendance still has no invented attendance copy. This type does not create those copies.

**Final status.** APPROVED

The schema does not require a text type. `jsonb` does not set a pay rule. SQL NULL remains “no copy,” which is the schema’s block on finalization.

## 4. Audit previous_value and new_value

**Current schema statement.** Both fields are optional. They hold the old and new value when the subject is not itself a version row.

**Evidence.** Schema section 4.30. Versioned business rows remain the history of those facts. These columns are for transitions that are not a new version.

**Proposed physical representation.** `jsonb` on both columns. SQL NULL means no value was stored for that side. The columns are not collapsed into one universal scalar such as text or `numeric`.

**Business impact.** An audit row can record a status token, a date, or a structured change without choosing a business policy. No audit value is invented for D01–D18.

**Migration impact.** Migrated history that has no before-image keeps SQL NULL. A missing original is not filled with an empty object.

**Final status.** APPROVED

## 5. weekday_set

**Current schema statement.** `weekly_off_rules.weekday_set` is the set of weekdays on a company, group, or employee-exception rule. One rule at a scope contains a given date. The schema does not number the days.

**Evidence.** HOL-CORE-001 and HOL-CONF-001. The logical model says a legacy photo attribute becomes a weekly-off rule only where the business confirms the meaning. No approved document defines ISO weekday numbers or a `smallint` array.

**Proposed physical representation.** `smallint[]` with ISO numbers 1 = Monday through 7 = Sunday, plus a check that every element is inside 1–7. Empty array and NULL would stay distinct only if the business model requires that distinction.

**Business impact.** ISO numbering would decide which number is Monday. The approved documents do not make that decision. They also do not say whether an empty set means “no weekly off” and NULL means “not configured.”

**Migration impact.** Legacy weekday names would need a confirmed map before load. This record does not create that map.

**Final status.** BUSINESS DECISION REQUIRED

ISO 1–7 numbering is not established in the approved documents, so it is not finalized.

## 6. bypass_policy

**Current schema statement.** `bypass_policy` is optional. It stays empty until the company decides BD-D17. Empty is not a “no bypass” rule. It means the value is not configured.

**Evidence.** Schema section 4.20. APP-CONF-001. BD-D17 remains the company’s step list and bypass choice, and no list is seeded.

**Proposed physical representation.** `boolean` with no default. NULL means unspecified. `false` means the company explicitly recorded no bypass. `true` means the company explicitly recorded bypass.

**Business impact.** No company receives a no-bypass policy by omission. BD-D17 is still unset for every company. Storing `false` or `true` is a later company choice, not a product default.

**Migration impact.** Legacy approval rows are not backfilled with `false`. An approval with no bypass configuration stays NULL.

**Final status.** APPROVED

This matches the schema sentence that an empty value does not mean no bypass. It does not choose a company’s steps.

## 7. Location employment fact parent

**Current schema statement.** `employment_assignments.fact_kind` includes `location`. `fact_reference_id` may cite a branch, department, designation, group, location node, or shift, depending on kind. Status uses a token. The sentence names both a branch and a location node for location-like references.

**Evidence.** EMP-CORE-004 and LIFE-CORE-002 require an effective-dated location fact. ORG-CORE-004 requires a branch or site. ORG-CONF-001 says location-tree depth is a company choice, and unit, floor, and line are not a universal model. The physical model has `branches` and an optional `location_nodes` row under a branch. The employment fact’s relationship is “organization node or shift definition.”

**Proposed physical representation.** None. No parent table is chosen. No foreign key is created.

**Business impact.** A location transfer still has to be stored as an employment fact. The documents do not say whether that fact points at `branches`, at `location_nodes`, or at whichever of those the company’s depth uses.

**Migration impact.** Legacy location values cannot be pointed at a guessed parent. They stay unmapped until the parent is named.

**Final status.** UNKNOWN

The missing relationship is the single parent table for `fact_kind = location`. Branch and optional location node are both present, and the schema names both.

## 8. organization_reference_id parent

**Current schema statement.** `organization_reference_id` is set when `resolver_kind` is `organization_approver`. The schema does not name a parent table and does not define `organization_reference_type`.

**Evidence.** Schema section 4.20. Resolver kinds are role, reporting manager, organization approver, and named fallback. Reporting uses `reporting_links`, which is a different relationship. Organization structure includes branch, department, designation, group, and optional location node.

**Proposed physical representation.** Keep the column polymorphic: a kind plus `organization_reference_id`, and no foreign key. The kind the schema already defines is `resolver_kind = organization_approver`. That value does not say branch, department, location, or company. A new `organization_reference_type` column is not added here, because the approved schema does not define it.

**Business impact.** An organization-approver step can be stored without a parent lookup. The step cannot yet be checked against an organization row.

**Migration impact.** Boss and grand-boss columns are not loaded into this field. BD-D17 stays open.

**Final status.** UNKNOWN

The parent entity is not identified. No foreign key is created to organizations, companies, departments, locations, or any other table.

## STEP 20 READINESS

**NOT READY FOR STEP 20**

Approved in this record: overtime quantity as `numeric(19,4)` with `quantity_unit_key`, `copied_value` as `jsonb`, audit `previous_value` and `new_value` as `jsonb`, and `bypass_policy` as a boolean with no default.

Still unsupported:

1. Employee-code reuse after separation, and therefore unique `(company_id, employee_code)` across all history. BUSINESS DECISION REQUIRED.
2. ISO weekday numbering for `weekday_set`. BUSINESS DECISION REQUIRED.
3. Parent table for the location employment fact. UNKNOWN.
4. Parent entity for `organization_reference_id`. UNKNOWN.

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

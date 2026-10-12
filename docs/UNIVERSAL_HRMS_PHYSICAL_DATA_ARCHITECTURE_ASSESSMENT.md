# Universal HRMS Physical Data Architecture Assessment

Status: physical data architecture assessment. No migrations, tables, SQL, column types, engine configuration, API, UI, framework, vendor, queue, or deployment are specified.

Step 9 remains frozen. Step 10 remains the logical model. This document does not amend `docs/UNIVERSAL_HRMS_REQUIREMENTS.md`, `docs/UNIVERSAL_HRMS_ARCHITECTURE_ASSESSMENT.md`, or `docs/UNIVERSAL_HRMS_LOGICAL_DATA_ARCHITECTURE_ASSESSMENT.md`.

Proedge behavior is migration evidence. Viyellatex behavior is migration evidence. Neither storage shape is the target.

```
UNIVERSAL HRMS CORE
  + COMPANY-CONFIGURABLE POLICIES
  + OPTIONAL CLIENT EXTENSIONS
```

Classifications: CORE, CONFIGURABLE, CLIENT EXTENSION, LEGACY ONLY, BUSINESS DECISION, UNKNOWN.

Candidate names below are physical responsibilities. They are not `CREATE TABLE` statements.

---

## 1. Purpose

This assessment translates the approved logical model into a physical data architecture that can be implemented later without copying a database-per-client product.

The physical target keeps the logical separations: punch event, `work_date`, day-result version, correction, calculated overtime, payable overtime, paid overtime, and finalized payroll snapshot. Company scope is on the data. Policy is data. History is retained by version, not by overwrite.

## 2. Source documents

| Document | Role in this assessment |
|---|---|
| `docs/UNIVERSAL_HRMS_REQUIREMENTS.md` | Capabilities and the unresolved decisions D01–D18 |
| `docs/UNIVERSAL_HRMS_ARCHITECTURE_ASSESSMENT.md` | Frozen principles AP-01–AP-10 and ADR-001–ADR-017. ADR-002: company isolation is a rule, not a required database per client |
| `docs/UNIVERSAL_HRMS_LOGICAL_DATA_ARCHITECTURE_ASSESSMENT.md` | Domain ownership, cardinality, effective dating, and integrity rules this physical model must store |

## 3. Database and tenancy strategy

### Architectural decision

The product is one Universal HRMS. The company is the primary business boundary (ORG-CORE-001, ORG-CORE-002, ADR-002).

| Choice | Decision |
|---|---|
| Product shape | One logical schema for every company |
| Tenant key | `company_id` on operational records |
| Isolation | Logical tenant isolation enforced at the data-access boundary, on every read and write |
| Branch | A scope inside the company, not a second tenant |
| Database per client | Not required by the architecture. Legacy database-per-client is not copied |
| `db_alias` | Does not exist in the target. It selected behavior in the legacy product |
| `previous_db_id` | Does not exist in the target. It is not an employee attribute and not a tenant key |

A shared schema with `company_id` is the architectural target. UI filtering is not the isolation mechanism.

### Future infrastructure decision

These are not decided here:

- one physical database server or many
- schema-per-deployment for residency or operations
- replication, partitioning, and sharding

If a later deployment places companies in more than one physical database, each database still uses this same logical schema, and every operational row still carries `company_id`. Infrastructure placement does not replace the company key and does not reintroduce an alias that changes behavior.

## 4. Physical entity candidates

Each candidate states purpose, owner domain, company scope, immutability, effective dating, versioning, and major relationships. “Immutable” means the business content is not updated in place.

| Candidate | Purpose | Owner | `company_id` | Immutable | Effective-dated | Versioned | Major relationships |
|---|---|---|---|---|---|---|---|
| Company | Tenant boundary | Company | It is the tenant | Identity stable. Status may change | No | No | Parent of operational records |
| Branch | Site under a company | Organization | Direct | Amendable. History of the node is kept | Yes, when the node changes | Yes | Company. Optional policy override only if that company enables it |
| Department | Department under the company | Organization | Direct | Amendable with history | Yes | Yes | Company. Not required to hang under a branch |
| Designation | Designation under the company | Organization | Direct | Amendable with history | Yes | Yes | Company |
| Employee group | Shared policy audience | Organization | Direct | Amendable with history | Yes | Yes | Company. Preferred scope for eligibility and rate |
| Optional location node | Extra location depth | Organization | Direct | Amendable with history | Yes | Yes | Branch. Depth is CONFIGURABLE. Not a universal factory tree |
| Employee | Person in one company | Employee | Direct | Identity stable | Status changes are dated on employment facts | Master is stable. Facts are versioned | Exactly one company |
| Employment episode | One joining-to-separation span, including a later rejoin | Employment | Direct | Append-only episode | The episode has joining and last working dates | Episodes append | Employee |
| Employment fact | One fact type over an interval: department, designation, location, group, status, shift default | Employment | Direct | A closed interval is immutable | Yes | New interval supersedes. Old interval remains | Episode, organization node or shift definition |
| Reporting chain | The chain in force over an interval | Reporting | Direct | Closed chain immutable | Yes | Yes | Employee |
| Reporting link | One position in that chain | Reporting | Direct | Immutable with its chain | Inherited from the chain | Inherited | Chain, manager employee. Length is CONFIGURABLE |
| Shift definition | Stable identity of a shift | Time | Direct | Identity stable | No | Versions hold the window | Company |
| Shift definition version | Start, end, punch window, cross-midnight flag | Time | Direct | Immutable once cited by a calculation | The version’s validity | Yes | Definition. Cited by resolutions and calculations |
| Shift assignment candidate | A default interval or a dated roster entry | Time | Direct | Closed candidate immutable | Yes | Yes | Employee, shift version. Kind is default or roster |
| Shift resolution | The one effective assignment for an employee and `work_date` | Time | Direct | Immutable once written for that calculation | The `work_date` is the key | Recalculation writes a new resolution tied to the new calculation | Employee, `work_date`, winning candidate. Winner rule is BD-D03 |
| Weekly-off rule | Which weekdays are off, for a company, group, or rare person exception | Holiday | Direct | Closed rule immutable | Yes | Yes | Company, optional group, optional employee |
| Public holiday | A holiday date and its audience | Holiday | Direct | Immutable for a date already used by a stored result. Later dates are amendable | The holiday date is the effective date | Corrections to future dates version the calendar | Company. Optional shift limit is CONFIGURABLE |
| Punch source | A named source the company accepts | Ingestion | Direct | Name stable. Enabled flag may change | Enablement may be dated | No | Company. Protocol identity is not this candidate |
| Punch identity | Mapping from a punch identity to an employee | Ingestion | Direct | Closed mapping immutable | Yes, active interval | New interval supersedes | Employee. Identity is not the employee code |
| Punch identity overlap authorization | The explicit permission for one identity to be active for two employees | Ingestion | Direct | Immutable | Yes | Yes | Two mappings. Absent unless the company records an overlap |
| Raw punch event | Accepted event | Ingestion | Direct | Immutable | No | No | Punch identity, punch source. Holds calendar event time and ingestion time. Does not hold `work_date` |
| Punch source reference | External id from the source, when the source has one | Ingestion | Direct | Immutable with the event | No | No | Punch event. Legacy `balid` is not this field’s meaning |
| Attendance calculation | One calculation of one employee and one `work_date` | Attendance | Direct | Immutable | The `work_date` | Yes. Recalculation appends | Shift resolution, policy versions, prior calculation |
| Selected punch link | Which events the calculation used | Attendance | Direct | Immutable with the calculation | No | With the calculation | Calculation, punch events. This link holds the `work_date` association |
| Attendance day result version | Stored status, incomplete condition, late duration, early duration, effective shift | Attendance | Direct | Immutable | Keyed by `work_date` | Yes. Original remains. A new version becomes current | Calculation. Optional correction |
| Current day-result pointer | Which version is current for employee + `work_date` | Attendance | Direct | The pointer moves only by writing a new version | The `work_date` | The pointer changes. Versions do not | Result version |
| Leave type | Company-defined type | Leave | Direct | Identity stable. Pay effect is policy, not a frozen value | Type meaning is effective-dated | Yes | Company. Paid, unpaid, or partial is BD-D06 |
| Leave request | Date-range request and reason | Leave | Direct | Request content stays. Outcome is recorded, not overwritten as a silent replacement | The requested dates | Outcome history is the approval instance | Employee, leave type, approval instance |
| Leave day impact | A `work_date` a configured outcome marked | Leave | Direct | Immutable once applied | The `work_date` | A later result version may cite a later impact | Leave request, result version |
| Correction request | A request distinct from the punch and the result | Correction | Direct | Original and requested values immutable after submit | The `work_date` | Approved and final values append. They do not replace original | Employee, `work_date`, original result version |
| Correction value set | Original, requested, approved, and final | Correction | Direct | Each value is immutable once recorded | No | The four values coexist | Correction request. Allowed fields are BD-D16 |
| Approval workflow definition | Ordered steps for a subject type | Approval | Direct | A cited version is immutable | Yes | Yes | Company. Step list is BD-D17 |
| Approval step definition | Sequence, resolver kind, bypass flag as configured | Approval | Direct | Immutable with its definition version | Inherited | Inherited | Definition version. Resolver is role, reporting-line position, organizational approver, or named fallback |
| Approval instance | One run of a definition against one subject | Approval | Direct | The binding step list is copied and then immutable | No | Actions append | Definition version, subject |
| Approval action | Acting user, time, outcome | Approval | Direct | Immutable | No | Append-only | Instance, user. Unknown actor only for migrated history |
| Calculated OT | The figure produced with a day-result version | Overtime | Direct | Immutable with that result version | The `work_date` | A new result version may carry a new figure | Result version, policy version. Formula is BD-D09 |
| Payable OT | The separate figure that may feed payroll | Overtime | Direct | A finalized reference is immutable. A draft figure versions | The `work_date` | Yes | Calculated OT. Optional approval instance when BD-D11 is enabled |
| Paid OT | Money on the payroll snapshot | Payroll | Direct, on the snapshot | Immutable with the finalized snapshot | The payroll period | With the snapshot version | Snapshot amount. Not an attendance column. Rate is BD-D12 |
| Salary head | Company-defined earning or deduction identity | Payroll | Direct | Identity stable | Head validity is dated | Yes | Company. The catalogue is CONFIGURABLE |
| Salary structure | Structure identity for an employee | Payroll | Direct | Identity stable | Assignment interval | Yes | Employee |
| Salary structure head | Which heads the structure includes over an interval | Payroll | Direct | Closed interval immutable | Yes | Yes | Structure, head |
| Payroll period | Company period | Payroll | Direct | Identity stable | The period bounds | Close and reopen are events, not overwrites of history | Company |
| Attendance period control | Whether cutoff is enabled, and the boundary when it is | Period | Direct | A change is an event | The period | Yes | Company, period. Enablement is BD-D13 |
| Payroll run | One calculation attempt for a period | Payroll | Direct | A completed version is immutable | The period | Yes. Draft recalculation appends a version | Period, declared inputs |
| Declared input set | The named attendance, leave, and OT inputs that version used | Payroll | Direct | Immutable with the run version | No | With the run | Run. A run that ignores attendance says so |
| Payroll snapshot | Employee result: structure identity, net, version, actors, times, finalization state | Payroll | Direct | Finalized content immutable | The period | Yes. Reopen appends. It does not edit the prior finalized row | Run, employee, structure |
| Snapshot head amount | One head’s amount on that snapshot | Payroll | Direct | Immutable with the snapshot version | No | With the snapshot | Snapshot, head |
| Snapshot input fact | Copied value of each declared input | Payroll | Direct | Immutable with the snapshot version | The facts are as-of calculation | With the snapshot | Snapshot. Copied values, not a live link |
| Snapshot optional fact | Payable days, leave, absence, late or early, calculated OT, approved OT, payable OT, only when that company stores them | Payroll | Direct | Immutable with the snapshot version | No | With the snapshot | Snapshot. Which lines exist is BD-D18 |
| Payslip | A reading of one finalized snapshot | Payroll | Inherited from the snapshot | No independent amounts | No | The reading follows the current finalized snapshot | Snapshot. Legal layout is extension metadata, not amounts |
| Policy record | Scope, precedence, effective interval, value | Policy | Direct, except a product-scope default | A cited version is immutable | Yes | Yes | Company or a lower scope only when that policy allows it. Values of D01–D18 are empty |
| Policy citation | The policy version a calculation or payroll run used | Policy | Direct | Immutable | No | With the calculation or run | Calculation or run, policy version |
| Audit record | Actor, time, old, new, subject | Audit | Direct | Immutable and append-only | No | Append-only | Subject. Never hard-deleted |
| Extension catalog entry | A known optional slot | Extension | None. Product list | Identity stable | No | No | Slots already named: legal payslip layout, external day-count feed, mobile location source, future device protocol |
| Company extension enablement | On or off for one company | Extension | Direct | A change is audited | Enablement may be dated | Yes | Company, catalog entry. Default off |
| Legacy source reference | Uninterpreted import key | Migration support | Direct | Immutable | No | No | Any migrated row. Holds source name and source key. Not a business identifier |

User and role records are required for actor and authorization references. This assessment does not design the permission model. An actor reference is a surrogate user id. Migrated actions may store an explicit unknown actor.

## 5. Identifier strategy

| Kind | Recommendation | Where |
|---|---|---|
| Surrogate primary identifier | A unique identifier that is not a business code. ULID is the recommended form: it does not need a central counter, and it sorts by creation time, which fits append-only history and loading many legacy companies into one product. UUID is acceptable where time order is unnecessary | Every candidate entity |
| Auto-increment integer | Not the target primary-key strategy. It binds identity to one database instance and collides when separate legacy databases are combined | Not used as the cross-company identity |
| Business code | An attribute with scoped uniqueness. Never the primary identifier | Employee code, punch identity value, period label, head code, shift code |
| External or imported identifier | A separate source-reference pair: source name plus source key | Legacy row ids, and a source event id when a punch source actually provides one |

Employee code, punch identity, payroll numbers, and similar business codes stay unique only inside the scope stated in section 8. They can change or be reused only under a rule that is still open, so they cannot be the permanent primary key.

Storage width and textual versus binary encoding of the surrogate are an implementation detail. They are not chosen here.

## 6. Company scoping

Direct `company_id` is required on every record that a list, report, ingestion, calculation, or payroll path can read without first loading its parent. That includes employees, employment facts, shifts, holidays, punch identities, raw punches, calculations, day results, leave, corrections, approval instances, overtime figures, payroll runs, snapshots, policies, audit records, and extension enablement.

Inherited company scope is acceptable only for a child that is always written and read in the same operation as its parent and is never authorized on its own. Reporting links, approval actions, selected-punch links, snapshot head amounts, and snapshot input facts may inherit, and they still store `company_id` directly because a missed join must not return another company’s child.

Global shared data is limited to the extension catalog and to product-scope policy defaults, which have no company. Product defaults are not a place to park one company’s rules.

Cross-company references are rejected for operational data. A punch enters a company only through a punch-identity mapping in that company. A manager on a reporting link belongs to the same company. A snapshot cites structures, heads, and copied inputs of the same company.

The data-access boundary checks `company_id` on every read and write. A screen filter is not that check.

## 7. Foreign key strategy

No SQL constraints are created in this phase. The intended relationships are:

| Class | Relationships | Rule |
|---|---|---|
| Strong | Employee to company. Employment fact to employee. Punch identity to employee. Punch event to punch identity and punch source. Calculation to employee. Result version to calculation. Correction to original result version. Approval action to instance and user. Snapshot to run, employee, and structure. Snapshot amount to snapshot and head. Payable OT to calculated OT | Product-native writes require the parent to exist in the same company |
| Historical pointer | Calculation to the shift-definition version, policy versions, and selected punches. Snapshot input fact may store the attendance-version id it copied | The pointer identifies a specific version. It does not update that version, and it does not cascade payroll changes back into attendance |
| Optional | Payable OT to an approval instance. Public holiday to selected shifts. Branch policy override. Person exception on weekly off or OT eligibility. Punch source event id | Present only when that configuration or source fact exists |
| External reference | Legacy source key. Uninterpreted `area` and `balid` if they are retained at all | Not foreign keys. They do not point at a target parent |
| Not a foreign key | Punch event to `work_date`. Finalized snapshot amounts to live attendance rows. Payslip to a second amount store. Paid OT to an attendance column | These links would collapse separations the logical model requires |

Hard foreign keys on product-native data are appropriate. They are a poor fit for a migration row whose parent was never stored, such as a missing prior day-result version or an approval with no user. Those rows use an explicit unknown marker or stay in a migration staging area until the reference is resolved. That staging exception does not remove foreign keys from the target product.

Deleting a parent is not the way history is withdrawn. Withdrawal is a status. A foreign key must not be satisfied by cascading a hard delete through punches, results, corrections, approvals, or finalized snapshots.

## 8. Uniqueness and integrity

| Subject | Physical uniqueness | Decision dependency |
|---|---|---|
| Employee code | `UNIQUE (company_id, employee_code)`, including separated employees. The code stays on that identity | Closed by `docs/UNIVERSAL_HRMS_STEP20_FINAL_EMPLOYEE_CODE_DECISION.md` |
| Punch identity | At most one active mapping per company for a given identity, unless an explicit overlap authorization exists for that interval | How many identities one employee may hold is CONFIGURABLE. The overlap exception is already allowed by PUNCH-CORE-009 |
| Source event id | When a source provides an event id, one event per company, source, and source event id | Sources that provide no id are valid. Legacy rows often have none. Absence is not a duplicate |
| Same-second punches | Not unique on timestamp plus identity | A unique pair would drop a second real event. Duplicate treatment is PUNCH-CONF-002, not a universal single-row constraint |
| Effective shift | Exactly one shift resolution per company, employee, and `work_date` for a given calculation generation’s current pointer | Which candidate wins is BD-D03. Uniqueness applies to the resolved row, not to “only one candidate may exist” |
| Overlapping candidates of the same kind and precedence | Rejected or deterministically resolved for the same employee and interval | Winner between default and roster remains BD-D03 |
| Overlapping policy | One open interval per company, policy, scope, and precedence | The value is not chosen. The overlap rule is architectural |
| Payroll snapshot | One current finalized snapshot per company, employee, and period. Earlier finalized versions remain, marked not current | Optional lines inside it are BD-D18 |
| Payroll run in progress | One in-progress run per company and period | Historical versions are many |
| Approval step order | Step sequence is unique inside a workflow definition version | The company’s step list and bypass flag are BD-D17. Do not require every step to have an action |
| Correction application | One in-progress application per company, employee, and `work_date` | Which fields may change is BD-D16 |
| Leave overlap | Not a universal unique constraint | The requirements do not forbid two requests that touch the same dates. Each request is stored. Overlap policy is not invented here |
| Calculated, payable, and paid OT | Each figure has its own identity. No constraint requires them to be equal | Formula, eligibility, rounding, approval, and rate are BD-D08–BD-D12 |
| Paid OT linkage | When the run declares OT, the snapshot input fact cites the payable OT figure used | When the company does not pay OT, no paid amount is stored |

Current-pointer uniqueness is what users and reports resolve. Historical versions are unique by their own surrogate plus their predecessor link, not by hiding the older row.

## 9. Effective dating

Physical intervals use `effective_from` and `effective_to`. An open current interval has no `effective_to`. Lookup for a `work_date` or a payroll period selects the interval that contains that date at the required precedence.

Intervals are treated as half-open: the start is included and the end is excluded. Adjacent intervals meet without a one-day gap and without a one-day overlap. That is a storage convention, not a business date and not a policy value.

| Stored with intervals | Physical rule |
|---|---|
| Employment facts | One interval per fact type contains a given date. Overlap at the same fact type is rejected |
| Organization assignment | Department, designation, location, and group are employment facts, not a second history |
| Reporting line | One chain interval contains a given date. Links belong to that chain version |
| Shift assignment candidates | Default uses an interval. Roster may name specific dates. Same kind and same precedence do not overlap |
| Shift resolution | One current row per employee and `work_date`, citing the candidate that won |
| Policy | One interval per policy, scope, and precedence. The calculation stores the policy version id, so a later edit does not change the old result |
| Salary structure and structure heads | The snapshot stores the structure version id that was in force |
| Approval workflow definition | The instance copies the definition version id and the step list. Later edits create a new definition version |
| Attendance period control and payroll close | Changes are dated events. They do not erase the prior state |
| Weekly off and public holiday | A stored result keeps the holiday fact it used, by citation, after the calendar is edited for a future date |

No actual dates are chosen.

## 10. Attendance physical architecture

Physical path:

```
Raw punch event          immutable, calendar timestamp, no work_date
→ Attendance calculation original
→ Attendance day result version original
→ Correction request     original and requested values
→ Approval action
→ Approved correction    approved value
→ Attendance calculation new version
→ Attendance day result version new/current
```

| Physical rule | How it is stored |
|---|---|
| Raw punch immutable | Event content is insert-only. No correction path updates it |
| `work_date` independent of punch time | `work_date` is on the calculation, the resolution, and the result version. Event time stays on the punch |
| Cross-midnight | The shift version stores start, end, punch window, and a cross-midnight indicator. A shift from Oct 9 09:00 to Oct 10 09:00 resolves to `work_date` Oct 9. A punch at Oct 10 02:00 still stores Oct 10 02:00 |
| One effective shift | The resolution row is unique for that employee and `work_date` among current rows |
| Selected punches traceable | Selected-punch links point at event ids and record that they were used for that `work_date`. An empty selection is an explicit calculation fact, not a missing link |
| Calculation identity | Each calculation has its own surrogate, cites policy versions, shift version, holiday, leave, and correction if any, and cites the previous calculation when it is a recalculation |
| Original result preserved | The original version remains. The current pointer moves to the new version |
| Correction impact visible | The new result version stores the correction id. The correction stores original, requested, approved, and final values |
| Reports | Read the current result version. They do not recompute from punches |
| Finalized payroll | Not in this write set |

Incomplete attendance is stored as its own condition on the result version. The company’s resulting status is a separate stored value. Neither Present-plus-text nor the letter A is the physical representation. What the status becomes is BD-D02.

## 11. OT physical architecture

| Figure | Physical candidate | Traceability |
|---|---|---|
| Calculated OT | One row per day-result version when the calculation produces a figure | Cites the result version, the shift version, and the formula policy version. The formula value is not chosen |
| Payable OT | A separate row per employee and `work_date` version | Cites the calculated OT it considered. Cites eligibility, rounding, and limit policy versions. Cites an approval instance only when the company has enabled OT approval |
| Paid OT | Amount on the snapshot, plus a snapshot input fact | Cites the payable figure used when OT was a declared input. No attendance column holds the money |

Calculated, payable, and paid values may differ. Nothing in the physical model sets them equal.

A punch correction does not write payable OT. If both a correction and an OT approval exist, they are different approval instances and different subject types.

Recalculating attendance appends a new calculated figure with the new result version. It does not update a payable figure that a finalized snapshot has already copied. While payroll for that period is still draft, a new payroll version may read the new payable figure. That payroll write is a new snapshot version, not an edit of a finalized one.

## 12. Payroll snapshot architecture

A finalized snapshot is a copied, self-contained employee result. Later attendance recalculation inserts attendance versions only. It does not update snapshot rows.

Always stored on the snapshot version:

| Content | Physical home |
|---|---|
| Employee | Snapshot |
| Payroll period | Snapshot and run |
| Salary structure identity | Snapshot cites the structure version |
| Head amounts, including deductions | Snapshot head amount rows |
| Net pay | Snapshot |
| Calculation version | Run version id on the snapshot |
| Calculation timestamp and calculated actor | Run version |
| Finalized actor and finalized timestamp | Snapshot, set when state becomes finalized |
| Finalization state | Snapshot. Draft and finalized are states. Prior versions remain |
| Declared inputs | Declared input set on the run version |
| Values of those declared inputs | Snapshot input facts, copied at calculation and confirmed at finalization |

Optional facts use snapshot optional-fact rows and exist only for the lines that company has configured. The physical model has a place for payable days, paid and unpaid leave, absence, late or early treatment, calculated OT, approved OT, and payable OT. None of those lines is turned on here (BD-D18). Payable-day basis and absence formula are not chosen (BD-D07).

Finalization checks that every declared input has a copied value. Missing declared input blocks finalization.

The payslip reads the current finalized snapshot for that employee and period. It has no amount rows of its own. An extension may store layout enablement. It may not store a second net pay.

Reopen, when a period is closed, appends a reopen audit event and allows a new draft run. The previous finalized snapshot stays unchanged and not current. Who may reopen is BD-D15 and stays deferred. A company that never closes can still finalize, and that finalized snapshot still does not follow attendance. Close is a separate control (BD-D14).

## 13. Audit and immutability

| Records | Physical behavior |
|---|---|
| Raw punch events | Immutable. Withdrawal is a status. Never hard-deleted |
| Selected-punch links, calculations, result versions | Append-only and immutable. The current pointer is the only moving attendance reference |
| Corrections | Original and requested values immutable after submit. Approved and final values append. Never hard-deleted |
| Approval instances and actions | Actions append-only and immutable. Never hard-deleted |
| Calculated OT and payable OT versions | Immutable once written. New versions append |
| Draft payroll run and draft snapshot | Replaced only by appending a new version. The draft row is not edited into a different result |
| Finalized snapshots, head amounts, and copied inputs | Immutable. Never hard-deleted. A later reopen does not update them |
| Paid OT on a finalized snapshot | Immutable with that snapshot |
| Audit records | Append-only, immutable, never hard-deleted |
| Policy versions already cited | Immutable. A change inserts a new version |
| Employment intervals already closed | Immutable |
| Extension enablement | Amendable only by a new dated enablement row, with an audit record |

No candidate in the attendance, correction, approval, overtime, or finalized-payroll set is hard-deletable. Soft deletion that hides history is not the withdrawal mechanism. Withdrawal keeps the row and records the status, actor, and time.

## 14. Indexing strategy

Indexes are access paths. Exact index definitions and SQL are not specified. The engine’s index implementation is deferred.

| Access pattern | Why an index is required |
|---|---|
| Company scope on operational lists | Almost every read starts with `company_id`. Without it, a query can scan another company’s rows before a later filter |
| Employee + `work_date` on resolutions, calculations, current result pointer, corrections | Day review, recalculation, and correction lookup are all this pair |
| Punch identity + event time | Ingestion duplicate detection and the shift-window search that selects punches. The index supports a time range. It is not a uniqueness constraint on that pair |
| Source + source event id | Idempotent ingestion when the source provides an id |
| Current result by company + `work_date` range | Attendance reports read stored current results for a period |
| Employee + effective interval | Employment, reporting, shift candidate, and policy lookup for one date |
| Policy by company + policy + scope + precedence + interval | Deterministic resolution must find the single winning row without depending on scan order |
| Payroll period + employee | Run, snapshot, and payslip reads |
| Approval instance by subject | Leave, correction, and optional OT approval |
| Correction by company + employee + `work_date` | Apply and history |
| Payable OT by company + employee + `work_date` | Payroll input copy |
| Snapshot by company + period + finalization state | Finalization, payslip, and “is there already a current finalized row” checks |
| Audit by subject | History of one business object |

An index that enforces uniqueness is limited to the cases in section 8. A reporting index must not accidentally become a uniqueness rule, especially on punch timestamp.

## 15. Transaction boundaries

Transactions are local to one company operation. Queues and distributed transactions are not designed.

| Operation | Writes that commit together | Writes excluded |
|---|---|---|
| Punch ingestion | The punch event, its source reference when present, and the duplicate-detection outcome. A quarantine of a failed import commits no partial event | Attendance calculation, payroll |
| Attendance calculation | Resolution, calculation, selected-punch links, result version, current pointer move, calculated OT for that version, policy citations | Updates to punch events. Updates to any finalized snapshot |
| Correction approval and application | Approval action, approved and final correction values, and the new attendance calculation set from the row above | Updates to the raw punch. Updates to the original result version. Updates to finalized payroll |
| OT approval | Approval action and the payable OT version, when the company has enabled this approval | Punch correction. Paid OT money |
| Payroll calculation | New run version, declared input set, snapshot version, head amounts, and copied input facts | Attendance rows, other than reading them |
| Payroll finalization | State, finalized actor, finalized timestamp, and a check that declared inputs are present | Attendance rows. Prior snapshot versions |
| Payroll reopen or reversal | Reopen audit event, period-control event, and permission to start a new draft. The prior finalized snapshot is not updated | Attendance recalculation, unless a separate later operation does that |

A correction application is one transaction so the current pointer cannot move without the approved values, and the approved values cannot exist without the new result version.

## 16. Concurrency and consistency

Locking syntax is not chosen. The protection principles are:

| Risk | Protection |
|---|---|
| Two attendance calculations for the same employee and `work_date` | One current-pointer write at a time for that pair. The loser appends nothing or retries against the new current version. Both must not become current |
| Two corrections applied to the same `work_date` | One in-progress application for that pair. The second waits or is rejected. Each approved correction still produces its own result version, in order |
| Duplicate punch ingestion | If a source event id exists, a second insert of that company, source, and id is rejected or recognized as the same event. If no source id exists, the company’s duplicate policy decides. Two different events in the same second can both be stored |
| Two payroll runs for the same period | One in-progress run per company and period. A second draft start is rejected until the first completes, fails, or is superseded by an explicit new version |
| Two snapshots for the same employee in that run | One snapshot version per employee per run version |
| Finalization while attendance is recalculating | Finalization copies the input values it read and then freezes them. A attendance commit after that copy does not update the snapshot. Finalization and attendance do not write the same rows |
| Reopen while payroll is processing | Reopen and an in-progress run for that period are mutually exclusive. Reopen applies only to a closed period. The existing finalized rows stay immutable |
| Policy read during calculation | The calculation cites the policy version ids it resolved. A policy insert committed after resolution is invisible to that calculation |
| Current pointer and history | The transaction that inserts the new version is the transaction that moves the pointer. Readers of “current” see the previous current row until that transaction commits |

Consistency is per company. A transaction does not read or write a second company’s rows.

## 17. Legacy mapping to physical candidates

No migration script is specified.

| Legacy object | Physical candidate | Class |
|---|---|---|
| `companies`, without alias behavior | Company. Source reference holds the legacy company id | Direct migration candidate for identity. `db_alias` and `previous_db_id` are LEGACY ONLY and are not target columns |
| `employees` | Employee, employment episode, employment facts | Transformation required. Photo-as-weekend is not a target column |
| `employees.shift_id` | Shift assignment candidate of kind default | Migration evidence only until BD-D03. Not automatically the resolution row |
| `emp_shift_calendar` | Shift assignment candidate of kind roster | Transformation required where rows exist. Do not invent a roster |
| `att_shift` | Shift definition and shift definition version, including cross-midnight when the legacy window says next day | Transformation required. Grace is not copied as a universal value |
| `emp_code_to_punch_card` | Punch identity | Transformation required. Active duplicates are flagged, not silently merged |
| `all_att_log` | Raw punch event, plus a legacy source reference | History reconstruction required. Calendar time is kept. `work_date` is not backfilled onto the event. Company comes from the punch identity. Dropped middle punches are not invented |
| `area`, `balid` | Legacy source reference only, uninterpreted | UNKNOWN. Unsafe to treat as source id or device id |
| Company `att_log` | None | LEGACY ONLY for the studied companies |
| `attendance_detail` | Attendance day result version, usually as a single surviving version | History reconstruction required. Prior versions destroyed by delete-and-insert stay unknown. Unsafe to fabricate them |
| `hrm_leave_emp_app` | Leave request and approval instance | Transformation required. Numeric status is not the target outcome code |
| `attendance_approval_file` | Correction request and correction value set | History reconstruction required. Missing original or approved values stay unknown. A name string is not a user id. Unsafe to invent the punch that was overwritten |
| `boss_id`, `gboss_id` | Not the approval definition. Optional evidence for a reporting chain | LEGACY ONLY as workflow. Business decision BD-D17 remains open |
| `employee_overtime` | None | LEGACY ONLY for the studied months. Unsafe to load as payable OT |
| Attendance `ot_hours` | Calculated OT only after a unit is known | Business decision required (BD-D09, BD-D10). Unsafe to convert seconds or whole hours into one physical unit |
| `eligible_for_ot` | None | LEGACY ONLY |
| `employee_salary` | Snapshot head amounts, with declared inputs marked absent | Transformation required. Unsafe to invent attendance inputs that were not stored |
| Payslip views over stored salary | Payslip reading a snapshot | Evidence only. The view is not a target amount table |
| Payslip views over `nms_data` | Extension enablement for an external day-count feed | CLIENT EXTENSION evidence. Off by default |
| `attendance_month_lock` | Attendance period control | Business decision required. Unsafe to migrate Yes as cutoff (BD-D13) |
| `salary_month_lock` | Payroll period control | Business decision required. Unsafe to migrate Yes as close (BD-D14) |

Where the legacy actor is missing, the physical audit actor is the explicit unknown marker.

## 18. Retention

No legal retention period is chosen. Duration is a future company and legal policy decision.

Until that decision exists, the physical architecture keeps the rows:

| Records | Retention posture |
|---|---|
| Raw punches | Retained. Withdrawal is a status. Not hard-deleted. How long is open |
| Attendance result versions | Retained with their calculations and selected-punch links |
| Corrections and approvals | Retained after the day result changes |
| Payroll snapshots, including superseded finalized versions | Retained. A reopen does not license deletion of the prior snapshot |
| Audit records | Retained for the life of the subject they describe |

Archival storage, partitioning, and backup mechanics are infrastructure decisions and are not designed here. Archive-and-delete, as practiced on the legacy punch log, is not the target.

## 19. Physical data risks

| Risk | Prevention principle |
|---|---|
| Company leakage | Direct `company_id` on independently readable rows. Access checks the company. No cross-company operational foreign key |
| Duplicate business identifiers | Scoped uniqueness in section 8. Business codes are not primary keys |
| Overlapping effective records | Half-open intervals. One winner per fact type, scope, and precedence. Overlap is rejected or deterministically resolved, never by scan order |
| Ambiguous `work_date` | `work_date` is stored on the resolution and the result. It is not derived at read time from the punch clock date |
| Cross-midnight errors | Shift version stores the window and the cross-midnight indicator. Punch time is unchanged. The selected-punch link records the association |
| Mutable raw events | Punch content is insert-only |
| Mutable finalized payroll | Finalized snapshot rows are not in the attendance or draft-recalculation write set |
| Orphaned approvals | An approval instance requires a subject in the same company. An action requires the instance. Migration rows without a user use the unknown actor marker instead of a dangling user |
| Orphaned OT | Calculated OT requires a result version. Payable OT requires the calculated OT it considered. Paid OT requires a snapshot, and a payable citation when OT was declared |
| Missing audit actor | Product-native approval and finalization require a user. Migration may store unknown, labeled as unknown |
| Duplicate payroll runs | One in-progress run per company and period. One current finalized snapshot per employee and period |
| Policy ambiguity | Unique open interval per policy, scope, and precedence. Calculations cite version ids |
| Historical irreproducibility | Calculations and snapshots store the source facts, policy version ids, and their own version ids. Copied snapshot inputs do not point live at attendance |
| Same-second punch loss | No uniqueness constraint on event time plus identity |
| Alias behavior reintroduced | `db_alias` and `previous_db_id` are absent |
| Hard delete | Excluded for punches, results, corrections, approvals, overtime versions, finalized snapshots, and audit |

## 20. D01–D18 safety check

This physical assessment does not choose a policy value.

| ID | Subject | Physical posture |
|---|---|---|
| BD-D01 | Grace | A policy record slot at company default and shift override. No minutes |
| BD-D02 | Incomplete punch outcome | A condition on the result version, separate from status. No universal status |
| BD-D03 | Shift winner | Candidate rows plus one resolution row. The winner value is empty |
| BD-D04 | Late measurement start | Duration and a policy citation. No start rule |
| BD-D05 | Early-out pay | Duration stored. Pay effect is an empty payroll policy slot |
| BD-D06 | Leave pay | Leave type exists. Paid, unpaid, or partial is empty |
| BD-D07 | Absence formula | Optional snapshot input slot. No formula |
| BD-D08 | OT eligibility | Group policy slot. Legacy flag is not a column |
| BD-D09 | OT formula | Calculated OT is a figure plus a policy citation. No formula |
| BD-D10 | OT rounding | Policy slot. No method |
| BD-D11 | OT approval | Optional approval link. Disabled until the company enables it |
| BD-D12 | OT rate and payment | Paid OT is snapshot money. No rate |
| BD-D13 | Attendance cutoff | Period-control row. Enablement is empty |
| BD-D14 | Payroll close | Close is separate from finalization. Enablement is empty |
| BD-D15 | Who may reopen | DEFERRED. No role list |
| BD-D16 | Correction fields | Four value kinds exist. The allowed-field list is empty |
| BD-D17 | Approval sequence | Definition and step sequence exist. The company’s steps are empty |
| BD-D18 | Optional snapshot facts | Optional-fact rows can exist. None is required beyond the minimum snapshot and the inputs the run declared |

Payable-day basis stays open. No 30-day default is stored.

## 21. Deferred decisions

Not decided in this assessment:

- database engine and its configuration
- identifier storage format beyond the ULID recommendation
- server sizing
- replication
- partitioning
- sharding
- backup implementation
- cloud provider
- migrations and physical DDL
- API schema
- queue architecture
- cache infrastructure
- deployment
- monitoring
- legal or company retention durations
- the permission model’s physical tables
- reuse of an employee code after separation
- overlap rules for leave requests
- the meaning of legacy `area` and `balid`
- the external punch writer

ULID is an identifier recommendation. It is not an engine, a vendor, or a migration.

## 22. Readiness assessment

| Item | Assessment |
|---|---|
| Logical model can be represented as physical candidates | YES |
| Tenancy is `company_id` on one product schema | YES as an architectural decision |
| Database-per-client required | NO |
| `db_alias` and `previous_db_id` in the target | NO |
| Surrogate identifiers, business codes kept as attributes | YES |
| `work_date` stored independently of punch time, cross-midnight supported | YES |
| Raw punches immutable, result versions traceable, correction history preserved | YES |
| Calculated, payable, and paid OT stored separately | YES |
| Finalized snapshot independently preserved | YES |
| Effective dating and deterministic overlap rules | YES |
| D01–D18 | DECISION REQUIRED. BD-D15 DEFERRED |
| Engine, DDL, migrations, API, UI, deployment | NOT STARTED |
| Development | NOT STARTED |

The physical architecture is ready to guide a later implementation plan. It is not that implementation. Building the legacy tables forward would recreate alias behavior, min/max punch loss, overwritten corrections, and payroll without copied inputs.

### Verification

| Check | Result |
|---|---|
| SQL schema or migrations created | No |
| Database modified | No |
| API, UI, or framework designed | No |
| Business policy value selected | No |
| Proedge or Viyellatex storage promoted to the universal model | No. Section 17 maps them as transformation, reconstruction, or legacy-only |
| Company isolation explicit | Yes. Sections 3 and 6 |
| `work_date` independent of punch timestamp | Yes. Section 10 |
| Cross-midnight supported | Yes. Section 10 |
| Raw punches immutable | Yes. Sections 10 and 13 |
| Attendance versions traceable | Yes. Section 10 |
| Correction history preserved | Yes. Sections 10 and 13 |
| Calculated, payable, and paid OT separate | Yes. Section 11 |
| Finalized payroll snapshot independently preserved | Yes. Section 12 |
| Effective dating supported | Yes. Section 9 |
| Deterministic uniqueness and precedence addressed | Yes. Section 8 |
| D01–D18 remain open | Yes. Section 20 |

# Universal HRMS — Incremental Delivery and Decision Gates

Date: 2026-10-12

Status: **PLAN PREPARED**. This record does not approve a policy, start a stage, or mark a company migration-approved. The DEC-01 meeting has not occurred. Employee-migration and attendance fields in Step 20R remain blank. AUTH-ARCH is approved in that register, subject to all Section 14 conditions. SEC-CORE-004's initial role list is approved there, subject to its conditions. Neither approval satisfies SEC-CORE-003 or authorizes implementation. The first-release scope boundary is approved in Step 20R section 6D and does not authorize implementation.

## 1. Delivery principle

Proceed with development when the required architecture, data integrity rules, security controls, and dependencies are sufficiently defined. Resolve a business decision when the feature that depends on it is about to be built.

A proposal, including the D6 session text and the external D1, D3, and D4 draft, is not an approval.

## 2. What is already in place

Stages 4A, 4B, and 4C are recorded in their implementation reports. This plan did not rerun their tests. The Stage 4C report records `php artisan test` as 31 passed and 147 assertions on `proedge_new_hrms_test`. That count is the report’s figure.

In the Laravel application today:

- Employee identity is a ULID. The employee code is unique inside one company and is not changed after create.
- An employee has at most one `Open` episode. An `Open` episode cannot store a last working date.
- Login, an active company membership, and `can_manage_employees` gate the employee screens.
- Rejoin, as approved, is a new episode on the same employee and the same code. The previous episode remains. The screen still refuses to set an existing non-Open episode back to `Open`.

AUD-01 through AUD-06 stay complete. They were not repeated.

## 3. Classification

**Blocks all implementation:** none. Company, employee, episode, user, and membership tables exist. The approved identity and company-scope rules are already enforced. No open item removes the ability to change code that stays inside those rules. This sentence does not authorize a new implementation milestone. Section 5 remains the next-task statement. A new module, screen, or calculation still requires the authorization in section 5.

**Approved,** from Step 20R section 2 and Step 20B section 5: ID-APPROVED, CODE-APPROVED, NOMERGE-APPROVED, D7-APPROVED, SEED-DONE, RECON-APPROVED (the contract, not an executed load), ARCH-APPROVED, PUNCH-APPROVED, and LIFE-CORE-008. Rejoin keeps the employee identity and employee code, inserts a new episode, and leaves the previous episode in place. One `Open` episode, and an empty last working date while `Open`, stay approved. The three company rows are tenant records. They are not a migration approval.

**Deferred:** BD-D15, as already deferred in Step 20R. PER-CONF-003, as already deferred in the requirements. SCOPE-01 stays out of the first release until an owner adds a module. Step 20R section 6D approves that deferral as part of the first-release scope boundary. This plan does not bring those modules in.

## 4. Decision gates

| ID | Status | Class | Dependent feature | Permitted before approval | Prohibited until approval | Required by | Approver or evidence already named |
| --- | --- | --- | --- | --- | --- | --- | --- |
| D1 | Unsigned. Option A is a proposal | Blocks legacy name load | `display_name` for a legacy row, including VTL id 437 | Manual entry of a display name on a new identity | A load that composes the three legacy name fields | Before the employee migration that writes those names | HR operations. Steps 20G–20K |
| D2 | Unsigned | Blocks legacy zero-date load | Episode dates copied from `0000-00-00` | Storing a real date entered in the application | Treating `0000-00-00` as a target date | Before a load that reads those source dates | HR operations |
| D3 | Unsigned. Option A is a proposal | Blocks legacy status load | Episode status when `active` and `disconrinue` are both 1 | The three stored statuses on a manually entered episode | Inferring `inactive` or `separated` from that flag pair | Before a load of those rows | HR operations |
| D4 | Unsigned hold. No merge and no silent recode are approved | Blocks the 14 duplicate rows | Those seven VTL codes | Keeping both identities distinct | Merge, silent recode, or loading the group without a disposition | Before a load of those 14 rows | HR operations |
| D5 | Unresolved. No definition | Can be resolved later | Any later domain that reads `employees.dis` | Leaving the column out of identity and status | Mapping `dis` into identity or episode status | Only if a later domain uses the column | Not assigned |
| D6 | Unsigned. Register proposal is Option A. The session proposal is also unsigned | Blocks legacy episode creation | Creating an episode from a legacy employee row | Creating an episode from data entered in the application | Creating an episode from an unverified legacy row, and adopting either proposal as the load rule | Before a load that creates episodes | HR operations. Approver and date for the session proposal are not recorded |
| LIFE-OVERLAP | Unresolved. No recommendation | Blocks a specific check | A rule that rejects overlapping episode dates, and a load that writes a second episode | The existing one-`Open` rule. The current screen may store dates without an overlap check | A new overlap rejection, and a migration that writes a second episode | Before that check or that load | HR operations, before a second episode is designed |
| LIFE-ORDER | Unresolved. No recommendation | Blocks a specific check | Rejecting a last working date before the joining date | The current screen, which can store reversed dates and says the rule is not approved | A validation that rejects reversed dates | Before that validation or a load that needs it | HR operations |
| LIFE-INACTIVE-PAY | Unresolved. No recommendation | Blocks payroll use of `inactive` | Pay result of episode status `inactive` | Storing the status. LIFE-CORE-006 remains the requirement that inactive follows company policy | A pay amount, a payable-day change, or a payroll formula for `inactive` | Before payroll reads `inactive` | HR operations and Finance. D06/D07 |
| Inactive last working date | Unresolved. No decision ID and no options | Blocks a specific check | Whether `inactive` may store `last_working_date` | The current screen stores a supplied date and does not treat it as pay | A rule that requires or forbids that date | Before that rule | Not named beyond Step 20B section 5 |
| Assignment | No decision ID. Columns are not defined | Blocks assignment | `employment_assignments` and department, designation, group, location, and shift history | Foundation nodes remain separate tables | An assignment migration, screen, or payroll use of those facts | Before that schema | EMP-CORE-004 and LIFE-CORE-002 through LIFE-CORE-005. The owner must name the columns and references |
| BD-D01 | Unsigned | Blocks grace | Late and early grace | — | Freezing 16 or 31 minutes, or another grace value | Before attendance grace is built | Attendance/Admin, HR |
| BD-D02 | Unsigned | Blocks an incomplete punch | Missing in or out outcome | Detecting that the day is incomplete, without copying a company result | An approved outcome or pay effect | Before that outcome is built | HR operations |
| BD-D03 | Unsigned | Blocks shift selection | Employee default versus roster | Storing both candidates | Choosing a winner | Before shift selection is built | HR operations |
| BD-D04 | Unsigned | Blocks late counting | Start of late minutes | — | A hidden second threshold | Before late counting is built | Attendance/Admin |
| BD-D05 | Unsigned | Blocks early-out pay | Early-out definition and pay effect | — | An early-out deduction formula | Before that pay effect is built | Attendance/Admin, Payroll |
| BD-D06 | Unsigned | Blocks paid leave | Which leave types are paid | — | A rule that all leave is paid | Before leave pay is built | HR, Payroll, Finance |
| LEAVE-OPENING | Unsigned. No formula is proposed | Blocks an approved leave balance | Using a stored or calculated balance for leave approval, and loading one as a confirmed production balance | Recording a proposed amount with its period, source, basis, variance, and exception status | Treating a legacy balance, a generator result, or an empty confirmation as approved. Choosing `earned`, `prev_bal` plus `earned`, or another figure from the dumps | Before leave-balance approval or a leave-balance load | HR operations. The 2026-10-17 dump reading does not answer it |
| BD-D07 | Unsigned | Blocks absence and joiner or leaver pay | Absence deduction and partial-month pay | The AUD-06 source description | A payable-day or proration formula, including a 30-day or calendar divisor | Before payroll calculation | Payroll, Finance, HR |
| BD-D08 | Unsigned | Blocks overtime eligibility | Who may earn overtime | — | Freezing `eligible_for_ot` | Before overtime eligibility is built | HR, Payroll |
| BD-D09 | Unsigned | Blocks overtime calculation | How overtime is calculated | — | Either studied formula | Before overtime calculation | Attendance/Admin, Payroll |
| BD-D10 | Unsigned | Blocks overtime rounding | Rounding rule | — | The 0.75 fraction | Before overtime rounding is built | Payroll |
| BD-D11 | Unsigned | Blocks overtime approval | Whether overtime needs its own approval | — | Treating punch approval as overtime approval | Before that approval is built | HR, Attendance/Admin |
| BD-D12 | Unsigned | Blocks overtime pay | Rate, minimum, and maximum | — | basic/208×2 or either studied amount | Before overtime pay is built | Payroll, Finance, HR |
| BD-D13 | Unsigned | Blocks attendance close | Attendance cutoff | — | Treating a Yes flag as a cutoff | Before attendance freeze is built | HR operations |
| BD-D14 | Unsigned | Blocks payroll close behavior | Freeze after generation | The approved snapshot rule: a finalized snapshot does not follow later attendance | A regeneration block beyond that approved rule | Before that close behavior is built | Finance |
| BD-D15 | Deferred | Deferred | Who may reopen a closed period | — | A reopen action | Remains deferred until an owner owns a reopen rule | Management, Payroll |
| BD-D16 | Unsigned | Blocks correction fields | Which fields a correction may change | The approved mechanism: original, requested, approved, and final values. The raw punch is not edited | A field list | Before correction editing is built | HR operations |
| BD-D17 | Unsigned | Blocks approval steps | The approval step list | — | A fixed boss and grand-boss sequence | Before that workflow is built | HR, Management |
| BD-D18 | Unsigned | Blocks optional snapshot lines | Which extra facts are snapshotted | Snapshot separation, which is approved | Invented snapshot lines | Before those lines are built | Finance |
| FY-BOUNDARY | Unsigned | Blocks financial-year reports | Start and end month of a company financial year | A payroll month that is not a financial year | January–December, June–May, or March–February as a company’s setting | Before a financial-year report | Finance. FY-CONF-001 |
| PUNCH-WITHDRAW | Unsigned | Blocks punch withdrawal | Who may withdraw a raw punch, and the audit record | Punch immutability | Deleting or withdrawing a punch | Before a withdrawal action | HR operations |
| SEC-CORE-003 | Requirement. Scope model unsigned | Blocks a specific limit | Branch, department, or employee scope | Company membership as implemented | A scope narrower than the company | Before that limit is built | Not named as a person |
| SEC-CORE-004 | Initial role list approved 2026-10-17, subject to the Step 20R section 6C conditions. Role-matrix implementation not authorized | Blocks implementation of the role matrix | Permissions granted by role | `can_manage_employees` on the employee module only, and `can_manage_organization_masters` as already implemented. The approved list is not a build authorization | Copying that flag onto payroll, attendance correction, or period close. Building the role matrix, permission screens, or a flag replacement | Before a role matrix is built | Anisur Rahman, Owner — Universal HRMS Project, 2026-10-17. Step 20R section 6C |
| DB-01 | Open until MySQL is already running | Can be resolved later | Live catalog comparison | Application work on the approved schema | Treating the 25 / 101 / 416 catalog as live-verified. Starting MySQL from this plan | When MySQL is already running | Step 21C id list |
| SCOPE-01 | Deferred. First-release scope boundary approved 2026-10-17 in Step 20R section 6D. SCOPE-01 stays out | Deferred | Recruitment, PMS, funds, and the other inventory-only groups | The approved boundary, which does not include these groups | An audit or build of those modules for the first release | When an owner adds one to the first release | Anisur Rahman, Owner — Universal HRMS Project, 2026-10-17, for the boundary that keeps SCOPE-01 deferred. An addition still needs its own decision |

Employee migration stays blocked until D1, D2, D3, D4 dispositions, and D6 are signed, the exception register is signed, reconciliation has been executed, and a named company approval exists. A company row does not provide that approval.

AUTH-ARCH in Step 20R is **APPROVED**, subject to all Section 14 conditions. Approver: Anisur Rahman, Owner — Universal HRMS Project. Approval date: 2026-10-17. That approval does not change the SEC-CORE-003 row above and does not itself approve the role list. SEC-CORE-004 in Step 20R section 6C is **APPROVED** as the initial role list, by the same approver and date, subject to the conditions in that section. The SEC-CORE-004 row above records that list. It does not authorize a role matrix, permission-flag replacement, directory enforcement, a narrower scope, attendance, payroll, leave, overtime, or migration. SEC-CORE-003 stays unsigned. The first-release scope boundary in Step 20R section 6D is **APPROVED** by the same approver and date. It keeps SCOPE-01 deferred and does not authorize implementation.

## 5. Current checkpoint

The rejoin-message correction is complete. The validation message and the employee screens state the approved rejoin rule. That correction is not the next task.

The L2 Leave-Position Recording Engine remains authorized and implemented under Step 20R section 6I. Approver: Anisur Rahman, Owner. Approval date: 2026-10-18.

The L2 Leave-Position Recording Operator UI is implemented and verified under Step 20R section 6J. Approver: Anisur Rahman / Product Owner/CTO. Approval date: 2026-10-18. The implemented screens are the company-scoped `/session` screens named in that entry: leave-type list/create/rename/status (name and status only); proposed/excepted leave-position list/create/update; movement list/create, including reversals that add a new row. They reuse login, active-company context, the `leave_position_writers` grant, JSON APIs, confirmation refusal, `usable_as_approved_balance=false`, and audit, and they follow the existing `/session` RealSession pattern. Verification on 2026-10-18, without a signed-in browser pass: `npm run build` in `frontend` passed (`tsc --noEmit` and the Vite build). `php artisan test --compact` on LeavePositionOperatorUiContractTest, LeavePositionRecordingTest, and AuthenticationAndMembershipTest passed 25 tests and 284 assertions against `proedge_new_hrms_test`, with transaction rollback. The employee picker loads at most 50 directory pages (750 employees). A movement shows the acting user id, not a display name. Position detail takes the company name from the leave-type list. The G7-UI closeout did not itself authorize a further implementation milestone. It does not overwrite section 6I. It does not authorize approved-balance use, confirmation, booking, payroll, attendance, assignment, role-matrix work, writer-grant UI, migration, or release. AUTH-ARCH and the SEC-CORE-004 role list remain approved subject to their conditions and do not authorize those excluded items.

The first-release scope boundary is approved in Step 20R section 6D. Approver: Anisur Rahman, Owner — Universal HRMS Project. Approval date: 2026-10-17. The boundary is the shared platform and catalog modules `organizationsettings`, `pims`, `payroll`, `leave`, and `attendance`, with only Payroll Basic, Overtime, and OT Management inside payroll. SCOPE-01 stays deferred. The full catalog of 25 modules, 101 submodules, and 416 controllers stays on the roadmap. This approval does not authorize an implementation milestone and does not change any business-rule, security, UAT, migration, or release gate. The evidence register is the later UAT result record. UAT-01, UAT-03, UAT-04, UAT-05, UAT-06, and UAT-07 are **PASS**. UAT-02 is **defined flag checks PASS**. That result does not score the informational role-list sentence and does not approve the role matrix. All seven scenarios passed their defined, approved checks. This is not full release approval. The evidence register records the current UAT fixture state after UAT-04, UAT-05, and UAT-06: Employee A’s temporary display name, one additional Open episode with joining date `2026-01-05`, and department `UAT-DEPT`’s temporary name. Employee B and the empty designation list were unchanged. Those values are test inputs, not approved rules. The recorded session is `http://127.0.0.1:8091` on `proedge_new_hrms_uat`. The restore-verification database `proedge_new_hrms_restore_verify_20261017` remains separate and was not dropped. Tester, date, actual results, and evidence references are the 2026-10-17 Cursor agent session entries in the evidence register. These results do not authorize the next implementation milestone.

The next scoped leave-balance task is owner confirmation of the questions recorded as LEAVE-OPENING. That confirmation is not authorized by this section, and it is not an implementation milestone. An unsigned owner-review worksheet for those questions is `docs/UNIVERSAL_HRMS_LEAVE_OPENING_OWNER_DECISION_WORKSHEET.md`. It does not sign LEAVE-OPENING and does not authorize implementation.

Two unsigned review documents are available for access or attendance approval:

- `docs/UNIVERSAL_HRMS_PRODUCT_SURFACE_AND_ACCESS_MODEL_DECISION.md`
- `docs/UNIVERSAL_HRMS_BD_D16_BD_D17_OWNER_DECISION_WORKSHEET.md`

Section 6D remains a scope boundary only. Section 6I remains the authorized and implemented L2 leave-position recording engine. Section 6J remains the authorized L2 Operator UI, and that UI is implemented and verified. Neither entry authorizes a company migration, approved-balance use, or any excluded item listed with that entry.

The next implementation checkpoint is the L2 Immutable Punch Recording Engine, authorized in Step 20R section 6K. Approver: Anisur Rahman / Product Owner/CTO. Approval date: 2026-10-18. It is not implemented. PUNCH-APPROVED is the immutability rule named in the dependencies below. It is not this authorization. The leave-position approvals are not this authorization.

Registration status: **APPROVED.** This is the named implementation milestone for the bounded L2 Immutable Punch Recording Engine only. It does not overwrite G1–G6, section 6I, or section 6J.

| Field | Content |
| --- | --- |
| Named milestone | Immutable Punch Recording Engine |
| Layer | L2 |
| Bounded scope | Record raw punch events for one employee inside the active company, through one engine with two ingestion paths. Path A is an authorized human operator: manual entry of one raw event, and upload of a raw punch file. Path B is device integration: a controlled submission of raw punch events from an attendance device or an integration service. Both paths store the same event. Each accepted event stores event time, ingestion time, punch identity, and a named source. The named source is not the submitting actor. A human who uploads a device export remains the submitting actor; the file’s source stays the named source. A device or integration service that submits directly is a different submitting actor from that human. The original event stays readable. Later processing must not rewrite it. The engine detects a duplicate event. It rejects a punch identity that is active for two employees in the same company at the same time unless a documented overlap is already recorded. The overlap list stays empty. Company comes from an explicit company context, not from an empty company field and not from legacy `area`. Writer authorization is an explicit server check. It is not a new membership boolean and not a role-matrix change. |
| Explicit exclusions | Daily attendance calculation or status. Grace, lateness, shift selection, cutoff, and worked-day calculation. A day-result row. Punch correction, editing, withdrawal, or deletion. Leave booking, approved balances, accrual, and encashment. Payroll integration and payroll calculation. Employee assignment changes. Production migration or release. A new role matrix, a new membership flag, permission screens, and flag replacement. Naming a vendor, a device protocol, a source-name catalog, a duplicate key, or an overlap for cards `126384` and `132058`. Copying a legacy file layout, `area`, or `balid` into the product rule. |
| Required dependencies | Signed G1 and G2, as documentation rules only. They do not authorize this engine. Existing login and server-selected active company. Employee identity inside that company. PUNCH-CORE-001 through PUNCH-CORE-009, PUNCH-CORE-006, PUNCH-CORE-007, DI-CORE-005, and PUNCH-APPROVED, as the shape and the immutability rule only. SEC-CORE-001 for a state-changing action. AUDIT-CORE-001 for the submitting actor and time on an accepted event. NFR-CORE-006 and NFR-CORE-007 for a failed import and a replaceable external feed. |
| Unresolved prerequisites | The human writer is not named. The device or integration credential is not named. Functional design §3.6 names no attendance user and no SEC-CORE-004 category for attendance. `can_manage_employees` and `can_manage_organization_masters` do not grant this engine. The duplicate match fields are not defined. PUNCH-CONF-002, how a detected duplicate is treated, is unsigned. PUNCH-CONF-001, which sources a company accepts, is unsigned. PUNCH-CONF-004, whether one employee may hold multiple active punch identities, is unsigned. The overlap list is empty. No device protocol is identified. EXT-CLIENT-004 stays inactive until a protocol is identified. AUD-04: the external writer is UNKNOWN, `area` is UNKNOWN, and no in-repository device API was found. |
| Gates that remain deferred | BD-D01, BD-D02, BD-D03, BD-D04, BD-D05, BD-D13, BD-D16, PUNCH-WITHDRAW, SEC-CORE-003, FY-BOUNDARY, SCOPE-01, D1–D6, LEAVE-OPENING, and the role-matrix build. ATT-CORE-001 stays outside this milestone because it is day calculation. |
| Management's actual decision | Authorize the bounded Immutable Punch Recording Engine (L2) milestone only. Preserve all unresolved dependencies and exclusions. |
| Approver name/role | Anisur Rahman / Product Owner/CTO |
| Approval date | 2026-10-18 |
| Conditions or exceptions | This entry authorizes only the named L2 engine. It does not change G1–G6, Step 20R §6I, or Step 20R §6J. It does not sign PUNCH-WITHDRAW or any other unsigned attendance policy. The unresolved prerequisites above stay unresolved. |

**Ingestion paths.** Path A and path B are the two ingestion paths accepted with this milestone. They are not built.

| Path | What the documents support | What stays a dependency |
| --- | --- | --- |
| A. Authorized human operator | An ingestion boundary before calculation (PUNCH-CORE-001). A person may enter one event or submit a file of raw events. The signed-in user is the submitting actor. The named source is a separate stored fact (PUNCH-CORE-003). | Who that person is. The file layout. Which sources the company accepts (PUNCH-CONF-001). The legacy bracket-delimited upload is evidence from AUD-04. It is not the product format. |
| B. Device or integration service | An external feed stays replaceable behind that same boundary (NFR-CORE-007). A named protocol may be enabled only after it is identified (EXT-CLIENT-004). The device or service is the submitting actor. The named source remains distinct from that actor. | The protocol, the vendor, the credential, and how that caller is bound to one company. AUD-04 did not find a device client in the repository. `area` is not a source and is not a company. |

Both paths store event time and ingestion time as different facts (PUNCH-CORE-006). Neither path may assign the company from a blank company field (PUNCH-CORE-007).

**Identity and duplicates.**

- A punch identity maps to an employee inside one company. The identity may differ from the employee code (PUNCH-CORE-008).
- The same punch identity must not be active for two employees in that company at the same time unless a documented overlap is already recorded (PUNCH-CORE-009, DI-CORE-005). No overlap is recorded. Cards `126384` and `132058` are not decided here.
- A duplicate event must be detected (PUNCH-CORE-005). The fields that make two events the same event are not specified. PUNCH-CONF-002 leaves the treatment configurable and unsigned. This milestone does not choose a key, and it does not choose reject, quarantine, or skip. A failed import must not complete as a partial silent write (NFR-CORE-006).
- This milestone does not choose how many active identities one employee may hold (PUNCH-CONF-004).

**Immutability.** PUNCH-APPROVED and PUNCH-CORE-004 keep an accepted event. A later day result, if a later milestone is signed, must be a separate record and must not rewrite the event (ATT-CORE-002 read with functional design §3.6). This milestone does not create that day result. ATT-CORE-001 is calculation and stays out.

PUNCH-WITHDRAW is unsigned. This milestone includes no withdrawal, deletion, or edit. BD-D16’s correction mechanism does not include the raw punch as an editable field. The field list itself stays empty.

**Writer prerequisite.** No writer is named by this approval. Before implementation of a path, the owner must name both of the following, or must name one path and defer the other:

1. The human operator allowed to enter or upload raw punches for one company.
2. The device or integration credential allowed to submit raw punches for one company.

Those two actors are not the same fact as the named source. Naming either one is not a new role, not a membership boolean, and not a mapping of the two existing flags. The leave-position writer list is a different milestone. It is not reused here.

This entry does not sign BD-D01 through BD-D05, BD-D13, BD-D16, PUNCH-WITHDRAW, SEC-CORE-003, FY-BOUNDARY, SCOPE-01, D1–D6, or LEAVE-OPENING. It does not reopen AUD-01 through AUD-06. It does not start MySQL. No company is migration-approved.

## 6. Statement

LEAVE-OPENING remains unsigned. AUTH-ARCH is approved in the Step 20R register, subject to all Section 14 conditions. SEC-CORE-004's initial role list is approved there, and its gate row records that list without authorizing a build. SEC-CORE-003 remains unsigned. The first-release scope boundary is approved in Step 20R section 6D. Section 6I authorizes only the bounded L2 Leave-Position Recording Engine. Section 6J authorizes only the bounded L2 Leave-Position Recording Operator UI. That UI is implemented and verified. Section 6K authorizes only the bounded L2 Immutable Punch Recording Engine. It preserves the unresolved prerequisites and exclusions in that section. It does not sign PUNCH-WITHDRAW or any other unsigned attendance policy. It is not implemented by this documentation change. AUD-01 through AUD-06 were not repeated. The parent `docs` folder was not updated.

INCREMENTAL DELIVERY PLAN PREPARED

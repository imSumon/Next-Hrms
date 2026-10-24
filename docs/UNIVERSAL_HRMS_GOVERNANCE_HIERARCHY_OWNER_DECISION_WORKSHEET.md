# Universal HRMS — Governance Hierarchy Owner Decision Worksheet

Date: 2026-10-18

Status: G1–G6, G7, G7-UI, and the Immutable Punch Recording Engine entry below are signed. Deferred gates named inside those entries stay unsigned. G2 remains the signed L1–L4 classification and does not authorize operational activation. This worksheet does not sign LEAVE-OPENING, SEC-CORE-003, or any gate those entries leave deferred. The punch entry is recorded in Step 20R section 6K.

G1–G7 are temporary labels only. They are not official requirement IDs.

Signing G1–G6, or applying later documentation wording corrections, does **not** authorize implementation. Only a complete, explicitly approved milestone, recorded consistently in Step 20R, the active control center, and gates section 5, can authorize implementation. A blank or partial milestone authorizes nothing. The Immutable Punch Recording Engine entry below is that kind of separate approved milestone. It is recorded in Step 20R section 6K. It does not rewrite G1–G6, G7, or G7-UI.

## 1. What stays in force

Do not reopen these. This worksheet does not change them.

- AUTH-ARCH, subject to all Section 14 conditions (Step 20R section 6B).
- SEC-CORE-004 initial role list, subject to section 6C. The role-matrix build stays excluded. The two membership flags stay unmapped.
- First-release scope boundary (Step 20R section 6D). That approval is not a build authorization. SCOPE-01 stays deferred.
- Employee-foundation rules already in force: ULID identity, company-scoped code, no merge, no silent recode, LIFE-CORE-008 rejoin.
- Migration still requires D1, D2, D3, D4 dispositions, D6, a signed exception register, executed reconciliation, and a named company approval. No company is migration-approved.
- LEAVE-OPENING stays unsigned. It continues to block an approved leave balance and a confirmed leave-balance load. Gates section 4 still permits recording a proposed amount with period, source, basis, variance, and exception status. Signing this worksheet does not require LEAVE-OPENING to be signed in order to record a proposed position.
- LEAVE-CORE-008 stays proposed. BD-D06, BD-D17, FY-BOUNDARY, and SEC-CORE-003 stay unsigned. BD-D15 stays deferred.
- Owner UAT remains PARTIAL. The seven defined approved checks are not release approval.

The next scoped leave-balance task remains owner confirmation of LEAVE-OPENING. That confirmation is not an implementation milestone.

## 2. Conflicts this sitting must resolve

These sentences are both present. This worksheet does not choose between them.

| Location | Sentence |
| --- | --- |
| Authority §3 | “No module, permission model, schema, migration, or attendance or payroll workflow may be implemented because this authority note exists.” |
| Authority §4 | Conflict handling applies when parent `docs` or `legacy-reference\hrms\docs` disagrees. It does not classify two active `next-hrms\docs` sentences. |
| Gates §3 | “Blocks all implementation: none.” A new module, screen, or calculation still requires section 5. |
| Gates §5 | “The next implementation milestone is not yet authorized.” A later milestone needs the applicable owner decisions and the gates in that file. |
| Functional design §2 item 6 | “Attendance shape, leave-position shape, and payroll-snapshot shape. Each stays inactive until its own gate is signed.” |
| Functional design §3.7 | Recording the shape of a proposed position is the limit of that specification. Approved balance, leave pay, and approval workflow stay blocked. |
| Gates §4 LEAVE-OPENING | Permitted before approval: recording a proposed amount. Required before leave-balance approval or a leave-balance load. |

The control center §6 follows gates section 5 and does not repeat the authority blanket.

## 3. Owner decisions

Blank answers stay unsigned. A proposal is not an approval. Do not infer leave writes from `can_manage_employees` or `can_manage_organization_masters`. Do not invent a role or add a membership boolean.

### G1 — Authority hierarchy

Should signed Step 20R decisions, gates section 5 milestone authorization, and gates section 4 blockers govern new implementation work, in place of the authority §3 blanket?

| Field | Content |
| --- | --- |
| Options | A. Yes, as a documentation rule only: Step 20R + gates §5 + gates §4 govern new work. The authority §3 blanket is withdrawn only as a documentation rule after this sitting is signed and the wording correction is applied. Gates section 5 still has no named milestone. This option does not authorize implementation. B. No: the authority blanket stays. C. Revise (attach text). |
| Management's actual decision | **A.** Signed Step 20R decisions record owner decisions. Gates section 5 records authorization for a specifically named implementation milestone. Gates section 4 identifies applicable blockers. The contradictory blanket restriction in the authority file may be corrected only after G1 is formally recorded. G1 does not authorize implementation. |
| Approver name/role | Owner, Owner |
| Approval date | 2026-10-18 |
| Conditions or exceptions | This is a documentation-rule decision only. Gates section 5 still has no named milestone. Authority §3 and §4 were aligned to this G1 rule after the 2026-10-18 signature. This G1 entry is not a G7 milestone and does not authorize implementation. |

### G2 — L1–L4 policy

Accept, revise, or decline the proposed layers below. The text in this section is the **accepted L1–L4 governance policy**, signed 2026-10-18 by Anisur Rahman, Owner. It is the complete policy text for this question. It is not in Step 20R.

Accepted policy:

1. Work is classified as L1 reusable platform, L2 configurable engine, L3 company policy and operational activation, or L4 migration and release.
2. L1 and L2 may proceed only after a named bounded milestone is written in Step 20R with Approver name/role and Approval date, and after the active control center and gates section 5 are updated in the same documentation change. Scope approval (section 6D), AUTH-ARCH, the SEC-CORE-004 list, design documents, and UAT results do not authorize that milestone.
3. An L2 engine stores empty configuration slots. It must not hard-code a company value, treat a legacy dump as a default, or use an unsigned decision as a live rule.
4. LEAVE-OPENING blocks L3 use of an approved leave balance and L4 loading of a confirmed leave balance. It does not, by itself, block an L2 leave-position engine that only records proposed or excepted positions and movements, leaves period and confirmer empty, and refuses confirmation and leave-approval use. That engine still requires its own complete G7 and section 5 milestone. LEAVE-CORE-008 stays proposed. BD-D06 and BD-D17 stay unsigned.
5. The SEC-CORE-004 role-matrix build, permission screens, flag replacement, and any mapping of the two membership flags to the four categories stay excluded. A later unused permission-key store, if separately authorized, must not assign a category or create an approval workflow.
6. L4 remains blocked until D1, D2, D3, D4 dispositions, D6, a signed exception register, executed reconciliation, and a named company approval exist. No company is migration-approved.
7. SCOPE-01, BD-D15, and SEC-CORE-003 stay as already recorded.

Accepting this policy does not authorize a milestone.

| Field | Content |
| --- | --- |
| Options | Accept the seven points above / Revise (attach replacement text) / Decline |
| Management's actual decision | **Accept** (owner-stated Option A: adopt the proposed four-layer L1–L4 governance policy). This matches the worksheet option “Accept the seven points above.” L1 — Reusable Platform Foundation: shared platform mechanisms and security boundaries. This does not authorize company-specific values or a new module without the required milestone authorization. L2 — Configurable Feature Engine: reusable mechanisms that apply company-configurable policies without inventing company defaults. Implementation requires a named, authorized milestone and applicable gates. L3 — Company Policy and Operational Activation: company-specific configuration and operational use of policies require the applicable authorization. L4 — Production Migration and Release: production data migration and release require the applicable migration decisions, reconciliation evidence, exception handling, and named company approval. The four-layer policy is a governance classification. Approval of G2 does not authorize implementation, operational activation, migration, or production release. |
| Approver name/role | Anisur Rahman, Owner |
| Approval date | 2026-10-18 |
| Conditions or exceptions | Governance classification only. Does not authorize implementation, operational activation, migration, or production release. This G2 entry is not a G7 milestone. |

### G3 — Proposed-position writer

Who may record or change a proposed or excepted leave position? Functional design §3.7: no SEC-CORE-004 category includes leave.

| Field | Content |
| --- | --- |
| Options | A. Name a writer (function or user class, not a new role id). B. Hold: no writer is named. A leave-position engine remains ineligible until a later sitting names a writer. |
| Writer, if option A | Organization HR Managers and Organization Admin users, only for the company and organizational scope each user is authorized to manage |
| What is explicitly not a writer | Existing membership flags; company membership alone; a new boolean; a role-matrix permission |
| Management's actual decision | **A.** Authorized writers are Organization HR Managers and Organization Admin users. Scope is only the company and organizational scope each user is authorized to manage. Permitted actions: create and modify proposed or excepted leave-balance records. Prohibited actions: confirmation, approval, and operational use of unapproved balances. Authenticated-user attribution and auditability are required. Existing membership flags are not mapped to leave permissions. Role-matrix functionality and a new permission screen are not introduced. This decision does not authorize implementation. |
| Approver name/role | Anisur Rahman, Owner |
| Approval date | 2026-10-18 |
| Conditions or exceptions | Writers are limited to the company and organizational scope each user is authorized to manage. Until SEC-CORE-003 is signed, that scope is the active company only. Confirmation, approval, and operational use of unapproved balances are prohibited. Do not map `can_manage_employees` or `can_manage_organization_masters` to leave permissions. Do not add a membership boolean. Do not build a role matrix or a permission screen. Authenticated-user attribution and auditability stay required. Owner-named writers are stored as company-scoped `leave_position_writers` rows (user + company), not as membership flags. Active company membership is required for session context and is not a leave grant. This G3 entry is not a G7 milestone and does not authorize implementation. |

Option A does not authorize screens, routes, or a permission model. Option B does not authorize a later engine.

### G4 — Leave-type catalog

LEAVE-CORE-005 depends on LEAVE-CORE-001. BD-D06 (paid / unpaid / partial) stays unsigned.

Signing G4 does not authorize creating a catalog, tables, screens, or code. A later leave-type list, if required, still needs its own complete G7 (or a later sitting) before any implementation.

| Field | Content |
| --- | --- |
| Options | A. A later leave-position milestone, if one is ever authorized under G7, should include an empty company-scoped type list (name and status only). This option does not authorize that milestone. B. A leave-type list should be a separate prior milestone if a leave-position engine is later considered. That prior milestone is not named here and is not authorized by signing G4. C. Hold until a later sitting. |
| Management's actual decision | **A.** A later leave-position milestone, if one is ever authorized under G7, should include an empty company-scoped type list (name and status only). This option does not authorize that milestone. |
| Approver name/role | Anisur Rahman, Owner |
| Approval date | 2026-10-18 |
| Conditions or exceptions | No leave types, paid/unpaid values, or company defaults are invented or seeded. BD-D06 stays unsigned. Creating the empty list still requires the G7 milestone. |

### G5 — Confirmation boundary

LEAVE-CORE-007 names confirmation and a confirmed state. An unapproved position must not become an approved balance.

| Field | Content |
| --- | --- |
| Options | A. Omit confirmation and confirmed-state fields. B. Store them unused: confirmation empty; confirmed state refused; never readable as an approved balance. |
| Management's actual decision | **B.** Store confirmation-related fields unused: confirmation empty; confirmed state refused; never readable as an approved balance. |
| Approver name/role | Anisur Rahman, Owner |
| Approval date | 2026-10-18 |
| Conditions or exceptions | Stored confirmation columns stay empty. No user may confirm a balance. No confirmation workflow or approval authority is introduced. This does not authorize a leave-balance load, leave approval, or a confirmed production balance. |

Neither option authorizes a leave-balance load, leave approval, or a confirmed production balance.

### G6 — Functional design §2 item 6

Does “inactive until its own gate is signed” prohibit any engine build, or only operational use?

| Field | Content |
| --- | --- |
| Options | A. Operational use only: approved balance, approval workflow, and load stay blocked until the named section 4 gate. A recording engine still needs a later complete G7 and section 5 milestone. This option does not authorize that engine. B. Any engine build is prohibited until that shape’s section 4 gate is signed (for leave, LEAVE-OPENING). C. Revise (attach text). |
| Management's actual decision | **A.** Operational use only: approved balance, approval workflow, and load stay blocked until the named section 4 gate. A recording engine still needs a later complete G7 and section 5 milestone. This option does not authorize that engine. |
| Approver name/role | Anisur Rahman, Owner |
| Approval date | 2026-10-18 |
| Conditions or exceptions | LEAVE-OPENING stays unsigned. Approved-balance use, leave booking, and operational leave calculation stay blocked. This G6 entry is not a G7 milestone. |

Option A does not require LEAVE-OPENING to be signed in order to record a proposed position. Option B would hold any leave-position engine until LEAVE-OPENING is signed. Neither option signs LEAVE-OPENING.

### G7 — Named implementation milestone

Leave blank unless the owner names one. This worksheet does not select a milestone.

A G7 milestone is authorized only when all of the following are filled: named milestone, bounded scope, explicit exclusions, required dependencies/gates, approver name and role, and approval date. A blank or partial G7 is unsigned and authorizes nothing. The same complete G7 must then be recorded in Step 20R, the active control center, and gates section 5 in the same documentation change.

| Field | Content |
| --- | --- |
| Named milestone | Leave-Position Recording Engine |
| Layer (L1 / L2 / L3 / L4), if G2 is accepted | L2 |
| Bounded scope | Record proposed or excepted leave positions and generic movements/reversals only. Include an empty company-scoped leave-type list (name and status only). Store proposed amount, source evidence, stated basis, and period evidence (label or start/end) as evidence only — not as an operational leave year. Writer grants are owner-named `leave_position_writers` rows per company. Confirmation columns exist unused and cannot be set. JSON never treats a row as an approved balance. Deliverables: company-scoped schema; JSON write/read API behind active-company context and the writer grant; audit of actor and time; focused automated tests. |
| Explicit exclusions | Approved-balance use; confirmation or approval of balances; confirmed opening-balance load; entitlement, accrual, carry-forward, expiry, or encashment calculations; leave-request/approval workflow or operational booking; LEAVE-CORE-008; BD-D06; BD-D17; payroll; attendance; assignment; role matrix; flag mapping; new membership boolean; permission screens; company-specific type or leave-year defaults; production migration, company import, or production release; narrower-than-company scope (SEC-CORE-003); hourly-leave module 30. Movement `direction` is a recorded statement, not a closed kind list. |
| Required dependencies and gates signed first | G1; G2; G3 Option A; G4 Option A; G5 Option B; G6 Option A. Existing login and active-company isolation. |
| Gates that remain deferred | LEAVE-OPENING; LEAVE-CORE-008; BD-D06; BD-D17; FY-BOUNDARY; SEC-CORE-003; D1–D6; SCOPE-01; role-matrix build. |
| Management's actual decision | **Authorize** the named L2 Leave-Position Recording Engine only, within the bounded scope and exclusions above. |
| Approver name/role | Anisur Rahman, Owner |
| Approval date | 2026-10-18 |
| Conditions or exceptions | Signing G3–G6 does not authorize implementation. This G7 authorizes only the bounded L2 engine. Empty slots stay empty. Period evidence is not an operational leave year. Writer identification uses the owner-named company-scoped list, not membership flags. SEC-CORE-003 stays unsigned; enforced scope is the active company. |

If G7 stays blank or is only partly filled, no implementation milestone is authorized by this sitting.

### G7-UI — Leave-Position Recording Operator UI

Status: **APPROVED.** This is a new G7-style milestone. It does not overwrite the signed G7 Leave-Position Recording Engine above or Step 20R §6I.

| Field | Content |
| --- | --- |
| Named milestone | Leave-Position Recording Operator UI |
| Layer (L1 / L2 / L3 / L4), if G2 is accepted | L2 |
| Bounded scope | Company-scoped screens for the already-built L2 recording engine only: leave-type list/create/rename/status (name and status only); proposed/excepted leave-position list/create/update; movement list/create, including reversals that add a new row. Reuse existing login, active-company context, `leave_position_writers` grant, JSON APIs, confirmation refusal, `usable_as_approved_balance=false`, and audit. Follow the existing `/session` RealSession pattern used for department and designation masters. No new schema, flags, or permission model. |
| Explicit exclusions | Approved-balance use or display as a usable balance; confirmation controls; confirmed load; booking/approval workflow; accrual, carry-forward, expiry, encashment; payroll; attendance; assignment; role matrix; flag mapping; new membership boolean; permission screens; writer-grant UI; company-specific defaults; migration or production release; preview/fixture `/session` data paths; Blade rebuild of the engine. |
| Required dependencies and gates signed first | Signed G1–G6; signed G7 Leave-Position Recording Engine (Step 20R §6I); existing authentication, company context, and writer grant. |
| Gates that remain deferred | LEAVE-OPENING; LEAVE-CORE-008; BD-D06; BD-D17; FY-BOUNDARY; SEC-CORE-003; D1–D6; SCOPE-01; role-matrix build. |
| Management's actual decision | Authorize the named L2 Operator UI only. |
| Approver name/role | Anisur Rahman / Product Owner/CTO |
| Approval date | 2026-10-18 |
| Conditions or exceptions | This G7-UI authorizes only the named Operator UI. It does not change the signed G7 engine milestone. Writer grants remain CLI-only. Documentation of the signed entry is recorded in Step 20R §6J, the active control center, and gates section 5. |

### Immutable Punch Recording Engine

Status: **APPROVED.** This is a separate signed milestone. It does not overwrite G1–G6, the G7 Leave-Position Recording Engine above, G7-UI, Step 20R §6I, or Step 20R §6J. It is not implemented by this documentation change. PUNCH-APPROVED is the immutability rule named in the dependencies below. It is not this authorization. The leave-position approvals are not this authorization.

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

## 4. After a signed sitting

Copy only signed fields into Step 20R. If G7 is complete, update the active control center and gates section 5 in the same documentation change so they do not give two next-task statements.

If G1 is signed as option A, later wording may replace the authority §3 blanket and add an intra-`next-hrms` rule to authority §4. If G6 is signed, later wording may replace functional design §2 item 6 with the accepted meaning. Those wording corrections are documentation only. They do not authorize implementation.

## 5. Statement

No application code, schema, configuration, database, or Git state was changed by this documentation update. LEAVE-OPENING remains unsigned. G1–G6, the G7 Leave-Position Recording Engine, and G7-UI stay as signed above and were not rewritten by the Immutable Punch Recording Engine entry. That entry is approved in this worksheet, in Step 20R section 6K, in the active control center, and in gates section 5. Its unresolved prerequisites and exclusions stay unresolved. It is not implemented by this documentation update. PUNCH-WITHDRAW stays unsigned.

PUNCH ENGINE APPROVED. DEFERRED GATES IN THAT ENTRY STAY UNSIGNED.

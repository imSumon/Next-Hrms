# Universal HRMS — Pending Milestone: Immutable Punch Recording Engine (L2)

Date: 2026-10-18

Status: **Pending Owner Approval.** This file is a proposal for a new milestone. It does not fill Step 20R. It does not change G1–G6, Step 20R §6I, or Step 20R §6J. It does not update the active control center or gates section 5. Approver name/role and approval date are blank. PUNCH-APPROVED is not this authorization. The leave-position approvals are not this authorization. No implementation is authorized.

The same text may be copied into Step 20R as a new section after §6J, the governance worksheet, the active control center, and gates section 5 only after the owner supplies the approver name/role and the approval date. Until those two fields are written, this entry stays pending.

## 1. Pending milestone entry

This table uses the Step 20R implementation-milestone fields. Blank decision, approver, and date fields stay blank.

| Field | Content |
| --- | --- |
| Named milestone | Immutable Punch Recording Engine |
| Layer | L2 |
| Bounded scope | Record raw punch events for one employee inside the active company, through one engine with two ingestion paths. Path A is an authorized human operator: manual entry of one raw event, and upload of a raw punch file. Path B is device integration: a controlled submission of raw punch events from an attendance device or an integration service. Both paths store the same event. Each accepted event stores event time, ingestion time, punch identity, and a named source. The named source is not the submitting actor. A human who uploads a device export remains the submitting actor; the file’s source stays the named source. A device or integration service that submits directly is a different submitting actor from that human. The original event stays readable. Later processing must not rewrite it. The engine detects a duplicate event. It rejects a punch identity that is active for two employees in the same company at the same time unless a documented overlap is already recorded. The overlap list stays empty. Company comes from an explicit company context, not from an empty company field and not from legacy `area`. Writer authorization is an explicit server check. It is not a new membership boolean and not a role-matrix change. |
| Explicit exclusions | Daily attendance calculation or status. Grace, lateness, shift selection, cutoff, and worked-day calculation. A day-result row. Punch correction, editing, withdrawal, or deletion. Leave booking, approved balances, accrual, and encashment. Payroll integration and payroll calculation. Employee assignment changes. Production migration or release. A new role matrix, a new membership flag, permission screens, and flag replacement. Naming a vendor, a device protocol, a source-name catalog, a duplicate key, or an overlap for cards `126384` and `132058`. Copying a legacy file layout, `area`, or `balid` into the product rule. |
| Required dependencies | Signed G1 and G2, as documentation rules only. They do not authorize this engine. Existing login and server-selected active company. Employee identity inside that company. PUNCH-CORE-001 through PUNCH-CORE-009, PUNCH-CORE-006, PUNCH-CORE-007, DI-CORE-005, and PUNCH-APPROVED, as the shape and the immutability rule only. SEC-CORE-001 for a state-changing action. AUDIT-CORE-001 for the submitting actor and time on an accepted event. NFR-CORE-006 and NFR-CORE-007 for a failed import and a replaceable external feed. |
| Unresolved prerequisites | The human writer is not named. The device or integration credential is not named. Functional design §3.6 names no attendance user and no SEC-CORE-004 category for attendance. `can_manage_employees` and `can_manage_organization_masters` do not grant this engine. The duplicate match fields are not defined. PUNCH-CONF-002, how a detected duplicate is treated, is unsigned. PUNCH-CONF-001, which sources a company accepts, is unsigned. PUNCH-CONF-004, whether one employee may hold multiple active punch identities, is unsigned. The overlap list is empty. No device protocol is identified. EXT-CLIENT-004 stays inactive until a protocol is identified. AUD-04: the external writer is UNKNOWN, `area` is UNKNOWN, and no in-repository device API was found. |
| Gates that remain deferred | BD-D01, BD-D02, BD-D03, BD-D04, BD-D05, BD-D13, BD-D16, PUNCH-WITHDRAW, SEC-CORE-003, FY-BOUNDARY, SCOPE-01, D1–D6, LEAVE-OPENING, and the role-matrix build. ATT-CORE-001 stays outside this milestone because it is day calculation. |
| Management's actual decision | |
| Approver name/role | |
| Approval date | |
| Conditions or exceptions | |
| Registration status | **Pending Owner Approval.** |

## 2. What the two paths may include

Path A and path B are proposals for the owner to accept with the milestone. They are not built.

| Path | What the documents support | What stays a dependency |
| --- | --- | --- |
| A. Authorized human operator | An ingestion boundary before calculation (PUNCH-CORE-001). A person may enter one event or submit a file of raw events. The signed-in user is the submitting actor. The named source is a separate stored fact (PUNCH-CORE-003). | Who that person is. The file layout. Which sources the company accepts (PUNCH-CONF-001). The legacy bracket-delimited upload is evidence from AUD-04. It is not the product format. |
| B. Device or integration service | An external feed stays replaceable behind that same boundary (NFR-CORE-007). A named protocol may be enabled only after it is identified (EXT-CLIENT-004). The device or service is the submitting actor. The named source remains distinct from that actor. | The protocol, the vendor, the credential, and how that caller is bound to one company. AUD-04 did not find a device client in the repository. `area` is not a source and is not a company. |

Both paths store event time and ingestion time as different facts (PUNCH-CORE-006). Neither path may assign the company from a blank company field (PUNCH-CORE-007).

## 3. Identity and duplicates

These sentences are the limit of the signed design.

- A punch identity maps to an employee inside one company. The identity may differ from the employee code (PUNCH-CORE-008).
- The same punch identity must not be active for two employees in that company at the same time unless a documented overlap is already recorded (PUNCH-CORE-009, DI-CORE-005). No overlap is recorded. Cards `126384` and `132058` are not decided here.
- A duplicate event must be detected (PUNCH-CORE-005). The fields that make two events the same event are not specified. PUNCH-CONF-002 leaves the treatment configurable and unsigned. This proposal does not choose a key, and it does not choose reject, quarantine, or skip. A failed import must not complete as a partial silent write (NFR-CORE-006).
- This proposal does not choose how many active identities one employee may hold (PUNCH-CONF-004).

## 4. Immutability

PUNCH-APPROVED and PUNCH-CORE-004 keep an accepted event. A later day result, if a later milestone is signed, must be a separate record and must not rewrite the event (ATT-CORE-002 read with functional design §3.6). This milestone does not create that day result. ATT-CORE-001 is calculation and stays out.

PUNCH-WITHDRAW is unsigned. This milestone includes no withdrawal, deletion, or edit. BD-D16’s correction mechanism does not include the raw punch as an editable field. The field list itself stays empty.

## 5. Writer prerequisite

No writer is proposed. The owner must name both of the following before implementation, or must name one path and defer the other:

1. The human operator allowed to enter or upload raw punches for one company.
2. The device or integration credential allowed to submit raw punches for one company.

Those two actors are not the same fact as the named source. Naming either one is not a new role, not a membership boolean, and not a mapping of the two existing flags. The leave-position writer list is a different milestone. It is not reused here by this proposal.

## 6. What this file does not do

It does not authorize schema, an API, a screen, a device adapter, or a test. It does not sign BD-D01 through BD-D05, BD-D13, BD-D16, PUNCH-WITHDRAW, SEC-CORE-003, FY-BOUNDARY, SCOPE-01, D1–D6, or LEAVE-OPENING. It does not reopen AUD-01 through AUD-06. It does not start MySQL. No company is migration-approved.

PENDING OWNER APPROVAL

# Universal HRMS — Step 20 Final Employee Code Decision

Date: 2026-10-10

Status: approved business decision. No database, table, foreign key, migration, or application code was created.

This decision closes employee-code reuse. It does not reopen the location employment fact, `organization_reference_id`, shift-candidate overlap, `weekday_set`, or any other approved Step 20 encoding. D01–D18 and BD-D15 are unchanged.

## Decision

Employee codes must not be reused within the same company.

`employee_code` is permanently associated with one employee identity in that company. Separation does not release the code. A different employee identity must never receive a code that company has already used.

## Business rationale

The employee row survives separation, and rejoin adds an episode on that same row. If separation released the code to a new identity, rejoin would create two active holders of one code. Forbidding reuse keeps one code on one identity for the life of the company. Historical rows stay queryable without deleting anyone to free a code.

## Employee identity rule

The employee identity is the `employees` row and its ULID `id`. Employment episodes are periods of employment. An episode does not create a person. Two identities never share one `employee_code` inside one company.

There is no `employees.status` column. Open, inactive, and separated remain `employment_episodes.episode_status`. None of those states moves the code to another identity.

## Separation behavior

Separation records `episode_status` as separated and stores the last working date when the source has one. The `employees` row and its `employee_code` stay. The code is not cleared, not reassigned, and not deleted.

## Rejoin behavior

A separated employee who rejoins keeps:

- the same `employees` row and ULID
- the same `employee_code`
- a new employment episode

The previous episode remains.

## Migration implication

Migration preserves the existing employee-code-to-employee-identity relationship. Two identities are not merged because their codes match. A code is not silently moved from one identity to another.

Where legacy data has two people with the same code in one company, that collision is an explicit migration exception. It is not repaired by reassigning a code. Historical attendance, payroll, and other rows stay traceable to the original employee identity.

## Uniqueness requirement

The target constraint is:

`UNIQUE (company_id, employee_code)`

It covers every employee row in the company, including separated employees. No active-only predicate is used. `employees` has no status column, and the code is not released by an episode status.

## Legacy exception handling

Dirty or duplicate legacy codes are listed as migration exceptions. They are not solved by deleting a separated employee, by merging two identities, or by issuing the code to a new identity. The target rule still stands for data that passes the exception review.

## Consistency

LIFE-CORE-008 is satisfied: rejoin is a new episode on the same identity and the same code. The migration rule against merging two people is satisfied. Statements in the schema specification, the logical and physical architectures, the migration architecture, the mapping workbook, and earlier Step 20 notes that said reuse was not decided are closed by this decision.

## Step 20 readiness

READY FOR STEP 20 PHYSICAL IMPLEMENTATION

No unresolved Step 20 blocker remains in the approved decision set. This document does not create the unique constraint.

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

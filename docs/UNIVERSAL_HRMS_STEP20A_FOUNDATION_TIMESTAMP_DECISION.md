# Step 20A Foundation Timestamp Decision

Date: 2026-10-10

Status: explicit physical-design decision. No migration file, database, or application code was changed.

This decision applies only to the four foundation tables:

- `companies`
- `branches`
- `location_nodes`
- `organization_nodes`

## Decision

`created_at` and `updated_at` on those four tables use PostgreSQL `timestamptz`.

These columns are creation and modification instants. One type for both columns keeps application behavior consistent across the foundation tables.

## What this decision does not change

The target schema specification requires `created_at`. It gives insert-only tables no `updated_at`. It does not name a PostgreSQL type for either column.

Step 20 physical decision section 6 remains the frozen rule for the columns it names:

| Role | PostgreSQL type | Named fields |
| --- | --- | --- |
| Real event and audit instants | `timestamptz` | `event_at`, `ingested_at`, `acted_at`, `calculated_at`, `finalized_at`, `started_at`, `action_at`, `boundary_at` |
| Business, work, payroll, and effective dates | `date` | `work_date`, `roster_work_date`, `holiday_date`, `joining_date`, `last_working_date`, `period_start`, `period_end`, `effective_from`, `effective_to` |
| Wall-clock shift schedule times | `time` | `start_time`, `end_time`, `punch_window_start`, `punch_window_end` |

That section says the approval is not `timestamptz` on every temporal column. This foundation decision does not reopen that section and does not assign `timestamptz` to business dates or shift clock times.

`created_at` and `updated_at` were absent from all three lists. Their type for these four tables is set here for the first time.

## Scope

The four existing foundation migration files already declare `created_at` and `updated_at` as `timestamptz`. Those files were not edited to record this decision. No migration was run. No database was connected or modified.

## Safety

Production modified: NO

Legacy databases modified: NO

Migrations executed: NO

Migration files modified: NO

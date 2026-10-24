<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
ALTER TABLE attendance_policies
    ADD COLUMN working_day_code text NULL,
    ADD COLUMN non_working_day_code text NULL,
    ADD COLUMN non_working_status_code text NULL,
    ADD COLUMN non_working_attended_status_code text NULL;

ALTER TABLE attendance_policies DROP CONSTRAINT attendance_policies_punch_selection_chk;
ALTER TABLE attendance_policies ADD CONSTRAINT attendance_policies_punch_selection_chk
    CHECK (punch_selection IN ('first_and_last', 'margin_first_and_last'));

CREATE TABLE roster_source_days (
    id text NOT NULL,
    company_id text NOT NULL,
    employee_id text NOT NULL,
    shift_id text NOT NULL,
    work_date date NOT NULL,
    source_day_code text NOT NULL,
    always_present boolean NOT NULL,
    source_roster_flag text NOT NULL,
    window_starts_at timestamptz NOT NULL,
    window_ends_at timestamptz NOT NULL,
    shift_starts_at timestamptz NOT NULL,
    shift_ends_at timestamptz NOT NULL,
    absent_after_at timestamptz NOT NULL,
    source_system text NOT NULL,
    source_key text NOT NULL,
    source_catalog_key text NOT NULL,
    source_employee_key text NOT NULL,
    source_shift_key text NOT NULL,
    created_at timestamptz NOT NULL,
    updated_at timestamptz NOT NULL,
    CONSTRAINT roster_source_days_pkey PRIMARY KEY (id),
    CONSTRAINT roster_source_days_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT roster_source_days_code_chk CHECK (source_day_code IN ('W', 'H', 'O')),
    CONSTRAINT roster_source_days_window_chk CHECK (window_ends_at >= window_starts_at),
    CONSTRAINT roster_source_days_source_unique UNIQUE (company_id, source_system, source_key),
    CONSTRAINT roster_source_days_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id),
    CONSTRAINT roster_source_days_employee_fkey FOREIGN KEY (company_id, employee_id) REFERENCES employees (company_id, id),
    CONSTRAINT roster_source_days_shift_fkey FOREIGN KEY (company_id, shift_id) REFERENCES shifts (company_id, id)
);

CREATE UNIQUE INDEX roster_source_days_employee_date_unique
    ON roster_source_days (company_id, employee_id, work_date, source_system);

ALTER TABLE roster_source_days
    ADD CONSTRAINT roster_source_days_company_id_id_unique UNIQUE (company_id, id);

ALTER TABLE attendance_day_result_versions
    ADD COLUMN roster_source_day_id text NULL,
    ADD CONSTRAINT attendance_day_result_versions_roster_source_fkey
        FOREIGN KEY (company_id, roster_source_day_id) REFERENCES roster_source_days (company_id, id);

CREATE TABLE attendance_periods (
    id text NOT NULL,
    company_id text NOT NULL,
    period_start date NOT NULL,
    period_end date NOT NULL,
    period_state text NOT NULL,
    finalized_by_user_id text NULL,
    finalized_at timestamptz NULL,
    reopened_by_user_id text NULL,
    reopened_at timestamptz NULL,
    created_at timestamptz NOT NULL,
    updated_at timestamptz NOT NULL,
    CONSTRAINT attendance_periods_pkey PRIMARY KEY (id),
    CONSTRAINT attendance_periods_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT attendance_periods_range_chk CHECK (period_end >= period_start),
    CONSTRAINT attendance_periods_state_chk CHECK (period_state IN ('open', 'finalized')),
    CONSTRAINT attendance_periods_company_range_unique UNIQUE (company_id, period_start, period_end),
    CONSTRAINT attendance_periods_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id),
    CONSTRAINT attendance_periods_finalized_by_fkey FOREIGN KEY (finalized_by_user_id) REFERENCES users (id),
    CONSTRAINT attendance_periods_reopened_by_fkey FOREIGN KEY (reopened_by_user_id) REFERENCES users (id),
    CONSTRAINT attendance_periods_company_id_id_unique UNIQUE (company_id, id)
);

CREATE TABLE attendance_period_events (
    id text NOT NULL,
    company_id text NOT NULL,
    attendance_period_id text NOT NULL,
    event_kind text NOT NULL,
    acting_user_id text NOT NULL,
    reason text NULL,
    occurred_at timestamptz NOT NULL,
    created_at timestamptz NOT NULL,
    CONSTRAINT attendance_period_events_pkey PRIMARY KEY (id),
    CONSTRAINT attendance_period_events_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT attendance_period_events_kind_chk CHECK (event_kind IN ('opened', 'finalized', 'reopened')),
    CONSTRAINT attendance_period_events_reopen_reason_chk CHECK (event_kind <> 'reopened' OR btrim(coalesce(reason, '')) <> ''),
    CONSTRAINT attendance_period_events_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id),
    CONSTRAINT attendance_period_events_period_fkey FOREIGN KEY (company_id, attendance_period_id) REFERENCES attendance_periods (company_id, id),
    CONSTRAINT attendance_period_events_actor_fkey FOREIGN KEY (acting_user_id) REFERENCES users (id)
);

CREATE TABLE attendance_payroll_handoffs (
    id text NOT NULL,
    company_id text NOT NULL,
    attendance_period_id text NOT NULL,
    handoff_state text NOT NULL,
    exported_by_user_id text NOT NULL,
    exported_at timestamptz NOT NULL,
    employee_count integer NOT NULL,
    line_count integer NOT NULL,
    present_count integer NOT NULL,
    incomplete_count integer NOT NULL,
    non_working_count integer NOT NULL,
    other_status_count integer NOT NULL,
    late_seconds_total integer NOT NULL,
    created_at timestamptz NOT NULL,
    updated_at timestamptz NOT NULL,
    CONSTRAINT attendance_payroll_handoffs_pkey PRIMARY KEY (id),
    CONSTRAINT attendance_payroll_handoffs_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT attendance_payroll_handoffs_state_chk CHECK (handoff_state IN ('exported', 'superseded')),
    CONSTRAINT attendance_payroll_handoffs_counts_chk CHECK (employee_count >= 0 AND line_count >= 0 AND present_count >= 0 AND incomplete_count >= 0 AND non_working_count >= 0 AND other_status_count >= 0 AND late_seconds_total >= 0),
    CONSTRAINT attendance_payroll_handoffs_reconciled_chk CHECK (line_count = present_count + incomplete_count + non_working_count + other_status_count),
    CONSTRAINT attendance_payroll_handoffs_company_id_id_unique UNIQUE (company_id, id),
    CONSTRAINT attendance_payroll_handoffs_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id),
    CONSTRAINT attendance_payroll_handoffs_period_fkey FOREIGN KEY (company_id, attendance_period_id) REFERENCES attendance_periods (company_id, id),
    CONSTRAINT attendance_payroll_handoffs_exported_by_fkey FOREIGN KEY (exported_by_user_id) REFERENCES users (id)
);

CREATE UNIQUE INDEX attendance_payroll_handoffs_one_exported
    ON attendance_payroll_handoffs (company_id, attendance_period_id)
    WHERE handoff_state = 'exported';

CREATE TABLE attendance_payroll_handoff_lines (
    id text NOT NULL,
    company_id text NOT NULL,
    attendance_payroll_handoff_id text NOT NULL,
    employee_id text NOT NULL,
    work_date date NOT NULL,
    result_version_id text NOT NULL,
    day_class text NOT NULL,
    status_code text NULL,
    incomplete boolean NOT NULL,
    late_seconds integer NULL,
    early_seconds integer NULL,
    created_at timestamptz NOT NULL,
    CONSTRAINT attendance_payroll_handoff_lines_pkey PRIMARY KEY (id),
    CONSTRAINT attendance_payroll_handoff_lines_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT attendance_payroll_handoff_lines_class_chk CHECK (day_class IN ('present', 'incomplete', 'non_working', 'other')),
    CONSTRAINT attendance_payroll_handoff_lines_day_unique UNIQUE (attendance_payroll_handoff_id, employee_id, work_date),
    CONSTRAINT attendance_payroll_handoff_lines_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id),
    CONSTRAINT attendance_payroll_handoff_lines_handoff_fkey FOREIGN KEY (company_id, attendance_payroll_handoff_id) REFERENCES attendance_payroll_handoffs (company_id, id),
    CONSTRAINT attendance_payroll_handoff_lines_employee_fkey FOREIGN KEY (company_id, employee_id) REFERENCES employees (company_id, id),
    CONSTRAINT attendance_payroll_handoff_lines_version_fkey FOREIGN KEY (company_id, result_version_id) REFERENCES attendance_day_result_versions (company_id, id)
);

CREATE FUNCTION reject_attendance_period_event_mutation() RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    RAISE EXCEPTION 'Attendance period events are immutable.';
END;
$$;

CREATE TRIGGER attendance_period_events_no_update
    BEFORE UPDATE ON attendance_period_events
    FOR EACH ROW
    EXECUTE FUNCTION reject_attendance_period_event_mutation();

CREATE TRIGGER attendance_period_events_no_delete
    BEFORE DELETE ON attendance_period_events
    FOR EACH ROW
    EXECUTE FUNCTION reject_attendance_period_event_mutation();

CREATE FUNCTION reject_attendance_payroll_handoff_line_mutation() RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    RAISE EXCEPTION 'Attendance payroll handoff lines are immutable.';
END;
$$;

CREATE TRIGGER attendance_payroll_handoff_lines_no_update
    BEFORE UPDATE ON attendance_payroll_handoff_lines
    FOR EACH ROW
    EXECUTE FUNCTION reject_attendance_payroll_handoff_line_mutation();

CREATE TRIGGER attendance_payroll_handoff_lines_no_delete
    BEFORE DELETE ON attendance_payroll_handoff_lines
    FOR EACH ROW
    EXECUTE FUNCTION reject_attendance_payroll_handoff_line_mutation();
SQL);
    }

    public function down(): void
    {
        throw new RuntimeException('Roster source days and attendance periods are retained. Rollback is not authorized.');
    }
};

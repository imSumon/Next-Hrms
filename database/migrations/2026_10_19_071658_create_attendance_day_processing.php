<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE attendance_policies (
    id text NOT NULL,
    company_id text NOT NULL,
    timezone text NOT NULL,
    shift_winner text NOT NULL,
    punch_selection text NOT NULL,
    grace_seconds integer NULL,
    late_measured_from text NULL,
    early_threshold_seconds integer NULL,
    present_status_code text NULL,
    incomplete_status_code text NULL,
    correction_fields text NOT NULL,
    created_at timestamptz NOT NULL,
    updated_at timestamptz NOT NULL,
    CONSTRAINT attendance_policies_pkey PRIMARY KEY (id),
    CONSTRAINT attendance_policies_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT attendance_policies_company_unique UNIQUE (company_id),
    CONSTRAINT attendance_policies_timezone_chk CHECK (btrim(timezone) <> ''),
    CONSTRAINT attendance_policies_shift_winner_chk CHECK (shift_winner IN ('default', 'roster')),
    CONSTRAINT attendance_policies_punch_selection_chk CHECK (punch_selection = 'first_and_last'),
    CONSTRAINT attendance_policies_grace_chk CHECK (grace_seconds IS NULL OR grace_seconds >= 0),
    CONSTRAINT attendance_policies_late_measured_chk CHECK (late_measured_from IS NULL OR late_measured_from IN ('shift_start', 'grace_end')),
    CONSTRAINT attendance_policies_early_chk CHECK (early_threshold_seconds IS NULL OR early_threshold_seconds >= 0),
    CONSTRAINT attendance_policies_correction_fields_chk CHECK (correction_fields IN ('', 'status_code')),
    CONSTRAINT attendance_policies_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id)
);

CREATE TABLE shifts (
    id text NOT NULL,
    company_id text NOT NULL,
    name text NOT NULL,
    source_system text NULL,
    source_key text NULL,
    created_at timestamptz NOT NULL,
    updated_at timestamptz NOT NULL,
    CONSTRAINT shifts_pkey PRIMARY KEY (id),
    CONSTRAINT shifts_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT shifts_name_chk CHECK (btrim(name) <> ''),
    CONSTRAINT shifts_company_id_id_unique UNIQUE (company_id, id),
    CONSTRAINT shifts_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id)
);

CREATE UNIQUE INDEX shifts_company_source_unique
    ON shifts (company_id, source_system, source_key)
    WHERE source_key IS NOT NULL;

CREATE TABLE shift_versions (
    id text NOT NULL,
    company_id text NOT NULL,
    shift_id text NOT NULL,
    start_time time NOT NULL,
    end_time time NOT NULL,
    crosses_midnight boolean NOT NULL,
    effective_from date NOT NULL,
    effective_to date NULL,
    source_system text NULL,
    source_key text NULL,
    created_at timestamptz NOT NULL,
    updated_at timestamptz NOT NULL,
    CONSTRAINT shift_versions_pkey PRIMARY KEY (id),
    CONSTRAINT shift_versions_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT shift_versions_window_chk CHECK (crosses_midnight OR end_time > start_time),
    CONSTRAINT shift_versions_effective_chk CHECK (effective_to IS NULL OR effective_to >= effective_from),
    CONSTRAINT shift_versions_company_id_id_unique UNIQUE (company_id, id),
    CONSTRAINT shift_versions_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id),
    CONSTRAINT shift_versions_shift_fkey FOREIGN KEY (company_id, shift_id) REFERENCES shifts (company_id, id)
);

CREATE TABLE shift_assignment_candidates (
    id text NOT NULL,
    company_id text NOT NULL,
    employee_id text NOT NULL,
    shift_id text NOT NULL,
    candidate_kind text NOT NULL,
    roster_work_date date NULL,
    source_system text NULL,
    source_key text NULL,
    created_at timestamptz NOT NULL,
    updated_at timestamptz NOT NULL,
    CONSTRAINT shift_assignment_candidates_pkey PRIMARY KEY (id),
    CONSTRAINT shift_assignment_candidates_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT shift_assignment_candidates_kind_chk CHECK (candidate_kind IN ('default', 'roster')),
    CONSTRAINT shift_assignment_candidates_roster_date_chk CHECK (
        (candidate_kind = 'default' AND roster_work_date IS NULL)
        OR (candidate_kind = 'roster' AND roster_work_date IS NOT NULL)
    ),
    CONSTRAINT shift_assignment_candidates_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id),
    CONSTRAINT shift_assignment_candidates_employee_fkey FOREIGN KEY (company_id, employee_id) REFERENCES employees (company_id, id),
    CONSTRAINT shift_assignment_candidates_shift_fkey FOREIGN KEY (company_id, shift_id) REFERENCES shifts (company_id, id)
);

CREATE UNIQUE INDEX shift_assignment_candidates_one_default
    ON shift_assignment_candidates (company_id, employee_id)
    WHERE candidate_kind = 'default';

CREATE UNIQUE INDEX shift_assignment_candidates_one_roster_date
    ON shift_assignment_candidates (company_id, employee_id, roster_work_date)
    WHERE candidate_kind = 'roster';

CREATE TABLE attendance_day_result_versions (
    id text NOT NULL,
    company_id text NOT NULL,
    employee_id text NOT NULL,
    work_date date NOT NULL,
    shift_version_id text NOT NULL,
    status_code text NULL,
    incomplete boolean NOT NULL,
    late_seconds integer NULL,
    early_seconds integer NULL,
    selected_in_event_id text NULL,
    selected_out_event_id text NULL,
    generation_kind text NOT NULL,
    correction_id text NULL,
    previous_version_id text NULL,
    calculated_at timestamptz NOT NULL,
    created_at timestamptz NOT NULL,
    CONSTRAINT attendance_day_result_versions_pkey PRIMARY KEY (id),
    CONSTRAINT attendance_day_result_versions_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT attendance_day_result_versions_generation_chk CHECK (generation_kind IN ('calculated', 'correction')),
    CONSTRAINT attendance_day_result_versions_late_chk CHECK (late_seconds IS NULL OR late_seconds >= 0),
    CONSTRAINT attendance_day_result_versions_early_chk CHECK (early_seconds IS NULL OR early_seconds >= 0),
    CONSTRAINT attendance_day_result_versions_company_id_id_unique UNIQUE (company_id, id),
    CONSTRAINT attendance_day_result_versions_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id),
    CONSTRAINT attendance_day_result_versions_employee_fkey FOREIGN KEY (company_id, employee_id) REFERENCES employees (company_id, id),
    CONSTRAINT attendance_day_result_versions_shift_fkey FOREIGN KEY (company_id, shift_version_id) REFERENCES shift_versions (company_id, id),
    CONSTRAINT attendance_day_result_versions_in_fkey FOREIGN KEY (company_id, selected_in_event_id) REFERENCES punch_events (company_id, id),
    CONSTRAINT attendance_day_result_versions_out_fkey FOREIGN KEY (company_id, selected_out_event_id) REFERENCES punch_events (company_id, id),
    CONSTRAINT attendance_day_result_versions_previous_fkey FOREIGN KEY (company_id, previous_version_id) REFERENCES attendance_day_result_versions (company_id, id)
);

CREATE TABLE attendance_day_results (
    id text NOT NULL,
    company_id text NOT NULL,
    employee_id text NOT NULL,
    work_date date NOT NULL,
    current_version_id text NOT NULL,
    created_at timestamptz NOT NULL,
    updated_at timestamptz NOT NULL,
    CONSTRAINT attendance_day_results_pkey PRIMARY KEY (id),
    CONSTRAINT attendance_day_results_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT attendance_day_results_employee_date_unique UNIQUE (company_id, employee_id, work_date),
    CONSTRAINT attendance_day_results_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id),
    CONSTRAINT attendance_day_results_employee_fkey FOREIGN KEY (company_id, employee_id) REFERENCES employees (company_id, id),
    CONSTRAINT attendance_day_results_version_fkey FOREIGN KEY (company_id, current_version_id) REFERENCES attendance_day_result_versions (company_id, id)
);

CREATE TABLE attendance_corrections (
    id text NOT NULL,
    company_id text NOT NULL,
    employee_id text NOT NULL,
    work_date date NOT NULL,
    field_key text NOT NULL,
    correction_state text NOT NULL,
    reason text NOT NULL,
    requested_by_user_id text NOT NULL,
    decided_by_user_id text NULL,
    result_version_id text NOT NULL,
    applied_version_id text NULL,
    requested_at timestamptz NOT NULL,
    decided_at timestamptz NULL,
    created_at timestamptz NOT NULL,
    updated_at timestamptz NOT NULL,
    CONSTRAINT attendance_corrections_pkey PRIMARY KEY (id),
    CONSTRAINT attendance_corrections_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT attendance_corrections_field_chk CHECK (field_key = 'status_code'),
    CONSTRAINT attendance_corrections_state_chk CHECK (correction_state IN ('submitted', 'rejected', 'applied')),
    CONSTRAINT attendance_corrections_reason_chk CHECK (btrim(reason) <> ''),
    CONSTRAINT attendance_corrections_company_id_id_unique UNIQUE (company_id, id),
    CONSTRAINT attendance_corrections_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id),
    CONSTRAINT attendance_corrections_employee_fkey FOREIGN KEY (company_id, employee_id) REFERENCES employees (company_id, id),
    CONSTRAINT attendance_corrections_result_fkey FOREIGN KEY (company_id, result_version_id) REFERENCES attendance_day_result_versions (company_id, id),
    CONSTRAINT attendance_corrections_applied_fkey FOREIGN KEY (company_id, applied_version_id) REFERENCES attendance_day_result_versions (company_id, id),
    CONSTRAINT attendance_corrections_requested_by_fkey FOREIGN KEY (requested_by_user_id) REFERENCES users (id),
    CONSTRAINT attendance_corrections_decided_by_fkey FOREIGN KEY (decided_by_user_id) REFERENCES users (id)
);

ALTER TABLE attendance_day_result_versions
    ADD CONSTRAINT attendance_day_result_versions_correction_fkey
    FOREIGN KEY (company_id, correction_id) REFERENCES attendance_corrections (company_id, id);

CREATE TABLE attendance_correction_values (
    id text NOT NULL,
    company_id text NOT NULL,
    attendance_correction_id text NOT NULL,
    value_stage text NOT NULL,
    field_key text NOT NULL,
    field_value text NULL,
    created_at timestamptz NOT NULL,
    CONSTRAINT attendance_correction_values_pkey PRIMARY KEY (id),
    CONSTRAINT attendance_correction_values_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT attendance_correction_values_stage_chk CHECK (value_stage IN ('original', 'requested', 'approved', 'final')),
    CONSTRAINT attendance_correction_values_field_chk CHECK (field_key = 'status_code'),
    CONSTRAINT attendance_correction_values_stage_unique UNIQUE (attendance_correction_id, value_stage, field_key),
    CONSTRAINT attendance_correction_values_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id),
    CONSTRAINT attendance_correction_values_correction_fkey FOREIGN KEY (company_id, attendance_correction_id) REFERENCES attendance_corrections (company_id, id)
);

CREATE FUNCTION reject_attendance_day_result_version_mutation() RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    RAISE EXCEPTION 'Attendance day result versions are immutable.';
END;
$$;

CREATE TRIGGER attendance_day_result_versions_no_update
    BEFORE UPDATE ON attendance_day_result_versions
    FOR EACH ROW
    EXECUTE FUNCTION reject_attendance_day_result_version_mutation();

CREATE TRIGGER attendance_day_result_versions_no_delete
    BEFORE DELETE ON attendance_day_result_versions
    FOR EACH ROW
    EXECUTE FUNCTION reject_attendance_day_result_version_mutation();

CREATE FUNCTION reject_attendance_correction_value_mutation() RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    RAISE EXCEPTION 'Attendance correction values are immutable.';
END;
$$;

CREATE TRIGGER attendance_correction_values_no_update
    BEFORE UPDATE ON attendance_correction_values
    FOR EACH ROW
    EXECUTE FUNCTION reject_attendance_correction_value_mutation();

CREATE TRIGGER attendance_correction_values_no_delete
    BEFORE DELETE ON attendance_correction_values
    FOR EACH ROW
    EXECUTE FUNCTION reject_attendance_correction_value_mutation();
SQL);
    }

    public function down(): void
    {
        throw new RuntimeException('Attendance day processing tables are retained. Rollback is not authorized.');
    }
};

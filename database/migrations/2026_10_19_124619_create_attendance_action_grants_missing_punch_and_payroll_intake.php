<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE attendance_payroll_action_grants (
    company_id text NOT NULL,
    user_id text NOT NULL,
    action_key text NOT NULL,
    authorized_at timestamptz NOT NULL,
    CONSTRAINT attendance_payroll_action_grants_pkey PRIMARY KEY (company_id, user_id, action_key),
    CONSTRAINT attendance_payroll_action_grants_action_chk CHECK (action_key IN (
        'finalize_attendance_period',
        'reopen_attendance_period',
        'handoff_attendance_period',
        'review_missing_punch',
        'consume_attendance_handoff'
    )),
    CONSTRAINT attendance_payroll_action_grants_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id),
    CONSTRAINT attendance_payroll_action_grants_user_id_fkey FOREIGN KEY (user_id) REFERENCES users (id)
);

ALTER TABLE attendance_policies
    ADD COLUMN missing_punch_requests boolean NOT NULL DEFAULT false;

CREATE TABLE attendance_missing_punch_requests (
    id text NOT NULL,
    company_id text NOT NULL,
    employee_id text NOT NULL,
    work_date date NOT NULL,
    punch_side text NOT NULL,
    requested_event_at timestamptz NOT NULL,
    reason text NOT NULL,
    request_state text NOT NULL,
    requested_by_user_id text NOT NULL,
    requested_at timestamptz NOT NULL,
    decided_by_user_id text NULL,
    decided_at timestamptz NULL,
    result_version_id text NOT NULL,
    applied_version_id text NULL,
    created_at timestamptz NOT NULL,
    updated_at timestamptz NOT NULL,
    CONSTRAINT attendance_missing_punch_requests_pkey PRIMARY KEY (id),
    CONSTRAINT attendance_missing_punch_requests_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT attendance_missing_punch_requests_side_chk CHECK (punch_side IN ('in', 'out')),
    CONSTRAINT attendance_missing_punch_requests_state_chk CHECK (request_state IN ('submitted', 'rejected', 'applied')),
    CONSTRAINT attendance_missing_punch_requests_reason_chk CHECK (btrim(reason) <> ''),
    CONSTRAINT attendance_missing_punch_requests_decided_chk CHECK ((request_state = 'submitted') = (decided_at IS NULL)),
    CONSTRAINT attendance_missing_punch_requests_company_id_id_unique UNIQUE (company_id, id),
    CONSTRAINT attendance_missing_punch_requests_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id),
    CONSTRAINT attendance_missing_punch_requests_employee_fkey FOREIGN KEY (company_id, employee_id) REFERENCES employees (company_id, id),
    CONSTRAINT attendance_missing_punch_requests_result_fkey FOREIGN KEY (company_id, result_version_id) REFERENCES attendance_day_result_versions (company_id, id),
    CONSTRAINT attendance_missing_punch_requests_applied_fkey FOREIGN KEY (company_id, applied_version_id) REFERENCES attendance_day_result_versions (company_id, id),
    CONSTRAINT attendance_missing_punch_requests_requested_by_fkey FOREIGN KEY (requested_by_user_id) REFERENCES users (id),
    CONSTRAINT attendance_missing_punch_requests_decided_by_fkey FOREIGN KEY (decided_by_user_id) REFERENCES users (id)
);

CREATE UNIQUE INDEX attendance_missing_punch_requests_one_open
    ON attendance_missing_punch_requests (company_id, employee_id, work_date, punch_side)
    WHERE request_state = 'submitted';

CREATE FUNCTION guard_attendance_missing_punch_request() RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    IF TG_OP = 'DELETE' THEN
        RAISE EXCEPTION 'Missing-punch requests are retained.';
    END IF;
    IF OLD.request_state = 'applied'
        AND OLD.applied_version_id IS NULL
        AND NEW.applied_version_id IS NOT NULL
        AND NEW.request_state = OLD.request_state
        AND NEW.decided_by_user_id IS NOT DISTINCT FROM OLD.decided_by_user_id
        AND NEW.decided_at IS NOT DISTINCT FROM OLD.decided_at
        AND NEW.requested_event_at = OLD.requested_event_at
        AND NEW.result_version_id = OLD.result_version_id THEN
        RETURN NEW;
    END IF;
    IF OLD.request_state <> 'submitted'
        OR NEW.company_id <> OLD.company_id
        OR NEW.employee_id <> OLD.employee_id
        OR NEW.work_date <> OLD.work_date
        OR NEW.punch_side <> OLD.punch_side
        OR NEW.requested_event_at <> OLD.requested_event_at
        OR NEW.reason <> OLD.reason
        OR NEW.requested_by_user_id <> OLD.requested_by_user_id
        OR NEW.requested_at <> OLD.requested_at
        OR NEW.result_version_id <> OLD.result_version_id THEN
        RAISE EXCEPTION 'A missing-punch request can only move from submitted to a decision.';
    END IF;
    RETURN NEW;
END;
$$;

CREATE TRIGGER attendance_missing_punch_requests_guard_update
    BEFORE UPDATE ON attendance_missing_punch_requests
    FOR EACH ROW
    EXECUTE FUNCTION guard_attendance_missing_punch_request();

CREATE TRIGGER attendance_missing_punch_requests_guard_delete
    BEFORE DELETE ON attendance_missing_punch_requests
    FOR EACH ROW
    EXECUTE FUNCTION guard_attendance_missing_punch_request();

ALTER TABLE attendance_day_result_versions
    ADD COLUMN override_in_at timestamptz NULL,
    ADD COLUMN override_out_at timestamptz NULL,
    ADD COLUMN override_in_request_id text NULL,
    ADD COLUMN override_out_request_id text NULL,
    ADD CONSTRAINT attendance_day_result_versions_override_in_fkey
        FOREIGN KEY (company_id, override_in_request_id) REFERENCES attendance_missing_punch_requests (company_id, id),
    ADD CONSTRAINT attendance_day_result_versions_override_out_fkey
        FOREIGN KEY (company_id, override_out_request_id) REFERENCES attendance_missing_punch_requests (company_id, id),
    ADD CONSTRAINT attendance_day_result_versions_override_in_chk
        CHECK ((override_in_at IS NULL) = (override_in_request_id IS NULL)),
    ADD CONSTRAINT attendance_day_result_versions_override_out_chk
        CHECK ((override_out_at IS NULL) = (override_out_request_id IS NULL));

ALTER TABLE attendance_day_result_versions DROP CONSTRAINT attendance_day_result_versions_generation_chk;
ALTER TABLE attendance_day_result_versions ADD CONSTRAINT attendance_day_result_versions_generation_chk
    CHECK (generation_kind IN ('calculated', 'correction', 'missing_punch'));

CREATE TABLE payroll_periods (
    id text NOT NULL,
    company_id text NOT NULL,
    period_start date NOT NULL,
    period_end date NOT NULL,
    label text NOT NULL,
    created_at timestamptz NOT NULL,
    updated_at timestamptz NOT NULL,
    CONSTRAINT payroll_periods_pkey PRIMARY KEY (id),
    CONSTRAINT payroll_periods_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT payroll_periods_range_chk CHECK (period_end >= period_start),
    CONSTRAINT payroll_periods_label_chk CHECK (btrim(label) <> ''),
    CONSTRAINT payroll_periods_company_start_unique UNIQUE (company_id, period_start),
    CONSTRAINT payroll_periods_company_id_id_unique UNIQUE (company_id, id),
    CONSTRAINT payroll_periods_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id)
);

CREATE TABLE payroll_runs (
    id text NOT NULL,
    company_id text NOT NULL,
    payroll_period_id text NOT NULL,
    run_sequence integer NOT NULL,
    run_state text NOT NULL,
    attendance_usage text NOT NULL,
    attendance_payroll_handoff_id text NULL,
    attendance_taken_at timestamptz NULL,
    created_by_user_id text NOT NULL,
    calculated_by_user_id text NULL,
    calculated_at timestamptz NULL,
    previous_run_id text NULL,
    created_at timestamptz NOT NULL,
    updated_at timestamptz NOT NULL,
    CONSTRAINT payroll_runs_pkey PRIMARY KEY (id),
    CONSTRAINT payroll_runs_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT payroll_runs_state_chk CHECK (run_state IN ('in_progress', 'draft', 'finalized', 'superseded')),
    CONSTRAINT payroll_runs_usage_chk CHECK (attendance_usage IN ('uses_declared_attendance', 'declares_no_attendance')),
    CONSTRAINT payroll_runs_handoff_usage_chk CHECK (attendance_payroll_handoff_id IS NULL OR attendance_usage = 'uses_declared_attendance'),
    CONSTRAINT payroll_runs_taken_chk CHECK ((attendance_payroll_handoff_id IS NULL) = (attendance_taken_at IS NULL)),
    CONSTRAINT payroll_runs_sequence_chk CHECK (run_sequence >= 1),
    CONSTRAINT payroll_runs_period_sequence_unique UNIQUE (payroll_period_id, run_sequence),
    CONSTRAINT payroll_runs_company_id_id_unique UNIQUE (company_id, id),
    CONSTRAINT payroll_runs_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id),
    CONSTRAINT payroll_runs_period_fkey FOREIGN KEY (company_id, payroll_period_id) REFERENCES payroll_periods (company_id, id),
    CONSTRAINT payroll_runs_handoff_fkey FOREIGN KEY (company_id, attendance_payroll_handoff_id) REFERENCES attendance_payroll_handoffs (company_id, id),
    CONSTRAINT payroll_runs_previous_fkey FOREIGN KEY (company_id, previous_run_id) REFERENCES payroll_runs (company_id, id),
    CONSTRAINT payroll_runs_created_by_fkey FOREIGN KEY (created_by_user_id) REFERENCES users (id),
    CONSTRAINT payroll_runs_calculated_by_fkey FOREIGN KEY (calculated_by_user_id) REFERENCES users (id)
);

CREATE UNIQUE INDEX payroll_runs_one_in_progress
    ON payroll_runs (company_id, payroll_period_id)
    WHERE run_state = 'in_progress';

CREATE TABLE payroll_run_declared_inputs (
    id text NOT NULL,
    company_id text NOT NULL,
    payroll_run_id text NOT NULL,
    input_key text NOT NULL,
    created_at timestamptz NOT NULL,
    CONSTRAINT payroll_run_declared_inputs_pkey PRIMARY KEY (id),
    CONSTRAINT payroll_run_declared_inputs_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT payroll_run_declared_inputs_key_chk CHECK (btrim(input_key) <> ''),
    CONSTRAINT payroll_run_declared_inputs_run_key_unique UNIQUE (payroll_run_id, input_key),
    CONSTRAINT payroll_run_declared_inputs_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id),
    CONSTRAINT payroll_run_declared_inputs_run_fkey FOREIGN KEY (company_id, payroll_run_id) REFERENCES payroll_runs (company_id, id)
);

CREATE TABLE payroll_run_attendance_inputs (
    id text NOT NULL,
    company_id text NOT NULL,
    payroll_run_id text NOT NULL,
    employee_id text NOT NULL,
    input_key text NOT NULL,
    copied_value bigint NOT NULL,
    attendance_payroll_handoff_id text NOT NULL,
    created_at timestamptz NOT NULL,
    CONSTRAINT payroll_run_attendance_inputs_pkey PRIMARY KEY (id),
    CONSTRAINT payroll_run_attendance_inputs_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT payroll_run_attendance_inputs_value_chk CHECK (copied_value >= 0),
    CONSTRAINT payroll_run_attendance_inputs_unique UNIQUE (payroll_run_id, employee_id, input_key),
    CONSTRAINT payroll_run_attendance_inputs_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id),
    CONSTRAINT payroll_run_attendance_inputs_run_fkey FOREIGN KEY (company_id, payroll_run_id) REFERENCES payroll_runs (company_id, id),
    CONSTRAINT payroll_run_attendance_inputs_employee_fkey FOREIGN KEY (company_id, employee_id) REFERENCES employees (company_id, id),
    CONSTRAINT payroll_run_attendance_inputs_handoff_fkey FOREIGN KEY (company_id, attendance_payroll_handoff_id) REFERENCES attendance_payroll_handoffs (company_id, id)
);

CREATE FUNCTION reject_payroll_intake_mutation() RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    RAISE EXCEPTION 'Declared and copied payroll inputs are immutable.';
END;
$$;

CREATE TRIGGER payroll_run_declared_inputs_no_update
    BEFORE UPDATE OR DELETE ON payroll_run_declared_inputs
    FOR EACH ROW
    EXECUTE FUNCTION reject_payroll_intake_mutation();

CREATE TRIGGER payroll_run_attendance_inputs_no_update
    BEFORE UPDATE OR DELETE ON payroll_run_attendance_inputs
    FOR EACH ROW
    EXECUTE FUNCTION reject_payroll_intake_mutation();
SQL);
    }

    public function down(): void
    {
        throw new RuntimeException('Attendance action grants, missing-punch requests, and payroll intake rows are retained. Rollback is not authorized.');
    }
};

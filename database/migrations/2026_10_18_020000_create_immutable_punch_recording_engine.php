<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE punch_identities (
    id text NOT NULL,
    company_id text NOT NULL,
    employee_id text NOT NULL,
    identity_value text NOT NULL,
    effective_from timestamptz NOT NULL,
    effective_to timestamptz NULL,
    created_at timestamptz NOT NULL,
    CONSTRAINT punch_identities_pkey PRIMARY KEY (id),
    CONSTRAINT punch_identities_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT punch_identities_value_chk CHECK (btrim(identity_value) <> ''),
    CONSTRAINT punch_identities_range_chk CHECK (effective_to IS NULL OR effective_to > effective_from),
    CONSTRAINT punch_identities_company_id_id_unique UNIQUE (company_id, id),
    CONSTRAINT punch_identities_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id),
    CONSTRAINT punch_identities_employee_fkey FOREIGN KEY (company_id, employee_id) REFERENCES employees (company_id, id)
);

CREATE INDEX punch_identities_company_value_idx
    ON punch_identities (company_id, identity_value, effective_from);

CREATE TABLE punch_identity_overlaps (
    id text NOT NULL,
    company_id text NOT NULL,
    identity_value text NOT NULL,
    effective_from timestamptz NOT NULL,
    effective_to timestamptz NOT NULL,
    reason text NOT NULL,
    created_at timestamptz NOT NULL,
    CONSTRAINT punch_identity_overlaps_pkey PRIMARY KEY (id),
    CONSTRAINT punch_identity_overlaps_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT punch_identity_overlaps_value_chk CHECK (btrim(identity_value) <> ''),
    CONSTRAINT punch_identity_overlaps_reason_chk CHECK (btrim(reason) <> ''),
    CONSTRAINT punch_identity_overlaps_range_chk CHECK (effective_to > effective_from),
    CONSTRAINT punch_identity_overlaps_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id)
);

CREATE INDEX punch_identity_overlaps_company_value_idx
    ON punch_identity_overlaps (company_id, identity_value, effective_from);

CREATE TABLE punch_events (
    id text NOT NULL,
    company_id text NOT NULL,
    punch_identity_id text NOT NULL,
    source_name text NOT NULL,
    event_at timestamptz NOT NULL,
    ingested_at timestamptz NOT NULL,
    source_event_id text NULL,
    acting_user_id text NOT NULL,
    withdrawal_status text NOT NULL,
    created_at timestamptz NOT NULL,
    CONSTRAINT punch_events_pkey PRIMARY KEY (id),
    CONSTRAINT punch_events_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT punch_events_source_name_chk CHECK (btrim(source_name) <> ''),
    CONSTRAINT punch_events_source_event_id_chk CHECK (source_event_id IS NULL OR btrim(source_event_id) <> ''),
    CONSTRAINT punch_events_withdrawal_status_chk CHECK (withdrawal_status = 'accepted'),
    CONSTRAINT punch_events_company_id_id_unique UNIQUE (company_id, id),
    CONSTRAINT punch_events_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id),
    CONSTRAINT punch_events_identity_fkey FOREIGN KEY (company_id, punch_identity_id) REFERENCES punch_identities (company_id, id),
    CONSTRAINT punch_events_actor_fkey FOREIGN KEY (acting_user_id) REFERENCES users (id)
);

CREATE INDEX punch_events_company_identity_event_idx
    ON punch_events (company_id, punch_identity_id, event_at);

CREATE FUNCTION reject_punch_event_mutation() RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    RAISE EXCEPTION 'Punch events are immutable.';
END;
$$;

CREATE TRIGGER punch_events_no_update
    BEFORE UPDATE ON punch_events
    FOR EACH ROW
    EXECUTE FUNCTION reject_punch_event_mutation();

CREATE TRIGGER punch_events_no_delete
    BEFORE DELETE ON punch_events
    FOR EACH ROW
    EXECUTE FUNCTION reject_punch_event_mutation();

CREATE FUNCTION reject_punch_identity_overlap_mutation() RETURNS trigger
LANGUAGE plpgsql
AS $$
BEGIN
    RAISE EXCEPTION 'Punch identity overlaps are immutable.';
END;
$$;

CREATE TRIGGER punch_identity_overlaps_no_update
    BEFORE UPDATE ON punch_identity_overlaps
    FOR EACH ROW
    EXECUTE FUNCTION reject_punch_identity_overlap_mutation();

CREATE TRIGGER punch_identity_overlaps_no_delete
    BEFORE DELETE ON punch_identity_overlaps
    FOR EACH ROW
    EXECUTE FUNCTION reject_punch_identity_overlap_mutation();
SQL);
    }

    public function down(): void
    {
        throw new RuntimeException('Punch recording tables are retained. Rollback is not authorized.');
    }
};

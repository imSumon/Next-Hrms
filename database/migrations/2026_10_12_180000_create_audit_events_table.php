<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE audit_events (
    id text NOT NULL,
    company_id text NOT NULL,
    subject_kind text NOT NULL,
    subject_id text NOT NULL,
    action_kind text NOT NULL,
    acting_user_id text NULL,
    acted_at timestamptz NOT NULL,
    previous_value text NULL,
    new_value text NULL,
    reason text NULL,
    source_system text NULL,
    source_key text NULL,
    created_at timestamptz NOT NULL,
    CONSTRAINT audit_events_pkey PRIMARY KEY (id),
    CONSTRAINT audit_events_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT audit_events_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id),
    CONSTRAINT audit_events_acting_user_id_fkey FOREIGN KEY (acting_user_id) REFERENCES users (id)
);

CREATE INDEX audit_events_company_subject_history_idx
    ON audit_events (company_id, subject_kind, subject_id, acted_at);
SQL);
    }

    public function down(): void
    {
        throw new \RuntimeException('Audit events are retained. Rollback is not authorized.');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE leave_position_writers (
    company_id text NOT NULL,
    user_id text NOT NULL,
    authorized_at timestamptz NOT NULL,
    CONSTRAINT leave_position_writers_pkey PRIMARY KEY (company_id, user_id),
    CONSTRAINT leave_position_writers_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id),
    CONSTRAINT leave_position_writers_user_id_fkey FOREIGN KEY (user_id) REFERENCES users (id)
);

CREATE TABLE leave_types (
    id text NOT NULL,
    company_id text NOT NULL,
    name text NOT NULL,
    status text NOT NULL,
    created_at timestamptz NOT NULL,
    updated_at timestamptz NOT NULL,
    CONSTRAINT leave_types_pkey PRIMARY KEY (id),
    CONSTRAINT leave_types_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT leave_types_status_chk CHECK (status IN ('Active', 'withdrawn')),
    CONSTRAINT leave_types_company_id_id_unique UNIQUE (company_id, id),
    CONSTRAINT leave_types_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id)
);

CREATE TABLE leave_positions (
    id text NOT NULL,
    company_id text NOT NULL,
    employee_id text NOT NULL,
    leave_type_id text NOT NULL,
    proposed_amount numeric NOT NULL,
    period_label text NULL,
    period_start date NULL,
    period_end date NULL,
    source_evidence text NOT NULL,
    stated_basis text NOT NULL,
    status text NOT NULL,
    confirmation_recorded_at timestamptz NULL,
    confirmation_actor_id text NULL,
    confirmation_decision text NULL,
    variance numeric NULL,
    reconciliation_result text NULL,
    created_at timestamptz NOT NULL,
    updated_at timestamptz NOT NULL,
    CONSTRAINT leave_positions_pkey PRIMARY KEY (id),
    CONSTRAINT leave_positions_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT leave_positions_status_chk CHECK (status IN ('proposed', 'excepted')),
    CONSTRAINT leave_positions_confirmation_unused_chk CHECK (
        confirmation_recorded_at IS NULL
        AND confirmation_actor_id IS NULL
        AND confirmation_decision IS NULL
    ),
    CONSTRAINT leave_positions_company_id_id_unique UNIQUE (company_id, id),
    CONSTRAINT leave_positions_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id),
    CONSTRAINT leave_positions_employee_fkey FOREIGN KEY (company_id, employee_id) REFERENCES employees (company_id, id),
    CONSTRAINT leave_positions_leave_type_fkey FOREIGN KEY (company_id, leave_type_id) REFERENCES leave_types (company_id, id)
);

CREATE TABLE leave_position_movements (
    id text NOT NULL,
    company_id text NOT NULL,
    leave_position_id text NOT NULL,
    amount numeric NOT NULL,
    direction text NOT NULL,
    related_reference text NULL,
    reverses_movement_id text NULL,
    acting_user_id text NOT NULL,
    recorded_at timestamptz NOT NULL,
    created_at timestamptz NOT NULL,
    updated_at timestamptz NOT NULL,
    CONSTRAINT leave_position_movements_pkey PRIMARY KEY (id),
    CONSTRAINT leave_position_movements_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT leave_position_movements_direction_chk CHECK (btrim(direction) <> ''),
    CONSTRAINT leave_position_movements_company_id_id_unique UNIQUE (company_id, id),
    CONSTRAINT leave_position_movements_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id),
    CONSTRAINT leave_position_movements_position_fkey FOREIGN KEY (company_id, leave_position_id) REFERENCES leave_positions (company_id, id),
    CONSTRAINT leave_position_movements_reversal_fkey FOREIGN KEY (company_id, reverses_movement_id) REFERENCES leave_position_movements (company_id, id),
    CONSTRAINT leave_position_movements_actor_fkey FOREIGN KEY (acting_user_id) REFERENCES users (id)
);

CREATE UNIQUE INDEX leave_positions_identity_unique
    ON leave_positions (
        company_id,
        employee_id,
        leave_type_id,
        COALESCE(period_label, ''),
        COALESCE(period_start, DATE '0001-01-01'),
        COALESCE(period_end, DATE '0001-01-01')
    );
SQL);
    }

    public function down(): void
    {
        throw new RuntimeException('Leave-position recording tables are retained. Rollback is not authorized.');
    }
};

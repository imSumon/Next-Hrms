<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE employment_episodes (
    id text NOT NULL,
    company_id text NOT NULL,
    employee_id text NOT NULL,
    joining_date date NOT NULL,
    last_working_date date NULL,
    episode_status text NOT NULL,
    source_system text NULL,
    source_key text NULL,
    created_at timestamptz NOT NULL,
    updated_at timestamptz NOT NULL,
    CONSTRAINT employment_episodes_pkey PRIMARY KEY (id),
    CONSTRAINT employment_episodes_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT employment_episodes_status_chk CHECK (episode_status IN ('Open', 'inactive', 'separated')),
    CONSTRAINT employment_episodes_open_last_working_date_chk CHECK (episode_status <> 'Open' OR last_working_date IS NULL),
    CONSTRAINT employment_episodes_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id),
    CONSTRAINT employment_episodes_company_employee_fkey FOREIGN KEY (company_id, employee_id) REFERENCES employees (company_id, id)
);

CREATE UNIQUE INDEX employment_episodes_one_open_per_employee
    ON employment_episodes (employee_id)
    WHERE episode_status = 'Open';
SQL);
    }

    public function down(): void
    {
        throw new \RuntimeException('Employee tables are retained. Rollback is not authorized.');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE employees (
    id text NOT NULL,
    company_id text NOT NULL,
    employee_code text NOT NULL,
    display_name text NOT NULL,
    source_system text NULL,
    source_key text NULL,
    created_at timestamptz NOT NULL,
    updated_at timestamptz NOT NULL,
    CONSTRAINT employees_pkey PRIMARY KEY (id),
    CONSTRAINT employees_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT employees_company_id_id_unique UNIQUE (company_id, id),
    CONSTRAINT employees_company_employee_code_unique UNIQUE (company_id, employee_code),
    CONSTRAINT employees_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id)
);
SQL);
    }

    public function down(): void
    {
        throw new \RuntimeException('Employee tables are retained. Rollback is not authorized.');
    }
};

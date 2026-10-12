<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE branches (
    id text NOT NULL,
    company_id text NOT NULL,
    code text NULL,
    name text NOT NULL,
    status text NOT NULL,
    source_system text NULL,
    source_key text NULL,
    created_at timestamptz NOT NULL,
    updated_at timestamptz NOT NULL,
    CONSTRAINT branches_pkey PRIMARY KEY (id),
    CONSTRAINT branches_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT branches_status_chk CHECK (status IN ('Active', 'withdrawn')),
    CONSTRAINT branches_company_id_id_unique UNIQUE (company_id, id),
    CONSTRAINT branches_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id)
);

CREATE UNIQUE INDEX branches_company_code_unique
    ON branches (company_id, code)
    WHERE code IS NOT NULL;
SQL);
    }

    public function down(): void
    {
        throw new \RuntimeException('Foundation tables are retained. Rollback is not authorized.');
    }
};

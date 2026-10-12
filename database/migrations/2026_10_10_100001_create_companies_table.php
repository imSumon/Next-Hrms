<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE companies (
    id text NOT NULL,
    company_code text NULL,
    company_name text NOT NULL,
    status text NOT NULL,
    source_system text NULL,
    source_key text NULL,
    created_at timestamptz NOT NULL,
    updated_at timestamptz NOT NULL,
    CONSTRAINT companies_pkey PRIMARY KEY (id),
    CONSTRAINT companies_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT companies_status_chk CHECK (status IN ('Active', 'withdrawn'))
);

CREATE UNIQUE INDEX companies_company_code_unique
    ON companies (company_code)
    WHERE company_code IS NOT NULL;
SQL);
    }

    public function down(): void
    {
        throw new \RuntimeException('Foundation tables are retained. Rollback is not authorized.');
    }
};

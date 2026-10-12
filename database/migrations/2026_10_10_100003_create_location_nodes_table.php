<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE location_nodes (
    id text NOT NULL,
    company_id text NOT NULL,
    branch_id text NOT NULL,
    code text NULL,
    name text NOT NULL,
    status text NOT NULL,
    source_system text NULL,
    source_key text NULL,
    created_at timestamptz NOT NULL,
    updated_at timestamptz NOT NULL,
    CONSTRAINT location_nodes_pkey PRIMARY KEY (id),
    CONSTRAINT location_nodes_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT location_nodes_status_chk CHECK (status IN ('Active', 'withdrawn')),
    CONSTRAINT location_nodes_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id),
    CONSTRAINT location_nodes_company_branch_fkey FOREIGN KEY (company_id, branch_id) REFERENCES branches (company_id, id)
);

CREATE UNIQUE INDEX location_nodes_company_code_unique
    ON location_nodes (company_id, code)
    WHERE code IS NOT NULL;
SQL);
    }

    public function down(): void
    {
        throw new \RuntimeException('Foundation tables are retained. Rollback is not authorized.');
    }
};

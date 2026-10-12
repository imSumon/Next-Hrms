<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE organization_nodes (
    id text NOT NULL,
    company_id text NOT NULL,
    parent_id text NULL,
    source_system text NULL,
    source_key text NULL,
    created_at timestamptz NOT NULL,
    updated_at timestamptz NOT NULL,
    CONSTRAINT organization_nodes_pkey PRIMARY KEY (id),
    CONSTRAINT organization_nodes_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT organization_nodes_company_id_id_unique UNIQUE (company_id, id),
    CONSTRAINT organization_nodes_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id),
    CONSTRAINT organization_nodes_parent_fkey FOREIGN KEY (company_id, parent_id) REFERENCES organization_nodes (company_id, id)
);
SQL);
    }

    public function down(): void
    {
        throw new \RuntimeException('Foundation tables are retained. Rollback is not authorized.');
    }
};

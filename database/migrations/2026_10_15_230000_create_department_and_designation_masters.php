<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
ALTER TABLE company_memberships
    ADD COLUMN can_manage_organization_masters boolean NOT NULL DEFAULT false;

CREATE TABLE departments (
    id text NOT NULL,
    company_id text NOT NULL,
    code text NULL,
    name text NOT NULL,
    status text NOT NULL,
    source_system text NULL,
    source_key text NULL,
    created_at timestamptz NOT NULL,
    updated_at timestamptz NOT NULL,
    CONSTRAINT departments_pkey PRIMARY KEY (id),
    CONSTRAINT departments_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT departments_status_chk CHECK (status IN ('Active', 'withdrawn')),
    CONSTRAINT departments_company_id_id_unique UNIQUE (company_id, id),
    CONSTRAINT departments_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id)
);

CREATE UNIQUE INDEX departments_company_code_unique
    ON departments (company_id, code)
    WHERE code IS NOT NULL;

CREATE TABLE designations (
    id text NOT NULL,
    company_id text NOT NULL,
    code text NULL,
    name text NOT NULL,
    status text NOT NULL,
    source_system text NULL,
    source_key text NULL,
    created_at timestamptz NOT NULL,
    updated_at timestamptz NOT NULL,
    CONSTRAINT designations_pkey PRIMARY KEY (id),
    CONSTRAINT designations_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT designations_status_chk CHECK (status IN ('Active', 'withdrawn')),
    CONSTRAINT designations_company_id_id_unique UNIQUE (company_id, id),
    CONSTRAINT designations_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id)
);

CREATE UNIQUE INDEX designations_company_code_unique
    ON designations (company_id, code)
    WHERE code IS NOT NULL;
SQL);
    }

    public function down(): void
    {
        throw new \RuntimeException('Organization master tables are retained. Rollback is not authorized.');
    }
};

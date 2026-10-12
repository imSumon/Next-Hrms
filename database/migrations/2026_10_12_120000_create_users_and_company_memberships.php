<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE users (
    id text NOT NULL,
    name text NOT NULL,
    email text NOT NULL,
    password text NOT NULL,
    remember_token text NULL,
    created_at timestamptz NOT NULL,
    updated_at timestamptz NOT NULL,
    CONSTRAINT users_pkey PRIMARY KEY (id),
    CONSTRAINT users_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT users_email_unique UNIQUE (email)
);

CREATE TABLE company_memberships (
    id text NOT NULL,
    user_id text NOT NULL,
    company_id text NOT NULL,
    status text NOT NULL,
    can_manage_employees boolean NOT NULL DEFAULT false,
    created_at timestamptz NOT NULL,
    updated_at timestamptz NOT NULL,
    CONSTRAINT company_memberships_pkey PRIMARY KEY (id),
    CONSTRAINT company_memberships_id_ulid_chk CHECK (id ~ '^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{26}$'),
    CONSTRAINT company_memberships_status_chk CHECK (status IN ('active', 'inactive')),
    CONSTRAINT company_memberships_user_company_unique UNIQUE (user_id, company_id),
    CONSTRAINT company_memberships_user_id_fkey FOREIGN KEY (user_id) REFERENCES users (id),
    CONSTRAINT company_memberships_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id)
);

CREATE INDEX company_memberships_user_id_idx ON company_memberships (user_id);
SQL);
    }

    public function down(): void
    {
        throw new \RuntimeException('Authentication tables are retained. Rollback is not authorized.');
    }
};

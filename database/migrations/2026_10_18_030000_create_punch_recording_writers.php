<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
CREATE TABLE punch_recording_writers (
    company_id text NOT NULL,
    user_id text NOT NULL,
    authorized_at timestamptz NOT NULL,
    CONSTRAINT punch_recording_writers_pkey PRIMARY KEY (company_id, user_id),
    CONSTRAINT punch_recording_writers_company_id_fkey FOREIGN KEY (company_id) REFERENCES companies (id),
    CONSTRAINT punch_recording_writers_user_id_fkey FOREIGN KEY (user_id) REFERENCES users (id)
);
SQL);
    }

    public function down(): void
    {
        throw new RuntimeException('Punch-recording writer rows are retained. Rollback is not authorized.');
    }
};

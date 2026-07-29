<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE admin.audit_logs ALTER COLUMN target_id TYPE VARCHAR(255) USING target_id::text');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE admin.audit_logs ALTER COLUMN target_id TYPE UUID USING target_id::uuid');
    }
};

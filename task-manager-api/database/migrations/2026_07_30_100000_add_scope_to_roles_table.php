<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE admin.roles ADD COLUMN scope VARCHAR(20) NOT NULL DEFAULT 'organization'");
        DB::statement("ALTER TABLE admin.roles ADD CONSTRAINT roles_scope_check CHECK (scope IN ('global', 'organization'))");

        DB::table('admin.roles')->where('slug', 'owner')->update(['scope' => 'global']);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE admin.roles DROP CONSTRAINT roles_scope_check');
        DB::statement('ALTER TABLE admin.roles DROP COLUMN scope');
    }
};

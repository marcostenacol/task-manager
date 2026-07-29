<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE admin.roles ADD COLUMN level INTEGER NOT NULL DEFAULT 100');
        DB::statement('ALTER TABLE admin.roles ADD COLUMN deleted_at TIMESTAMP NULL');
        DB::statement('ALTER TABLE admin.users ADD COLUMN deleted_at TIMESTAMP NULL');

        DB::table('admin.roles')->where('slug', 'admin')->update(['level' => 0]);
        DB::table('admin.roles')->where('slug', 'user')->update(['level' => 10]);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE admin.roles DROP COLUMN level');
        DB::statement('ALTER TABLE admin.roles DROP COLUMN deleted_at');
        DB::statement('ALTER TABLE admin.users DROP COLUMN deleted_at');
    }
};

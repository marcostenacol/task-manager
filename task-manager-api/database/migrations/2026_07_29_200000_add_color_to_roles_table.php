<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE admin.roles ADD COLUMN color VARCHAR(7) NOT NULL DEFAULT '#64748b'");

        DB::table('admin.roles')->where('slug', 'owner')->update(['color' => '#c084fc']);
        DB::table('admin.roles')->where('slug', 'admin')->update(['color' => '#f97316']);
        DB::table('admin.roles')->where('slug', 'user')->update(['color' => '#38bdf8']);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE admin.roles DROP COLUMN color');
    }
};

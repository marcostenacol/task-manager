<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public.tasks', function (Blueprint $table) {
            $table->string('visibility', 20)->default('personal')->after('organization_id');
        });

        DB::statement("ALTER TABLE public.tasks ADD CONSTRAINT tasks_visibility_check CHECK (visibility IN ('personal', 'organization'))");
    }

    public function down(): void
    {
        Schema::table('public.tasks', function (Blueprint $table) {
            DB::statement('ALTER TABLE public.tasks DROP CONSTRAINT tasks_visibility_check');
            $table->dropColumn('visibility');
        });
    }
};

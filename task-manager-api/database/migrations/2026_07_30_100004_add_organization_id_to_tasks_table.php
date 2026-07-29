<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public.tasks', function (Blueprint $table) {
            $table->uuid('organization_id')->nullable();

            $table->foreign('organization_id', 'fk_tasks_organization_id')
                ->references('id')->on('admin.organizations');
        });
    }

    public function down(): void
    {
        Schema::table('public.tasks', function (Blueprint $table) {
            $table->dropForeign('fk_tasks_organization_id');
            $table->dropColumn('organization_id');
        });
    }
};

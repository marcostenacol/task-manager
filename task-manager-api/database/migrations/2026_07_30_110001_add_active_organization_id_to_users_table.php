<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin.users', function (Blueprint $table) {
            $table->uuid('active_organization_id')->nullable();

            $table->foreign('active_organization_id', 'fk_users_active_organization_id')
                ->references('id')->on('admin.organizations');
        });

        // Backfill: quem tem exatamente 1 membership já entra com ela ativa.
        DB::statement('
            UPDATE admin.users U
            SET active_organization_id = UO.organization_id
            FROM admin.user_organizations UO
            WHERE UO.user_id = U.id
            AND U.active_organization_id IS NULL
            AND (SELECT COUNT(*) FROM admin.user_organizations WHERE user_id = U.id) = 1
        ');
    }

    public function down(): void
    {
        Schema::table('admin.users', function (Blueprint $table) {
            $table->dropForeign('fk_users_active_organization_id');
            $table->dropColumn('active_organization_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin.roles', function (Blueprint $table) {
            $table->uuid('organization_id')->nullable()->after('scope');
            $table->foreign('organization_id', 'roles_organization_id_fk')
                ->references('id')->on('admin.organizations')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('admin.roles', function (Blueprint $table) {
            $table->dropForeign('roles_organization_id_fk');
            $table->dropColumn('organization_id');
        });
    }
};

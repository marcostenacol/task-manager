<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin.audit_logs', function (Blueprint $table) {
            $table->uuid('organization_id')->nullable()->after('actor_id');
            $table->foreign('organization_id', 'audit_logs_organization_id_fk')
                ->references('id')->on('admin.organizations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('admin.audit_logs', function (Blueprint $table) {
            $table->dropForeign('audit_logs_organization_id_fk');
            $table->dropColumn('organization_id');
        });
    }
};

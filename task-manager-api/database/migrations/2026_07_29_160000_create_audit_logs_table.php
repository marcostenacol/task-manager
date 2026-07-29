<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin.audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('actor_id');
            $table->string('action');
            $table->string('target_type');
            $table->uuid('target_id');
            $table->jsonb('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('actor_id', 'audit_logs_actor_id_fk')
                ->references('id')
                ->on('admin.users');
        });
    }

    public function down(): void
    {
        Schema::table('admin.audit_logs', function (Blueprint $table) {
            $table->dropForeign('audit_logs_actor_id_fk');
        });
        Schema::dropIfExists('admin.audit_logs');
    }
};

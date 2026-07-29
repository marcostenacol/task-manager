<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin.users', function (Blueprint $table) {
            $table->uuid('global_role_id')->nullable();

            $table->foreign('global_role_id', 'fk_users_global_role_id')
                ->references('id')->on('admin.roles');
        });
    }

    public function down(): void
    {
        Schema::table('admin.users', function (Blueprint $table) {
            $table->dropForeign('fk_users_global_role_id');
            $table->dropColumn('global_role_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin.user_organizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('organization_id');
            $table->uuid('role_id');
            $table->timestamps();

            $table->unique(['user_id', 'organization_id'], 'user_organizations_user_id_organization_id_unique');

            $table->foreign('user_id', 'fk_user_organizations_user_id')
                ->references('id')->on('admin.users');
            $table->foreign('organization_id', 'fk_user_organizations_organization_id')
                ->references('id')->on('admin.organizations');
            $table->foreign('role_id', 'fk_user_organizations_role_id')
                ->references('id')->on('admin.roles');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin.user_organizations');
    }
};

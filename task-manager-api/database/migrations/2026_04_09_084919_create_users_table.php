<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admin.users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->uuid('role_id');
            $table->uuid('last_status_id')->nullable();
            $table->timestamps();

            $table->foreign('role_id', 'fk_users_role_id')->references('id')->on('admin.roles');
            $table->foreign('last_status_id', 'fk_users_last_status_id')->references('id')->on('admin.user_statuses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin.users');
    }
};

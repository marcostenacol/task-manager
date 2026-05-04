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
        Schema::create('admin.user_has_statuses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->uuid('status_id');
            $table->string('reason')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id', 'fk_user_has_statuses_user_id')->references('id')->on('admin.users');
            $table->foreign('status_id', 'fk_user_has_statuses_status_id')->references('id')->on('admin.user_statuses');
            $table->foreign('created_by', 'fk_user_has_statuses_created_by')->references('id')->on('admin.users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin.user_has_statuses');
    }
};

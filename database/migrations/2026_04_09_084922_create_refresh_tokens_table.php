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
        Schema::create('admin.refresh_tokens', function (Blueprint $table) {
            $table->id();
            $table->text('token')->unique();
            $table->uuid('user_id');
            $table->uuid('personal_access_token_id');
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id', 'fk_refresh_tokens_user_id')->references('id')->on('admin.users');
            $table->foreign('personal_access_token_id', 'fk_refresh_tokens_personal_access_token_id')->references('id')->on('admin.personal_access_tokens');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin.refresh_tokens');
    }
};

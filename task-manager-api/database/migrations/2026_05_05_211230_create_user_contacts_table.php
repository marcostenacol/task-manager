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
        Schema::create('social.user_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('type'); // phone, whatsapp, linkedin, github, etc
            $table->string('value');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->foreign('user_id', 'fk_user_contacts_user_id')->references('id')->on('admin.users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social.user_contacts');
    }
};

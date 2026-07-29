<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin.organizations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->uuid('parent_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('admin.organizations', function (Blueprint $table) {
            $table->foreign('parent_id', 'fk_organizations_parent_id')
                ->references('id')->on('admin.organizations');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin.organizations');
    }
};

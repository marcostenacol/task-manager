<?php

use Database\Seeders\OrganizationSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Backfilla organization_id das tasks existentes para a organization
     * root. A coluna continua nullable de propósito — CreateTaskService
     * ainda não resolve organization ativa (isso é trabalho da Fase 2,
     * "Auth com organization ativa"); tornar a coluna NOT NULL agora
     * quebraria a criação de tarefa em produção.
     */
    public function up(): void
    {
        Artisan::call('db:seed', [
            '--class' => OrganizationSeeder::class,
            '--force' => true,
        ]);
    }
};

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('admin.settings')->insertOrIgnore([
            [
                'name' => 'token_expiration_minutes',
                'value' => '1440', // 24 horas
                'description' => 'Tempo de expiração do Access Token em minutos.',
            ],
            [
                'name' => 'refresh_token.expiration_hours',
                'value' => '720', // 30 dias
                'description' => 'Tempo de expiração do Refresh Token em horas.',
            ],
            [
                'name' => 'organization_onboarding_rate_limit_per_hour',
                'value' => '3',
                'description' => 'Quantas organizations um usuário pode fundar por hora.',
            ],
            [
                'name' => 'organization_max_active_per_founder',
                'value' => '5',
                'description' => 'Máximo de organizations que um usuário pode administrar simultaneamente como fundador.',
            ],
        ]);
    }
}

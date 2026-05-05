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
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Packages\Admin\UserStatuses\Models\UserStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UserStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['name' => 'Active', 'slug' => 'active'],
            ['name' => 'Inactive', 'slug' => 'inactive'],
            ['name' => 'Banned', 'slug' => 'banned'],
        ];

        foreach ($statuses as $status) {
            UserStatus::firstOrCreate(
                ['slug' => $status['slug']],
                ['id' => (string) Str::uuid(), 'name' => $status['name']]
            );
        }
    }
}

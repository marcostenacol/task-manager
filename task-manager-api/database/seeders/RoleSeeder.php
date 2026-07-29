<?php

namespace Database\Seeders;

use App\Packages\Admin\Roles\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            ['name' => 'Admin', 'slug' => 'admin'],
            ['name' => 'User', 'slug' => 'user'],
        ];

        foreach ($roles as $role) {
            Role::withTrashed()->firstOrCreate(
                ['slug' => $role['slug']],
                ['id' => (string) Str::uuid(), 'name' => $role['name']]
            );
        }
    }
}

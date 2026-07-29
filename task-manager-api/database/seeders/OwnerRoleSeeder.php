<?php

namespace Database\Seeders;

use App\Packages\Admin\Roles\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OwnerRoleSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate(
            ['slug' => 'owner'],
            ['id' => (string) Str::uuid(), 'name' => 'Owner']
        );

        $role->update(['name' => 'Owner', 'level' => -10]);
    }
}

<?php

namespace Database\Seeders;

use App\Packages\Admin\Roles\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrgAdminRoleSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::withTrashed()->firstOrCreate(
            ['slug' => 'org-admin'],
            ['id' => (string) Str::uuid(), 'name' => 'Org Admin']
        );

        $role->update([
            'name' => 'Org Admin',
            'level' => 0,
            'scope' => 'organization',
            'color' => '#f97316',
        ]);
    }
}

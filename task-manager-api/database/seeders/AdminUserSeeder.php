<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Packages\Admin\Users\Models\User;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\UserStatuses\Models\UserStatus;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::where('slug', 'admin')->first();
        $activeStatus = UserStatus::where('slug', 'active')->first();

        if ($adminRole && $activeStatus) {
            User::firstOrCreate(
                ['email' => 'admin@admin.com'],
                [
                    'id' => (string) Str::uuid(),
                    'name' => 'Admin User',
                    'password' => 'password',
                    'role_id' => $adminRole->id,
                    'last_status_id' => $activeStatus->id,
                ]
            );
        }
    }
}

<?php

use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Database\Seeders\OwnerRoleSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    public function up(): void
    {
        Artisan::call('db:seed', [
            '--class' => OwnerRoleSeeder::class,
            '--force' => true,
        ]);

        Artisan::call('db:seed', [
            '--class' => PermissionSeeder::class,
            '--force' => true,
        ]);

        $ownerRole = Role::where('slug', 'owner')->first();

        if ($ownerRole) {
            User::where('email', 'admin@admin.com')->update(['role_id' => $ownerRole->id]);
        }
    }
};

<?php

use Database\Seeders\OrgAdminRoleSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('admin.roles')->where('slug', 'admin')->update(['scope' => 'global']);

        DB::table('admin.users')
            ->whereIn('role_id', DB::table('admin.roles')->where('slug', 'admin')->pluck('id'))
            ->whereNull('global_role_id')
            ->update(['global_role_id' => DB::raw('role_id')]);

        // Usuários que acabaram de virar global_role_id não devem mais ter
        // membership em user_organizations (papel global bypassa o pivot).
        DB::statement('
            DELETE FROM admin.user_organizations
            WHERE user_id IN (SELECT id FROM admin.users WHERE global_role_id IS NOT NULL)
        ');

        Artisan::call('db:seed', [
            '--class' => OrgAdminRoleSeeder::class,
            '--force' => true,
        ]);

        Artisan::call('db:seed', [
            '--class' => PermissionSeeder::class,
            '--force' => true,
        ]);
    }
};

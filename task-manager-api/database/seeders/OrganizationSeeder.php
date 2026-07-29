<?php

namespace Database\Seeders;

use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        $rootId = $this->createRootOrganization();

        $this->assignGlobalRoleToOwners();
        $this->createMembershipsForOrganizationScopedUsers($rootId);
        $this->backfillTaskOrganization($rootId);
    }

    private function createRootOrganization(): string
    {
        $existing = DB::table('admin.organizations')->where('slug', 'root')->first();

        if ($existing) {
            return $existing->id;
        }

        $id = (string) Str::uuid();

        DB::table('admin.organizations')->insert([
            'id' => $id,
            'name' => 'Root',
            'slug' => 'root',
            'parent_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $id;
    }

    private function assignGlobalRoleToOwners(): void
    {
        $globalRoleIds = Role::where('scope', 'global')->pluck('id');

        User::whereIn('role_id', $globalRoleIds)
            ->whereNull('global_role_id')
            ->update(['global_role_id' => DB::raw('role_id')]);
    }

    private function createMembershipsForOrganizationScopedUsers(string $rootId): void
    {
        $organizationScopedUserIds = User::whereNotNull('role_id')
            ->whereNull('global_role_id')
            ->get(['id', 'role_id']);

        foreach ($organizationScopedUserIds as $user) {
            DB::table('admin.user_organizations')->insertOrIgnore([
                'id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'organization_id' => $rootId,
                'role_id' => $user->role_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function backfillTaskOrganization(string $rootId): void
    {
        DB::table('public.tasks')->whereNull('organization_id')->update([
            'organization_id' => $rootId,
        ]);
    }
}

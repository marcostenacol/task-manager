<?php

namespace Tests\Feature\Admin;

use App\Packages\Admin\Organizations\Models\Organization;
use App\Packages\Admin\Organizations\Models\UserOrganization;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;

use function Pest\Laravel\postJson;
use function Pest\Laravel\withToken;

uses(DatabaseTransactions::class);

test('usuário com global_role_id usa as permissões da role global, sem organization ativa', function () {
    $ownerRole = Role::where('slug', 'owner')->first();

    $user = User::factory()->create([
        'role_id' => $ownerRole->id,
        'global_role_id' => $ownerRole->id,
        'password' => 'password123',
    ]);

    $token = postJson(route('v1.auth.login'), [
        'email' => $user->email,
        'password' => 'password123',
    ])->json('data.access_token.token');

    $response = withToken($token)->getJson('/api/v1/admin/users');

    $response->assertStatus(200)->assertJsonPath('success', true);
});

test('usuário sem global_role_id usa a role da membership na organization ativa', function () {
    $userRole = Role::where('slug', 'user')->first();
    $orgAdminRole = Role::where('slug', 'org-admin')->first();

    $organization = Organization::create([
        'id' => (string) Str::uuid(),
        'name' => 'Empresa Teste',
        'slug' => 'empresa-teste-'.Str::random(6),
    ]);

    $user = User::factory()->create([
        'role_id' => $userRole->id,
        'active_organization_id' => $organization->id,
        'password' => 'password123',
    ]);

    UserOrganization::create([
        'id' => (string) Str::uuid(),
        'user_id' => $user->id,
        'organization_id' => $organization->id,
        'role_id' => $orgAdminRole->id,
    ]);

    $token = postJson(route('v1.auth.login'), [
        'email' => $user->email,
        'password' => 'password123',
    ])->json('data.access_token.token');

    $response = withToken($token)->getJson('/api/v1/admin/users');

    $response->assertStatus(200)->assertJsonPath('success', true);
});

test('usuário legado (só role_id, sem global_role_id nem membership) continua funcionando', function () {
    $adminRole = Role::where('slug', 'admin')->first();

    $user = User::factory()->create([
        'role_id' => $adminRole->id,
        'password' => 'password123',
    ]);

    $token = postJson(route('v1.auth.login'), [
        'email' => $user->email,
        'password' => 'password123',
    ])->json('data.access_token.token');

    $response = withToken($token)->getJson('/api/v1/admin/users');

    $response->assertStatus(200)->assertJsonPath('success', true);
});

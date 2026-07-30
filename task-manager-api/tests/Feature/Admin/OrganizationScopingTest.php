<?php

namespace Tests\Feature\Admin;

use App\Packages\Admin\Organizations\Models\Organization;
use App\Packages\Admin\Organizations\Models\UserOrganization;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;

use function Pest\Laravel\artisan;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withToken;

uses(DatabaseTransactions::class);

beforeEach(function () {
    artisan('optimize:clear');

    $this->userRole = Role::where('slug', 'user')->first();
    $this->orgAdminRole = Role::where('slug', 'org-admin')->first();
    $this->adminRole = Role::where('slug', 'admin')->first();
    $this->ownerRole = Role::where('slug', 'owner')->first();

    $this->orgA = Organization::create(['id' => (string) Str::uuid(), 'name' => 'Org A', 'slug' => 'org-a-'.Str::random(6)]);
    $this->orgB = Organization::create(['id' => (string) Str::uuid(), 'name' => 'Org B', 'slug' => 'org-b-'.Str::random(6)]);

    $this->orgAAdmin = User::factory()->create([
        'role_id' => $this->orgAdminRole->id,
        'active_organization_id' => $this->orgA->id,
        'password' => 'password123',
    ]);
    UserOrganization::create(['user_id' => $this->orgAAdmin->id, 'organization_id' => $this->orgA->id, 'role_id' => $this->orgAdminRole->id]);

    $this->orgAAdminToken = postJson(route('v1.auth.login'), [
        'email' => $this->orgAAdmin->email,
        'password' => 'password123',
    ])->json('data.access_token.token');
});

test('org admin só vê usuários da própria organization na listagem', function () {
    $userInOrgA = User::factory()->create(['role_id' => $this->userRole->id, 'active_organization_id' => $this->orgA->id]);
    UserOrganization::create(['user_id' => $userInOrgA->id, 'organization_id' => $this->orgA->id, 'role_id' => $this->userRole->id]);

    $userInOrgB = User::factory()->create(['role_id' => $this->userRole->id, 'active_organization_id' => $this->orgB->id]);
    UserOrganization::create(['user_id' => $userInOrgB->id, 'organization_id' => $this->orgB->id, 'role_id' => $this->userRole->id]);

    $response = withToken($this->orgAAdminToken)->getJson('/api/v1/admin/users?limit=100');

    $ids = collect($response->json('data.data'))->pluck('id');

    expect($ids)->toContain($userInOrgA->id);
    expect($ids)->not->toContain($userInOrgB->id);
});

test('admin global continua vendo usuários de todas as organizations', function () {
    $admin = User::factory()->create(['role_id' => $this->adminRole->id, 'global_role_id' => $this->adminRole->id, 'password' => 'password123']);
    $adminToken = postJson(route('v1.auth.login'), [
        'email' => $admin->email,
        'password' => 'password123',
    ])->json('data.access_token.token');

    $userInOrgB = User::factory()->create(['role_id' => $this->userRole->id, 'active_organization_id' => $this->orgB->id]);
    UserOrganization::create(['user_id' => $userInOrgB->id, 'organization_id' => $this->orgB->id, 'role_id' => $this->userRole->id]);

    $response = withToken($adminToken)->getJson('/api/v1/admin/users?limit=100');

    $ids = collect($response->json('data.data'))->pluck('id');
    expect($ids)->toContain($userInOrgB->id);
});

test('org admin cria usuário novo já dentro da própria organization', function () {
    $response = withToken($this->orgAAdminToken)->postJson('/api/v1/admin/users', [
        'name' => 'Novo Membro',
        'email' => 'novo-membro-'.Str::random(6).'@example.com',
        'password' => 'password123',
        'role_id' => $this->userRole->id,
    ]);

    $response->assertStatus(201)->assertJsonPath('success', true);

    $newUserId = $response->json('data.id');

    $this->assertDatabaseHas('admin.users', [
        'id' => $newUserId,
        'active_organization_id' => $this->orgA->id,
    ]);

    $this->assertDatabaseHas('admin.user_organizations', [
        'user_id' => $newUserId,
        'organization_id' => $this->orgA->id,
        'role_id' => $this->userRole->id,
    ]);
});

test('não deve permitir que org admin crie um usuário com role global', function () {
    $response = withToken($this->orgAAdminToken)->postJson('/api/v1/admin/users', [
        'name' => 'Tentativa de escalada',
        'email' => 'escalada-'.Str::random(6).'@example.com',
        'password' => 'password123',
        'role_id' => $this->adminRole->id,
    ]);

    $response->assertStatus(400)->assertJsonPath('success', false);
});

test('admin global consegue criar org admin em qualquer organization (scopes diferentes não colidem por level)', function () {
    $admin = User::factory()->create(['role_id' => $this->adminRole->id, 'global_role_id' => $this->adminRole->id, 'password' => 'password123']);
    $adminToken = postJson(route('v1.auth.login'), [
        'email' => $admin->email,
        'password' => 'password123',
    ])->json('data.access_token.token');

    $response = withToken($adminToken)->postJson('/api/v1/admin/users', [
        'name' => 'Org Admin criado pelo Admin global',
        'email' => 'org-admin-by-admin-'.Str::random(6).'@example.com',
        'password' => 'password123',
        'role_id' => $this->orgAdminRole->id,
        'organization_id' => $this->orgB->id,
    ]);

    $response->assertStatus(201)->assertJsonPath('success', true);

    $this->assertDatabaseHas('admin.user_organizations', [
        'user_id' => $response->json('data.id'),
        'organization_id' => $this->orgB->id,
        'role_id' => $this->orgAdminRole->id,
    ]);
});

test('não deve permitir que org admin crie outro org admin (mesmo nível)', function () {
    $response = withToken($this->orgAAdminToken)->postJson('/api/v1/admin/users', [
        'name' => 'Outro Org Admin',
        'email' => 'outro-org-admin-'.Str::random(6).'@example.com',
        'password' => 'password123',
        'role_id' => $this->orgAdminRole->id,
    ]);

    $response->assertStatus(400)->assertJsonPath('success', false);
});

test('listagem de usuários inclui a organization de cada um', function () {
    $response = withToken($this->orgAAdminToken)->getJson('/api/v1/admin/users?limit=100');

    $organizationEntry = collect($response->json('data.data'))->firstWhere('id', $this->orgAAdmin->id);

    expect($organizationEntry['organization']['id'])->toBe($this->orgA->id);
});

test('listagem de usuários mostra a role efetiva da membership, não a role legada da coluna users.role_id', function () {
    $adminGlobalRole = Role::where('slug', 'admin')->first();
    $adminGlobal = User::factory()->create([
        'role_id' => $adminGlobalRole->id,
        'global_role_id' => $adminGlobalRole->id,
        'password' => 'password123',
    ]);
    $adminGlobalToken = postJson(route('v1.auth.login'), [
        'email' => $adminGlobal->email,
        'password' => 'password123',
    ])->json('data.access_token.token');

    $member = User::factory()->create([
        'role_id' => $this->userRole->id,
        'active_organization_id' => $this->orgA->id,
    ]);
    UserOrganization::create(['user_id' => $member->id, 'organization_id' => $this->orgA->id, 'role_id' => $this->userRole->id]);

    $promotedRole = Role::create([
        'id' => (string) Str::uuid(),
        'name' => 'Supervisor Efetivo',
        'slug' => 'supervisor-efetivo-'.Str::random(6),
        'level' => $this->orgAdminRole->level + 1,
        'scope' => 'organization',
        'organization_id' => $this->orgA->id,
    ]);

    // Promove o membro via membership (não altera admin.users.role_id, só admin.user_organizations.role_id)
    withToken($this->orgAAdminToken)->putJson("/api/v1/organizations/members/{$member->id}/role", [
        'role_id' => $promotedRole->id,
    ])->assertStatus(200);

    $response = withToken($adminGlobalToken)->getJson('/api/v1/admin/users?limit=200');

    $entry = collect($response->json('data.data'))->firstWhere('id', $member->id);

    expect($entry['role']['id'])->toBe($promotedRole->id);
    $this->assertDatabaseHas('admin.users', ['id' => $member->id, 'role_id' => $this->userRole->id]);
});

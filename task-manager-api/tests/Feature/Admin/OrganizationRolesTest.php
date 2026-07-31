<?php

namespace Tests\Feature\Admin;

use App\Packages\Admin\Organizations\Models\Organization;
use App\Packages\Admin\Organizations\Models\UserOrganization;
use App\Packages\Admin\Permissions\Models\Permission;
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

    $this->orgAdminRole = Role::where('slug', 'org-admin')->first();

    $this->orgA = Organization::create(['id' => (string) Str::uuid(), 'name' => 'Org A Roles', 'slug' => 'org-a-roles-'.Str::random(6)]);
    $this->orgB = Organization::create(['id' => (string) Str::uuid(), 'name' => 'Org B Roles', 'slug' => 'org-b-roles-'.Str::random(6)]);

    $this->orgAAdmin = User::factory()->create([
        'role_id' => $this->orgAdminRole->id,
        'active_organization_id' => $this->orgA->id,
        'password' => 'password123',
    ]);
    UserOrganization::create(['user_id' => $this->orgAAdmin->id, 'organization_id' => $this->orgA->id, 'role_id' => $this->orgAdminRole->id]);

    $this->orgBAdmin = User::factory()->create([
        'role_id' => $this->orgAdminRole->id,
        'active_organization_id' => $this->orgB->id,
        'password' => 'password123',
    ]);
    UserOrganization::create(['user_id' => $this->orgBAdmin->id, 'organization_id' => $this->orgB->id, 'role_id' => $this->orgAdminRole->id]);

    $this->orgAAdminToken = postJson(route('v1.auth.login'), [
        'email' => $this->orgAAdmin->email,
        'password' => 'password123',
    ])->json('data.access_token.token');

    $this->orgBAdminToken = postJson(route('v1.auth.login'), [
        'email' => $this->orgBAdmin->email,
        'password' => 'password123',
    ])->json('data.access_token.token');
});

test('org admin cria uma role customizada escopada à própria organization', function () {
    $response = withToken($this->orgAAdminToken)->postJson('/api/v1/admin/roles', [
        'name' => 'Supervisor',
    ]);

    $response->assertStatus(201)->assertJsonPath('success', true);

    $this->assertDatabaseHas('admin.roles', [
        'name' => 'Supervisor',
        'organization_id' => $this->orgA->id,
        'scope' => 'organization',
    ]);
});

test('duas organizations diferentes podem ter roles com o mesmo nome', function () {
    $responseA = withToken($this->orgAAdminToken)->postJson('/api/v1/admin/roles', ['name' => 'Moderador']);
    $responseB = withToken($this->orgBAdminToken)->postJson('/api/v1/admin/roles', ['name' => 'Moderador']);

    $responseA->assertStatus(201);
    $responseB->assertStatus(201);
});

test('não deve permitir duas roles com o mesmo nome dentro da mesma organization', function () {
    withToken($this->orgAAdminToken)->postJson('/api/v1/admin/roles', ['name' => 'Repetida']);

    $response = withToken($this->orgAAdminToken)->postJson('/api/v1/admin/roles', ['name' => 'Repetida']);

    $response->assertStatus(422);
});

test('org admin não consegue criar uma role com scope global', function () {
    $response = withToken($this->orgAAdminToken)->postJson('/api/v1/admin/roles', [
        'name' => 'Tentativa Global',
        'scope' => 'global',
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('admin.roles', [
        'name' => 'Tentativa Global',
        'scope' => 'organization',
        'organization_id' => $this->orgA->id,
    ]);
});

test('org admin não vê roles customizadas de outra organization na listagem', function () {
    withToken($this->orgAAdminToken)->postJson('/api/v1/admin/roles', ['name' => 'Role Org A']);
    withToken($this->orgBAdminToken)->postJson('/api/v1/admin/roles', ['name' => 'Role Org B']);

    $response = withToken($this->orgAAdminToken)->getJson('/api/v1/admin/roles');

    $response->assertStatus(200);

    $names = collect($response->json('data'))->pluck('name');
    expect($names)->toContain('Role Org A');
    expect($names)->not->toContain('Role Org B');
});

test('org admin não vê as roles globais Admin e Owner na listagem', function () {
    $response = withToken($this->orgAAdminToken)->getJson('/api/v1/admin/roles');

    $response->assertStatus(200);

    $slugs = collect($response->json('data'))->pluck('slug');
    expect($slugs)->not->toContain('admin');
    expect($slugs)->not->toContain('owner');
    expect($slugs)->toContain('user');
    expect($slugs)->toContain('org-admin');
});

test('role customizada nasce com as permissões padrão da role user', function () {
    $response = withToken($this->orgAAdminToken)->postJson('/api/v1/admin/roles', ['name' => 'Role Com Default']);

    $roleId = $response->json('data.id');
    $userRole = Role::where('slug', 'user')->first();

    expect($response->json('data.permissions_count'))->toBe($userRole->permissions()->count());
    expect($response->json('data.permissions_count'))->toBeGreaterThan(0);
});

test('org admin não consegue editar role customizada de outra organization', function () {
    $roleOrgB = withToken($this->orgBAdminToken)->postJson('/api/v1/admin/roles', ['name' => 'Role Só Da B']);
    $roleId = $roleOrgB->json('data.id');

    $response = withToken($this->orgAAdminToken)->patchJson("/api/v1/admin/roles/{$roleId}/name", ['name' => 'Hijacked']);

    $response->assertStatus(400)->assertJsonPath('success', false);
});

test('org admin não consegue editar uma role global (ex: user)', function () {
    $userRole = Role::where('slug', 'user')->first();

    $response = withToken($this->orgAAdminToken)->patchJson("/api/v1/admin/roles/{$userRole->id}/name", ['name' => 'Hijacked']);

    $response->assertStatus(400)->assertJsonPath('success', false);
});

test('org admin não consegue atribuir uma permissão de escopo global à sua role customizada', function () {
    $roleResponse = withToken($this->orgAAdminToken)->postJson('/api/v1/admin/roles', ['name' => 'Role Com Permissão']);
    $roleId = $roleResponse->json('data.id');

    $globalPermission = Permission::where('name', 'admin.settings.manage')->first();

    $response = withToken($this->orgAAdminToken)->putJson("/api/v1/admin/roles/{$roleId}/permissions", [
        'permission_ids' => [$globalPermission->id],
    ]);

    $response->assertStatus(400)->assertJsonPath('success', false);
});

test('org admin consegue atribuir uma permissão normal à sua role customizada', function () {
    $roleResponse = withToken($this->orgAAdminToken)->postJson('/api/v1/admin/roles', ['name' => 'Role Com Permissão Normal']);
    $roleId = $roleResponse->json('data.id');

    $taskPermission = Permission::where('name', 'task.tasks.list')->first();

    $response = withToken($this->orgAAdminToken)->putJson("/api/v1/admin/roles/{$roleId}/permissions", [
        'permission_ids' => [$taskPermission->id],
    ]);

    $response->assertStatus(200)->assertJsonPath('success', true);

    $this->assertDatabaseHas('admin.role_has_permissions', [
        'role_id' => $roleId,
        'permission_id' => $taskPermission->id,
    ]);
});

test('ator global com scope=global não vê roles customizadas de organizations', function () {
    withToken($this->orgAAdminToken)->postJson('/api/v1/admin/roles', ['name' => 'Custom Org A']);

    $globalAdminRole = Role::where('slug', 'admin')->first();
    $globalAdmin = User::factory()->create(['role_id' => $globalAdminRole->id, 'global_role_id' => $globalAdminRole->id, 'password' => 'password123']);
    $globalAdminToken = postJson(route('v1.auth.login'), [
        'email' => $globalAdmin->email,
        'password' => 'password123',
    ])->json('data.access_token.token');

    $response = withToken($globalAdminToken)->getJson('/api/v1/admin/roles?scope=global');

    $response->assertStatus(200);
    collect($response->json('data'))->each(function ($role) {
        expect($role['scope'])->toBe('global');
    });
});

test('ator global filtra roles por organization_id e só vê as customizadas daquela organization', function () {
    withToken($this->orgAAdminToken)->postJson('/api/v1/admin/roles', ['name' => 'Custom Org A']);
    withToken($this->orgBAdminToken)->postJson('/api/v1/admin/roles', ['name' => 'Custom Org B']);

    $globalAdminRole = Role::where('slug', 'admin')->first();
    $globalAdmin = User::factory()->create(['role_id' => $globalAdminRole->id, 'global_role_id' => $globalAdminRole->id, 'password' => 'password123']);
    $globalAdminToken = postJson(route('v1.auth.login'), [
        'email' => $globalAdmin->email,
        'password' => 'password123',
    ])->json('data.access_token.token');

    $response = withToken($globalAdminToken)->getJson("/api/v1/admin/roles?organization_id={$this->orgA->id}");

    $response->assertStatus(200);
    $names = collect($response->json('data'))->pluck('name');
    expect($names)->toContain('Custom Org A');
    expect($names)->not->toContain('Custom Org B');
});

<?php

namespace Tests\Feature\Admin;

use App\Packages\Admin\AuditLogs\Models\AuditLog;
use App\Packages\Admin\Organizations\Models\Organization;
use App\Packages\Admin\Organizations\Models\UserOrganization;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use App\Packages\Task\Priorities\Models\TaskPriority;
use App\Packages\Task\Statuses\Models\TaskStatus;
use App\Packages\Task\Tasks\Models\Task;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;

use function Pest\Laravel\artisan;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withToken;

uses(DatabaseTransactions::class);

beforeEach(function () {
    artisan('optimize:clear');

    $this->orgAdminRole = Role::where('slug', 'org-admin')->first();

    $this->parentOrg = Organization::create(['id' => (string) Str::uuid(), 'name' => 'Parent Org', 'slug' => 'parent-org-'.Str::random(6)]);
    $this->childOrg = Organization::create(['id' => (string) Str::uuid(), 'name' => 'Child Org', 'slug' => 'child-org-'.Str::random(6), 'parent_id' => $this->parentOrg->id]);
    $this->unrelatedOrg = Organization::create(['id' => (string) Str::uuid(), 'name' => 'Unrelated Org', 'slug' => 'unrelated-org-'.Str::random(6)]);

    $this->parentAdmin = User::factory()->create([
        'role_id' => $this->orgAdminRole->id,
        'active_organization_id' => $this->parentOrg->id,
        'password' => 'password123',
    ]);
    UserOrganization::create(['user_id' => $this->parentAdmin->id, 'organization_id' => $this->parentOrg->id, 'role_id' => $this->orgAdminRole->id]);

    $this->childMember = User::factory()->create([
        'role_id' => $this->orgAdminRole->id,
        'active_organization_id' => $this->childOrg->id,
        'password' => 'password123',
    ]);
    UserOrganization::create(['user_id' => $this->childMember->id, 'organization_id' => $this->childOrg->id, 'role_id' => $this->orgAdminRole->id]);

    $this->parentAdminToken = postJson(route('v1.auth.login'), [
        'email' => $this->parentAdmin->email,
        'password' => 'password123',
    ])->json('data.access_token.token');
});

test('admin da organization-pai vê tasks de organization da sub-organization', function () {
    $status = TaskStatus::where('slug', 'pending')->first();
    $priority = TaskPriority::where('slug', 'high')->first();

    Task::create([
        'user_id' => $this->childMember->id,
        'organization_id' => $this->childOrg->id,
        'visibility' => 'organization',
        'status_id' => $status->id,
        'priority_id' => $priority->id,
        'title' => 'Task da sub-organization',
    ]);

    Task::create([
        'user_id' => $this->childMember->id,
        'organization_id' => $this->unrelatedOrg->id,
        'visibility' => 'organization',
        'status_id' => $status->id,
        'priority_id' => $priority->id,
        'title' => 'Task de organization não relacionada',
    ]);

    $response = withToken($this->parentAdminToken)->getJson('/api/v1/tasks');

    $titles = collect($response->json('data.data'))->pluck('title');
    expect($titles)->toContain('Task da sub-organization');
    expect($titles)->not->toContain('Task de organization não relacionada');
});

test('admin da organization-pai vê role customizada da sub-organization na listagem', function () {
    withToken($this->parentAdminToken)->postJson('/api/v1/admin/roles', ['name' => 'Papel do Pai'])
        ->assertStatus(201);

    $childAdminToken = postJson(route('v1.auth.login'), [
        'email' => $this->childMember->email,
        'password' => 'password123',
    ])->json('data.access_token.token');

    withToken($childAdminToken)->postJson('/api/v1/admin/roles', ['name' => 'Papel do Filho'])
        ->assertStatus(201);

    $response = withToken($this->parentAdminToken)->getJson('/api/v1/admin/roles');

    $names = collect($response->json('data'))->pluck('name');
    expect($names)->toContain('Papel do Filho');
});

test('admin da organization-pai vê audit log da sub-organization', function () {
    AuditLog::create([
        'id' => (string) Str::uuid(),
        'actor_id' => $this->childMember->id,
        'organization_id' => $this->childOrg->id,
        'action' => 'user.role_change',
        'target_type' => 'User',
        'target_id' => (string) Str::uuid(),
        'metadata' => [],
    ]);

    AuditLog::create([
        'id' => (string) Str::uuid(),
        'actor_id' => $this->childMember->id,
        'organization_id' => $this->unrelatedOrg->id,
        'action' => 'user.role_change',
        'target_type' => 'User',
        'target_id' => (string) Str::uuid(),
        'metadata' => [],
    ]);

    $response = withToken($this->parentAdminToken)->getJson('/api/v1/admin/audit-logs');

    $organizationIds = collect($response->json('data.data'))->pluck('organization.id');
    expect($organizationIds)->toContain($this->childOrg->id);
    expect($organizationIds)->not->toContain($this->unrelatedOrg->id);
});

test('org admin consegue criar uma sub-organization da própria organization ativa', function () {
    $response = withToken($this->parentAdminToken)->postJson('/api/v1/organizations/sub', [
        'name' => 'Nova Sub Org',
    ]);

    $response->assertStatus(201)->assertJsonPath('success', true);
    $newSubOrgId = $response->json('data.id');

    $this->assertDatabaseHas('admin.organizations', [
        'id' => $newSubOrgId,
        'parent_id' => $this->parentOrg->id,
    ]);

    // O criador já entra como org-admin da sub-organization criada.
    $this->assertDatabaseHas('admin.user_organizations', [
        'user_id' => $this->parentAdmin->id,
        'organization_id' => $newSubOrgId,
    ]);
});

test('org admin não consegue criar sub-organization de uma organization arbitrária (ignora organization_id enviado)', function () {
    $response = withToken($this->parentAdminToken)->postJson('/api/v1/organizations/sub', [
        'name' => 'Sub Org Tentando Escolher',
        'organization_id' => $this->unrelatedOrg->id,
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('admin.organizations', [
        'id' => $response->json('data.id'),
        'parent_id' => $this->parentOrg->id,
    ]);
});

test('admin global consegue criar sub-organization de qualquer organization informando organization_id', function () {
    $globalAdminRole = Role::where('slug', 'admin')->first();
    $globalAdmin = User::factory()->create(['role_id' => $globalAdminRole->id, 'global_role_id' => $globalAdminRole->id, 'password' => 'password123']);
    $globalAdminToken = postJson(route('v1.auth.login'), [
        'email' => $globalAdmin->email,
        'password' => 'password123',
    ])->json('data.access_token.token');

    $response = withToken($globalAdminToken)->postJson('/api/v1/organizations/sub', [
        'name' => 'Sub Org Via Global',
        'organization_id' => $this->childOrg->id,
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('admin.organizations', [
        'id' => $response->json('data.id'),
        'parent_id' => $this->childOrg->id,
    ]);
});

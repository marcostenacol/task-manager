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

    $adminRole = Role::where('slug', 'admin')->first();
    $this->admin = User::factory()->create(['role_id' => $adminRole->id, 'password' => 'password123']);
    $this->adminToken = postJson(route('v1.auth.login'), [
        'email' => $this->admin->email,
        'password' => 'password123',
    ])->json('data.access_token.token');
});

test('listagem de auditoria retorna metadados de paginação', function () {
    $targetUser = User::factory()->create();

    withToken($this->adminToken)->postJson("/api/v1/admin/users/{$targetUser->id}/ban", ['reason' => 'teste']);

    $response = withToken($this->adminToken)->getJson('/api/v1/admin/audit-logs?limit=1');

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => ['current_page', 'data', 'last_page', 'per_page', 'total'],
        ]);

    expect($response->json('data.per_page'))->toBe(1);
});

test('deve filtrar logs de auditoria por action', function () {
    $targetUser = User::factory()->create();

    withToken($this->adminToken)->postJson("/api/v1/admin/users/{$targetUser->id}/ban", ['reason' => 'teste']);
    withToken($this->adminToken)->postJson("/api/v1/admin/users/{$targetUser->id}/activate");

    $response = withToken($this->adminToken)->getJson('/api/v1/admin/audit-logs?action=user.ban');

    $response->assertStatus(200);

    $actions = collect($response->json('data.data'))->pluck('action')->unique();
    expect($actions->all())->toBe(['user.ban']);
});

test('org admin só vê logs de auditoria da própria organization', function () {
    $orgAdminRole = Role::where('slug', 'org-admin')->first();

    $orgA = Organization::create(['id' => (string) Str::uuid(), 'name' => 'Org A Audit', 'slug' => 'org-a-audit-'.Str::random(6)]);
    $orgB = Organization::create(['id' => (string) Str::uuid(), 'name' => 'Org B Audit', 'slug' => 'org-b-audit-'.Str::random(6)]);

    $orgAAdmin = User::factory()->create([
        'role_id' => $orgAdminRole->id,
        'active_organization_id' => $orgA->id,
        'password' => 'password123',
    ]);
    UserOrganization::create(['user_id' => $orgAAdmin->id, 'organization_id' => $orgA->id, 'role_id' => $orgAdminRole->id]);

    $orgBAdmin = User::factory()->create([
        'role_id' => $orgAdminRole->id,
        'active_organization_id' => $orgB->id,
        'password' => 'password123',
    ]);
    UserOrganization::create(['user_id' => $orgBAdmin->id, 'organization_id' => $orgB->id, 'role_id' => $orgAdminRole->id]);

    $orgAAdminToken = postJson(route('v1.auth.login'), [
        'email' => $orgAAdmin->email,
        'password' => 'password123',
    ])->json('data.access_token.token');

    $orgBAdminToken = postJson(route('v1.auth.login'), [
        'email' => $orgBAdmin->email,
        'password' => 'password123',
    ])->json('data.access_token.token');

    withToken($orgAAdminToken)->postJson('/api/v1/organizations/members/create', [
        'name' => 'Membro Org A',
        'email' => 'membro.orga@example.com',
        'cpf' => '52998224725',
        'role_id' => Role::where('slug', 'user')->first()->id,
    ]);

    withToken($orgBAdminToken)->postJson('/api/v1/organizations/members/create', [
        'name' => 'Membro Org B',
        'email' => 'membro.orgb@example.com',
        'cpf' => '11144477735',
        'role_id' => Role::where('slug', 'user')->first()->id,
    ]);

    $response = withToken($orgAAdminToken)->getJson('/api/v1/admin/audit-logs');

    $response->assertStatus(200);

    $orgIds = collect($response->json('data.data'))->pluck('organization.id')->unique()->filter();
    expect($orgIds->all())->toBe([$orgA->id]);
});

test('admin global consegue filtrar logs de auditoria por organization_id', function () {
    $orgAdminRole = Role::where('slug', 'org-admin')->first();

    $orgA = Organization::create(['id' => (string) Str::uuid(), 'name' => 'Org A Filter', 'slug' => 'org-a-filter-'.Str::random(6)]);
    $orgB = Organization::create(['id' => (string) Str::uuid(), 'name' => 'Org B Filter', 'slug' => 'org-b-filter-'.Str::random(6)]);

    $orgAAdmin = User::factory()->create([
        'role_id' => $orgAdminRole->id,
        'active_organization_id' => $orgA->id,
        'password' => 'password123',
    ]);
    UserOrganization::create(['user_id' => $orgAAdmin->id, 'organization_id' => $orgA->id, 'role_id' => $orgAdminRole->id]);

    $orgBAdmin = User::factory()->create([
        'role_id' => $orgAdminRole->id,
        'active_organization_id' => $orgB->id,
        'password' => 'password123',
    ]);
    UserOrganization::create(['user_id' => $orgBAdmin->id, 'organization_id' => $orgB->id, 'role_id' => $orgAdminRole->id]);

    $orgAAdminToken = postJson(route('v1.auth.login'), [
        'email' => $orgAAdmin->email,
        'password' => 'password123',
    ])->json('data.access_token.token');

    $orgBAdminToken = postJson(route('v1.auth.login'), [
        'email' => $orgBAdmin->email,
        'password' => 'password123',
    ])->json('data.access_token.token');

    withToken($orgAAdminToken)->postJson('/api/v1/organizations/members/create', [
        'name' => 'Membro Filter A',
        'email' => 'membro.filter.a@example.com',
        'cpf' => '52998224725',
        'role_id' => Role::where('slug', 'user')->first()->id,
    ]);

    withToken($orgBAdminToken)->postJson('/api/v1/organizations/members/create', [
        'name' => 'Membro Filter B',
        'email' => 'membro.filter.b@example.com',
        'cpf' => '11144477735',
        'role_id' => Role::where('slug', 'user')->first()->id,
    ]);

    $response = withToken($this->adminToken)->getJson("/api/v1/admin/audit-logs?organization_id={$orgA->id}");

    $response->assertStatus(200);

    $orgIds = collect($response->json('data.data'))->pluck('organization.id')->unique()->filter();
    expect($orgIds->all())->toBe([$orgA->id]);
});

test('metadata da auditoria resolve ids para nomes legíveis', function () {
    $targetUser = User::factory()->create();
    $userRole = Role::where('slug', 'user')->first();

    withToken($this->adminToken)->patchJson("/api/v1/admin/users/{$targetUser->id}/role", [
        'role_id' => $userRole->id,
    ]);

    $response = withToken($this->adminToken)->getJson('/api/v1/admin/audit-logs?action=user.role_change');

    $entry = collect($response->json('data.data'))->firstWhere('action', 'user.role_change');

    expect($entry['metadata']['role_id'])->toBe($userRole->name);
});

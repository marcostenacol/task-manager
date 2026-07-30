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

    $this->org = Organization::create(['id' => (string) Str::uuid(), 'name' => 'Admin View Org', 'slug' => 'admin-view-org-'.Str::random(6)]);

    $this->orgAdmin = User::factory()->create([
        'role_id' => $this->orgAdminRole->id,
        'active_organization_id' => $this->org->id,
        'password' => 'password123',
    ]);
    UserOrganization::create(['user_id' => $this->orgAdmin->id, 'organization_id' => $this->org->id, 'role_id' => $this->orgAdminRole->id]);

    $this->admin = User::factory()->create(['role_id' => $this->adminRole->id, 'global_role_id' => $this->adminRole->id, 'password' => 'password123']);
    $this->adminToken = postJson(route('v1.auth.login'), [
        'email' => $this->admin->email,
        'password' => 'password123',
    ])->json('data.access_token.token');
});

test('admin global lista todas as organizations', function () {
    $response = withToken($this->adminToken)->getJson('/api/v1/admin/organizations');

    $response->assertStatus(200)->assertJsonPath('success', true);

    $slugs = collect($response->json('data'))->pluck('slug');
    expect($slugs)->toContain($this->org->slug);
});

test('org admin não consegue listar todas as organizations', function () {
    $orgAdminToken = postJson(route('v1.auth.login'), [
        'email' => $this->orgAdmin->email,
        'password' => 'password123',
    ])->json('data.access_token.token');

    $response = withToken($orgAdminToken)->getJson('/api/v1/admin/organizations');

    $response->assertStatus(403);
});

test('admin global vê os membros de uma organization específica', function () {
    $response = withToken($this->adminToken)->getJson("/api/v1/admin/organizations/{$this->org->id}/members");

    $response->assertStatus(200)->assertJsonPath('success', true);

    $emails = collect($response->json('data'))->pluck('email');
    expect($emails)->toContain($this->orgAdmin->email);
});

test('admin global adiciona membro numa organization específica informando organization_id', function () {
    $outsider = User::factory()->create(['role_id' => $this->userRole->id, 'cpf' => '11144477735']);

    $response = withToken($this->adminToken)->postJson('/api/v1/organizations/members', [
        'user_id' => $outsider->id,
        'role_id' => $this->userRole->id,
        'organization_id' => $this->org->id,
    ]);

    $response->assertStatus(201)->assertJsonPath('success', true);

    $this->assertDatabaseHas('admin.user_organizations', [
        'user_id' => $outsider->id,
        'organization_id' => $this->org->id,
        'role_id' => $this->userRole->id,
    ]);
});

test('org admin edita o nome da própria organization', function () {
    $orgAdminToken = postJson(route('v1.auth.login'), [
        'email' => $this->orgAdmin->email,
        'password' => 'password123',
    ])->json('data.access_token.token');

    $response = withToken($orgAdminToken)->putJson("/api/v1/organizations/{$this->org->id}", ['name' => 'Novo Nome']);

    $response->assertStatus(200)->assertJsonPath('data.name', 'Novo Nome');
});

test('não deve permitir que org admin edite organization de outra pessoa', function () {
    $otherOrg = Organization::create(['id' => (string) Str::uuid(), 'name' => 'Other Org', 'slug' => 'other-org-'.Str::random(6)]);

    $orgAdminToken = postJson(route('v1.auth.login'), [
        'email' => $this->orgAdmin->email,
        'password' => 'password123',
    ])->json('data.access_token.token');

    $response = withToken($orgAdminToken)->putJson("/api/v1/organizations/{$otherOrg->id}", ['name' => 'Hijacked']);

    $response->assertStatus(400)->assertJsonPath('success', false);
});

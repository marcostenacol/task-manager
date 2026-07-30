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

    $this->org = Organization::create(['id' => (string) Str::uuid(), 'name' => 'Org Members Test', 'slug' => 'org-members-test-'.Str::random(6)]);

    $this->orgAdmin = User::factory()->create([
        'role_id' => $this->orgAdminRole->id,
        'active_organization_id' => $this->org->id,
        'password' => 'password123',
    ]);
    UserOrganization::create(['user_id' => $this->orgAdmin->id, 'organization_id' => $this->org->id, 'role_id' => $this->orgAdminRole->id]);

    $this->orgAdminToken = postJson(route('v1.auth.login'), [
        'email' => $this->orgAdmin->email,
        'password' => 'password123',
    ])->json('data.access_token.token');
});

test('org admin consegue ver os membros da própria organization', function () {
    $response = withToken($this->orgAdminToken)->getJson("/api/v1/organizations/{$this->org->id}/members");

    $response->assertStatus(200)->assertJsonPath('success', true);

    $emails = collect($response->json('data'))->pluck('email');
    expect($emails)->toContain($this->orgAdmin->email);
});

test('org admin não consegue ver os membros de outra organization', function () {
    $otherOrg = Organization::create(['id' => (string) Str::uuid(), 'name' => 'Other Org Members', 'slug' => 'other-org-members-'.Str::random(6)]);

    $response = withToken($this->orgAdminToken)->getJson("/api/v1/organizations/{$otherOrg->id}/members");

    $response->assertStatus(400)->assertJsonPath('success', false);
});

test('deve permitir salvar o cpf no perfil e rejeitar cpf inválido', function () {
    $response = withToken($this->orgAdminToken)->putJson('/api/v1/social/profile', ['cpf' => '11144477735']);
    $response->assertStatus(200)->assertJsonPath('data.cpf', '11144477735');

    $response = withToken($this->orgAdminToken)->putJson('/api/v1/social/profile', ['cpf' => '12345678900']);
    $response->assertStatus(422);
});

test('lookup por cpf encontra usuário existente fora da organization', function () {
    $outsider = User::factory()->create(['role_id' => $this->userRole->id, 'cpf' => '11144477735']);

    $response = withToken($this->orgAdminToken)->getJson('/api/v1/organizations/members/lookup?cpf=11144477735');

    $response->assertStatus(200)->assertJsonPath('data.user_id', $outsider->id);
});

test('lookup por cpf inexistente retorna sucesso com data null', function () {
    $response = withToken($this->orgAdminToken)->getJson('/api/v1/organizations/members/lookup?cpf=52998224725');

    $response->assertStatus(200)->assertJsonPath('data', null);
});

test('org admin adiciona usuário existente como membro da própria organization', function () {
    $outsider = User::factory()->create(['role_id' => $this->userRole->id, 'cpf' => '11144477735']);

    $response = withToken($this->orgAdminToken)->postJson('/api/v1/organizations/members', [
        'user_id' => $outsider->id,
        'role_id' => $this->userRole->id,
    ]);

    $response->assertStatus(201)->assertJsonPath('success', true);

    $this->assertDatabaseHas('admin.user_organizations', [
        'user_id' => $outsider->id,
        'organization_id' => $this->org->id,
        'role_id' => $this->userRole->id,
    ]);
});

test('não deve permitir adicionar membro com role igual ou superior', function () {
    $outsider = User::factory()->create(['role_id' => $this->userRole->id, 'cpf' => '11144477735']);

    $response = withToken($this->orgAdminToken)->postJson('/api/v1/organizations/members', [
        'user_id' => $outsider->id,
        'role_id' => $this->orgAdminRole->id,
    ]);

    $response->assertStatus(400)->assertJsonPath('success', false);
});

test('org admin altera a role de um membro da própria organization', function () {
    $member = User::factory()->create(['role_id' => $this->userRole->id]);
    UserOrganization::create(['user_id' => $member->id, 'organization_id' => $this->org->id, 'role_id' => $this->userRole->id]);

    $customRole = Role::create([
        'id' => (string) Str::uuid(),
        'name' => 'Supervisor Membros',
        'slug' => 'supervisor-membros-'.Str::random(6),
        'level' => $this->orgAdminRole->level + 1,
        'scope' => 'organization',
        'organization_id' => $this->org->id,
    ]);

    $response = withToken($this->orgAdminToken)->putJson("/api/v1/organizations/members/{$member->id}/role", [
        'role_id' => $customRole->id,
    ]);

    $response->assertStatus(200)->assertJsonPath('success', true);

    $this->assertDatabaseHas('admin.user_organizations', [
        'user_id' => $member->id,
        'organization_id' => $this->org->id,
        'role_id' => $customRole->id,
    ]);
});

test('não deve permitir alterar a própria role via esse endpoint', function () {
    $response = withToken($this->orgAdminToken)->putJson("/api/v1/organizations/members/{$this->orgAdmin->id}/role", [
        'role_id' => $this->userRole->id,
    ]);

    $response->assertStatus(400)->assertJsonPath('success', false);
});

test('não deve permitir promover um membro para org-admin por esse endpoint (evita múltiplos titulares)', function () {
    $member = User::factory()->create(['role_id' => $this->userRole->id]);
    UserOrganization::create(['user_id' => $member->id, 'organization_id' => $this->org->id, 'role_id' => $this->userRole->id]);

    $response = withToken($this->orgAdminToken)->putJson("/api/v1/organizations/members/{$member->id}/role", [
        'role_id' => $this->orgAdminRole->id,
    ]);

    $response->assertStatus(400)->assertJsonPath('success', false);
});

test('org admin remove um membro da própria organization', function () {
    $member = User::factory()->create(['role_id' => $this->userRole->id, 'active_organization_id' => $this->org->id]);
    UserOrganization::create(['user_id' => $member->id, 'organization_id' => $this->org->id, 'role_id' => $this->userRole->id]);

    $response = withToken($this->orgAdminToken)->deleteJson("/api/v1/organizations/members/{$member->id}");

    $response->assertStatus(200)->assertJsonPath('success', true);

    $this->assertDatabaseMissing('admin.user_organizations', [
        'user_id' => $member->id,
        'organization_id' => $this->org->id,
    ]);
});

test('não deve permitir remover a si mesmo', function () {
    $response = withToken($this->orgAdminToken)->deleteJson("/api/v1/organizations/members/{$this->orgAdmin->id}");

    $response->assertStatus(400)->assertJsonPath('success', false);
});

test('org admin cria um novo usuário direto na organization com senha inicial igual ao cpf', function () {
    $response = withToken($this->orgAdminToken)->postJson('/api/v1/organizations/members/create', [
        'name' => 'Novo Membro',
        'email' => 'novo.membro@example.com',
        'cpf' => '11144477735',
        'role_id' => $this->userRole->id,
    ]);

    $response->assertStatus(201)->assertJsonPath('success', true);

    $this->assertDatabaseHas('admin.users', [
        'email' => 'novo.membro@example.com',
        'cpf' => '11144477735',
    ]);

    $newUser = User::where('email', 'novo.membro@example.com')->first();

    $this->assertDatabaseHas('admin.user_organizations', [
        'user_id' => $newUser->id,
        'organization_id' => $this->org->id,
        'role_id' => $this->userRole->id,
    ]);

    postJson(route('v1.auth.login'), [
        'email' => 'novo.membro@example.com',
        'password' => '11144477735',
    ])->assertStatus(200)->assertJsonPath('success', true);
});

test('não deve permitir criar novo membro com role igual ou superior', function () {
    $response = withToken($this->orgAdminToken)->postJson('/api/v1/organizations/members/create', [
        'name' => 'Membro Bloqueado',
        'email' => 'membro.bloqueado@example.com',
        'cpf' => '11144477735',
        'role_id' => $this->orgAdminRole->id,
    ]);

    $response->assertStatus(400)->assertJsonPath('success', false);
});

test('não deve permitir criar novo membro com role global', function () {
    $globalAdminRole = Role::where('slug', 'admin')->first();

    $response = withToken($this->orgAdminToken)->postJson('/api/v1/organizations/members/create', [
        'name' => 'Membro Global Bloqueado',
        'email' => 'membro.global@example.com',
        'cpf' => '11144477735',
        'role_id' => $globalAdminRole->id,
    ]);

    $response->assertStatus(400)->assertJsonPath('success', false);
});

test('não deve criar novo membro com cpf ou email já em uso', function () {
    User::factory()->create(['role_id' => $this->userRole->id, 'cpf' => '11144477735']);

    $response = withToken($this->orgAdminToken)->postJson('/api/v1/organizations/members/create', [
        'name' => 'Membro Duplicado',
        'email' => 'membro.duplicado@example.com',
        'cpf' => '11144477735',
        'role_id' => $this->userRole->id,
    ]);

    $response->assertStatus(422);
});

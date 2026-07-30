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

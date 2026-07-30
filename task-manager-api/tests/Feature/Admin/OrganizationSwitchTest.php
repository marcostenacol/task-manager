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

    $userRole = Role::where('slug', 'user')->first();
    $orgAdminRole = Role::where('slug', 'org-admin')->first();

    $this->orgA = Organization::create(['id' => (string) Str::uuid(), 'name' => 'Org A', 'slug' => 'org-a-'.Str::random(6)]);
    $this->orgB = Organization::create(['id' => (string) Str::uuid(), 'name' => 'Org B', 'slug' => 'org-b-'.Str::random(6)]);
    $this->orgC = Organization::create(['id' => (string) Str::uuid(), 'name' => 'Org C', 'slug' => 'org-c-'.Str::random(6)]);

    $this->user = User::factory()->create([
        'role_id' => $userRole->id,
        'active_organization_id' => $this->orgA->id,
        'password' => 'password123',
    ]);
    UserOrganization::create(['user_id' => $this->user->id, 'organization_id' => $this->orgA->id, 'role_id' => $userRole->id]);
    UserOrganization::create(['user_id' => $this->user->id, 'organization_id' => $this->orgB->id, 'role_id' => $orgAdminRole->id]);

    $this->token = postJson(route('v1.auth.login'), [
        'email' => $this->user->email,
        'password' => 'password123',
    ])->json('data.access_token.token');
});

test('lista as próprias organizations com a ativa marcada', function () {
    $response = withToken($this->token)->getJson('/api/v1/organizations/mine');

    $response->assertStatus(200)->assertJsonPath('success', true);

    $memberships = collect($response->json('data'));
    expect($memberships)->toHaveCount(2);

    $active = $memberships->firstWhere('is_active', true);
    expect($active['organization']['id'])->toBe($this->orgA->id);
});

test('troca a organization ativa para uma que o usuário é membro', function () {
    $response = withToken($this->token)->patchJson('/api/v1/organizations/active', [
        'organization_id' => $this->orgB->id,
    ]);

    $response->assertStatus(200)->assertJsonPath('success', true);

    $this->assertDatabaseHas('admin.users', [
        'id' => $this->user->id,
        'active_organization_id' => $this->orgB->id,
    ]);
});

test('a troca reflete imediatamente nas permissões (sem esperar cache expirar)', function () {
    withToken($this->token)->patchJson('/api/v1/organizations/active', ['organization_id' => $this->orgB->id])
        ->assertStatus(200);

    $response = withToken($this->token)->getJson('/api/v1/admin/users');

    $response->assertStatus(200)->assertJsonPath('success', true);
});

test('não deve permitir trocar para uma organization que não é membro', function () {
    $response = withToken($this->token)->patchJson('/api/v1/organizations/active', [
        'organization_id' => $this->orgC->id,
    ]);

    $response->assertStatus(400)->assertJsonPath('success', false);
});

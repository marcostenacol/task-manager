<?php

namespace Tests\Feature\Admin;

use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use App\Packages\Admin\UserStatuses\Models\UserStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;

use function Pest\Laravel\artisan;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withToken;

uses(DatabaseTransactions::class);

beforeEach(function () {
    artisan('optimize:clear');

    $this->adminRole = Role::where('slug', 'admin')->first();
    $this->userRole = Role::where('slug', 'user')->first();

    // Create Admin
    $this->admin = User::factory()->create([
        'role_id' => $this->adminRole->id,
        'password' => 'password123',
    ]);

    $response = postJson(route('v1.auth.login'), [
        'email' => $this->admin->email,
        'password' => 'password123',
    ]);

    $this->adminToken = $response->json('data.access_token.token');

    // Create Normal User
    $this->user = User::factory()->create([
        'role_id' => $this->userRole->id,
        'password' => 'password123',
    ]);

    $responseUser = postJson(route('v1.auth.login'), [
        'email' => $this->user->email,
        'password' => 'password123',
    ]);

    $this->userToken = $responseUser->json('data.access_token.token');
});

test('não deve permitir que usuário comum acesse listagem de admin', function () {
    $response = withToken($this->userToken)
        ->getJson('/api/v1/admin/users');

    $response->assertStatus(403);
});

test('deve permitir que admin liste usuários', function () {
    $response = withToken($this->adminToken)
        ->getJson('/api/v1/admin/users');

    $response->assertStatus(200)
        ->assertJsonPath('success', true);
});

test('deve permitir que admin banir um usuário', function () {
    $response = withToken($this->adminToken)
        ->postJson("/api/v1/admin/users/{$this->user->id}/ban", [
            'reason' => 'Comportamento inadequado',
        ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('admin.users', [
        'id' => $this->user->id,
        'last_status_id' => UserStatus::where('slug', 'banned')->first()->id,
    ]);
});

test('com cache ligado, a listagem reflete o ban imediatamente (regressão de invalidação de cache)', function () {
    config(['api.cache.use_cache' => true]);

    // Filtra por e-mail pra não depender de paginação/dados pré-existentes.
    $query = '?search='.urlencode($this->user->email);

    // Aquece o cache da listagem antes do ban.
    withToken($this->adminToken)->getJson('/api/v1/admin/users'.$query);

    withToken($this->adminToken)
        ->postJson("/api/v1/admin/users/{$this->user->id}/ban", ['reason' => 'Teste de invalidação de cache'])
        ->assertStatus(200);

    $response = withToken($this->adminToken)->getJson('/api/v1/admin/users'.$query);
    $bannedUser = collect($response->json('data.data'))->firstWhere('id', $this->user->id);

    expect($bannedUser)->not->toBeNull();
    expect($bannedUser['status']['slug'])->toBe('banned');
});

test('com cache ligado, a listagem reflete um usuário recém-criado imediatamente (regressão de invalidação de cache)', function () {
    config(['api.cache.use_cache' => true]);

    // Owner global evita a resolução de organization (role nova é global-scope).
    $ownerRole = Role::where('slug', 'owner')->first();
    $owner = User::factory()->create(['role_id' => $ownerRole->id, 'global_role_id' => $ownerRole->id, 'password' => 'password123']);
    $ownerToken = postJson(route('v1.auth.login'), [
        'email' => $owner->email,
        'password' => 'password123',
    ])->json('data.access_token.token');

    $newEmail = 'depois-do-cache-'.Str::random(6).'@example.com';
    $query = '?search='.urlencode($newEmail);

    // Aquece o cache da listagem (vazia) antes da criação.
    withToken($ownerToken)->getJson('/api/v1/admin/users'.$query);

    $newUserResponse = withToken($ownerToken)->postJson('/api/v1/admin/users', [
        'name' => 'Usuário Criado Depois Do Cache',
        'email' => $newEmail,
        'password' => 'password123',
        'role_id' => $this->adminRole->id,
    ]);

    $newUserResponse->assertStatus(201);
    $newUserId = $newUserResponse->json('data.id');

    $response = withToken($ownerToken)->getJson('/api/v1/admin/users'.$query);
    $ids = collect($response->json('data.data'))->pluck('id');

    expect($ids)->toContain($newUserId);
});

test('não deve permitir que admin promova um usuário a admin via edição (PUT)', function () {
    $response = withToken($this->adminToken)
        ->putJson("/api/v1/admin/users/{$this->user->id}", [
            'name' => $this->user->name,
            'email' => $this->user->email,
            'role_id' => $this->adminRole->id,
        ]);

    $response->assertStatus(400)->assertJsonPath('success', false);

    $this->assertDatabaseHas('admin.users', [
        'id' => $this->user->id,
        'role_id' => $this->userRole->id,
    ]);
});

test('não deve permitir que admin bana a si mesmo', function () {
    $response = withToken($this->adminToken)
        ->postJson("/api/v1/admin/users/{$this->admin->id}/ban", [
            'reason' => 'Teste de auto-banimento',
        ]);

    $response->assertStatus(400)->assertJsonPath('success', false);

    $this->assertDatabaseMissing('admin.users', [
        'id' => $this->admin->id,
        'last_status_id' => UserStatus::where('slug', 'banned')->first()->id,
    ]);
});

test('não deve permitir que admin promova um usuário a uma role igual ou superior à sua', function () {
    $response = withToken($this->adminToken)
        ->patchJson("/api/v1/admin/users/{$this->user->id}/role", [
            'role_id' => $this->adminRole->id,
        ]);

    $response->assertStatus(400)->assertJsonPath('success', false);

    $this->assertDatabaseHas('admin.users', [
        'id' => $this->user->id,
        'role_id' => $this->userRole->id,
    ]);
});

test('deve permitir que owner altere a role de um usuário para admin', function () {
    $ownerRole = Role::where('slug', 'owner')->first();
    $owner = User::factory()->create(['role_id' => $ownerRole->id, 'password' => 'password123']);

    $ownerToken = postJson(route('v1.auth.login'), [
        'email' => $owner->email,
        'password' => 'password123',
    ])->json('data.access_token.token');

    $response = withToken($ownerToken)
        ->patchJson("/api/v1/admin/users/{$this->user->id}/role", [
            'role_id' => $this->adminRole->id,
        ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('admin.users', [
        'id' => $this->user->id,
        'role_id' => $this->adminRole->id,
    ]);
});

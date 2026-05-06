<?php

namespace Tests\Feature\Admin;

use App\Packages\Admin\Users\Models\User;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\UserStatuses\Models\UserStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use function Pest\Laravel\postJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\withToken;
use function Pest\Laravel\artisan;

uses(DatabaseTransactions::class);

beforeEach(function () {
    artisan('optimize:clear');
    
    $this->adminRole = Role::where('slug', 'admin')->first();
    $this->userRole = Role::where('slug', 'user')->first();
    
    // Create Admin
    $this->admin = User::factory()->create([
        'role_id' => $this->adminRole->id,
        'password' => 'password123'
    ]);
    
    $response = postJson(route('v1.auth.login'), [
        'email' => $this->admin->email,
        'password' => 'password123',
    ]);
    
    $this->adminToken = $response->json('data.access_token.token');
    
    // Create Normal User
    $this->user = User::factory()->create([
        'role_id' => $this->userRole->id,
        'password' => 'password123'
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
            'reason' => 'Comportamento inadequado'
        ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('admin.users', [
        'id' => $this->user->id,
        'last_status_id' => UserStatus::where('slug', 'banned')->first()->id
    ]);
});

test('deve permitir que admin altere a role de um usuário', function () {
    $response = withToken($this->adminToken)
        ->patchJson("/api/v1/admin/users/{$this->user->id}/role", [
            'role_id' => $this->adminRole->id
        ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('admin.users', [
        'id' => $this->user->id,
        'role_id' => $this->adminRole->id
    ]);
});

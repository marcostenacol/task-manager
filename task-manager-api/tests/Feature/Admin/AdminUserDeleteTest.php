<?php

namespace Tests\Feature\Admin;

use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use function Pest\Laravel\artisan;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withToken;

uses(DatabaseTransactions::class);

beforeEach(function () {
    artisan('optimize:clear');

    $this->ownerRole = Role::where('slug', 'owner')->first();
    $this->adminRole = Role::where('slug', 'admin')->first();
    $this->userRole = Role::where('slug', 'user')->first();

    $this->owner = User::factory()->create([
        'role_id' => $this->ownerRole->id,
        'password' => 'password123',
    ]);

    $this->ownerToken = postJson(route('v1.auth.login'), [
        'email' => $this->owner->email,
        'password' => 'password123',
    ])->json('data.access_token.token');

    $this->admin = User::factory()->create([
        'role_id' => $this->adminRole->id,
        'password' => 'password123',
    ]);

    $this->adminToken = postJson(route('v1.auth.login'), [
        'email' => $this->admin->email,
        'password' => 'password123',
    ])->json('data.access_token.token');

    $this->normalUser = User::factory()->create([
        'role_id' => $this->userRole->id,
    ]);
});

test('não deve permitir que admin exclua a si mesmo', function () {
    $response = withToken($this->adminToken)
        ->deleteJson("/api/v1/admin/users/{$this->admin->id}");

    $response->assertStatus(400)
        ->assertJsonPath('success', false);

    $this->assertDatabaseHas('admin.users', ['id' => $this->admin->id, 'deleted_at' => null]);
});

test('não deve permitir que admin exclua outro usuário de role igual ou superior', function () {
    $otherAdmin = User::factory()->create(['role_id' => $this->adminRole->id]);

    $response = withToken($this->adminToken)
        ->deleteJson("/api/v1/admin/users/{$otherAdmin->id}");

    $response->assertStatus(400)
        ->assertJsonPath('success', false);

    $this->assertDatabaseHas('admin.users', ['id' => $otherAdmin->id, 'deleted_at' => null]);
});

test('deve permitir que admin exclua um usuário comum (level inferior)', function () {
    $response = withToken($this->adminToken)
        ->deleteJson("/api/v1/admin/users/{$this->normalUser->id}");

    $response->assertStatus(200)
        ->assertJsonPath('success', true);

    $this->assertSoftDeleted('admin.users', ['id' => $this->normalUser->id]);
});

test('usuário excluído não aparece mais na listagem', function () {
    withToken($this->adminToken)->deleteJson("/api/v1/admin/users/{$this->normalUser->id}")
        ->assertStatus(200);

    $response = withToken($this->adminToken)->getJson('/api/v1/admin/users?limit=100');

    $ids = collect($response->json('data.data'))->pluck('id');

    expect($ids)->not->toContain($this->normalUser->id);
});

test('owner pode excluir um admin (level superior ao owner numericamente)', function () {
    $response = withToken($this->ownerToken)
        ->deleteJson("/api/v1/admin/users/{$this->admin->id}");

    $response->assertStatus(200)
        ->assertJsonPath('success', true);

    $this->assertSoftDeleted('admin.users', ['id' => $this->admin->id]);
});

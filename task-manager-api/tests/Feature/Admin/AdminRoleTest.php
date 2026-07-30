<?php

namespace Tests\Feature\Admin;

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

    $this->ownerRole = Role::where('slug', 'owner')->first();
    $this->adminRole = Role::where('slug', 'admin')->first();

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
});

test('não deve permitir que admin exclua a própria role', function () {
    $response = withToken($this->adminToken)
        ->deleteJson("/api/v1/admin/roles/{$this->adminRole->id}");

    $response->assertStatus(400)->assertJsonPath('success', false);

    $this->assertDatabaseHas('admin.roles', ['id' => $this->adminRole->id, 'deleted_at' => null]);
});

test('não deve permitir que admin exclua uma role igual ou superior (owner)', function () {
    $response = withToken($this->adminToken)
        ->deleteJson("/api/v1/admin/roles/{$this->ownerRole->id}");

    $response->assertStatus(400)->assertJsonPath('success', false);
});

test('owner pode excluir uma role de level inferior (mais fraca)', function () {
    $disposableRole = Role::create([
        'id' => (string) Str::uuid(),
        'name' => 'Disposable',
        'slug' => 'disposable-role-'.Str::random(6),
        'level' => 999,
    ]);

    $response = withToken($this->ownerToken)
        ->deleteJson("/api/v1/admin/roles/{$disposableRole->id}");

    $response->assertStatus(200)->assertJsonPath('success', true);

    $this->assertSoftDeleted('admin.roles', ['id' => $disposableRole->id]);
});

test('não deve permitir editar level da própria role', function () {
    $response = withToken($this->ownerToken)
        ->patchJson("/api/v1/admin/roles/{$this->ownerRole->id}/level", ['level' => 5]);

    $response->assertStatus(400)->assertJsonPath('success', false);
});

test('não deve permitir elevar uma role a um level igual ou superior ao do ator', function () {
    $disposableRole = Role::create([
        'id' => (string) Str::uuid(),
        'name' => 'Disposable 2',
        'slug' => 'disposable-role-2-'.Str::random(6),
        'level' => 999,
    ]);

    $response = withToken($this->ownerToken)
        ->patchJson("/api/v1/admin/roles/{$disposableRole->id}/level", ['level' => $this->ownerRole->level]);

    $response->assertStatus(400)->assertJsonPath('success', false);
});

test('deve permitir alterar o level de uma role para um valor válido (mais fraco que o do ator)', function () {
    $disposableRole = Role::create([
        'id' => (string) Str::uuid(),
        'name' => 'Disposable 3',
        'slug' => 'disposable-role-3-'.Str::random(6),
        'level' => 999,
    ]);

    $newLevel = $this->ownerRole->level + 1;

    $response = withToken($this->ownerToken)
        ->patchJson("/api/v1/admin/roles/{$disposableRole->id}/level", ['level' => $newLevel]);

    $response->assertStatus(200)->assertJsonPath('success', true);

    $this->assertDatabaseHas('admin.roles', ['id' => $disposableRole->id, 'level' => $newLevel]);
});

test('não deve permitir renomear a própria role', function () {
    $response = withToken($this->ownerToken)
        ->patchJson("/api/v1/admin/roles/{$this->ownerRole->id}/name", ['name' => 'Novo Nome']);

    $response->assertStatus(400)->assertJsonPath('success', false);
});

test('não deve permitir renomear uma role igual ou superior', function () {
    $response = withToken($this->adminToken)
        ->patchJson("/api/v1/admin/roles/{$this->ownerRole->id}/name", ['name' => 'Novo Nome']);

    $response->assertStatus(400)->assertJsonPath('success', false);
});

test('deve permitir renomear uma role de level inferior (mais fraca)', function () {
    $disposableRole = Role::create([
        'id' => (string) Str::uuid(),
        'name' => 'Disposable Rename',
        'slug' => 'disposable-role-rename-'.Str::random(6),
        'level' => 999,
    ]);

    $response = withToken($this->ownerToken)
        ->patchJson("/api/v1/admin/roles/{$disposableRole->id}/name", ['name' => 'Renamed Role']);

    $response->assertStatus(200)->assertJsonPath('success', true);

    $this->assertDatabaseHas('admin.roles', ['id' => $disposableRole->id, 'name' => 'Renamed Role', 'slug' => $disposableRole->slug]);
});

test('owner consegue criar uma role com scope global', function () {
    $response = withToken($this->ownerToken)->postJson('/api/v1/admin/roles', [
        'name' => 'Global Custom Role',
        'scope' => 'global',
    ]);

    $response->assertStatus(201)->assertJsonPath('success', true);

    $this->assertDatabaseHas('admin.roles', [
        'name' => 'Global Custom Role',
        'scope' => 'global',
        'organization_id' => null,
    ]);
});

test('owner cria role sem informar scope continua criando como organization (padrão)', function () {
    $response = withToken($this->ownerToken)->postJson('/api/v1/admin/roles', [
        'name' => 'Default Scope Role',
    ]);

    $response->assertStatus(201)->assertJsonPath('success', true);

    $this->assertDatabaseHas('admin.roles', [
        'name' => 'Default Scope Role',
        'scope' => 'organization',
        'organization_id' => null,
    ]);
});

test('renomear uma role não deve alterar o slug (identificador estável usado em lookups)', function () {
    $disposableRole = Role::create([
        'id' => (string) Str::uuid(),
        'name' => 'Disposable Slug Check',
        'slug' => 'disposable-slug-check-'.Str::random(6),
        'level' => 999,
    ]);

    $originalSlug = $disposableRole->slug;

    withToken($this->ownerToken)
        ->patchJson("/api/v1/admin/roles/{$disposableRole->id}/name", ['name' => 'Nome Completamente Diferente']);

    $this->assertDatabaseHas('admin.roles', ['id' => $disposableRole->id, 'slug' => $originalSlug]);
});

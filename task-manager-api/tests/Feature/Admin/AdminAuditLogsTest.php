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

<?php

namespace Tests\Feature\Admin;

use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Settings\Models\Setting;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use function Pest\Laravel\artisan;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withToken;

uses(DatabaseTransactions::class);

beforeEach(function () {
    artisan('optimize:clear');

    $adminRole = Role::where('slug', 'admin')->first();
    $userRole = Role::where('slug', 'user')->first();

    $this->admin = User::factory()->create(['role_id' => $adminRole->id, 'password' => 'password123']);
    $this->adminToken = postJson(route('v1.auth.login'), [
        'email' => $this->admin->email,
        'password' => 'password123',
    ])->json('data.access_token.token');

    $this->user = User::factory()->create(['role_id' => $userRole->id, 'password' => 'password123']);
    $this->userToken = postJson(route('v1.auth.login'), [
        'email' => $this->user->email,
        'password' => 'password123',
    ])->json('data.access_token.token');
});

test('não deve permitir que usuário comum acesse as configurações', function () {
    $response = withToken($this->userToken)->getJson('/api/v1/admin/settings');

    $response->assertStatus(403);
});

test('deve permitir que admin liste as configurações', function () {
    $response = withToken($this->adminToken)->getJson('/api/v1/admin/settings');

    $response->assertStatus(200)
        ->assertJsonPath('success', true);
});

test('deve permitir que admin atualize o valor de uma configuração', function () {
    $setting = Setting::where('name', 'token_expiration_minutes')->first();

    $response = withToken($this->adminToken)
        ->putJson("/api/v1/admin/settings/{$setting->id}", ['value' => '2000']);

    $response->assertStatus(200)
        ->assertJsonPath('data.value', '2000');

    $this->assertDatabaseHas('admin.settings', [
        'id' => $setting->id,
        'value' => '2000',
    ]);
});

test('não deve permitir atualizar configuração com valor vazio', function () {
    $setting = Setting::where('name', 'token_expiration_minutes')->first();

    $response = withToken($this->adminToken)
        ->putJson("/api/v1/admin/settings/{$setting->id}", ['value' => '']);

    $response->assertStatus(422);
});

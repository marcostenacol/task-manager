<?php

namespace Tests\Feature\Admin;

use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use function Pest\Laravel\artisan;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withToken;

uses(DatabaseTransactions::class);

beforeEach(function () {
    artisan('optimize:clear');
    artisan('db:seed', ['--class' => 'PermissionSeeder']);

    $this->adminRole = Role::where('slug', 'admin')->first();
    $this->userRole = Role::where('slug', 'user')->first();

    $this->admin = User::factory()->create([
        'role_id' => $this->adminRole->id,
        'password' => 'password123',
    ]);

    $this->adminToken = postJson(route('v1.auth.login'), [
        'email' => $this->admin->email,
        'password' => 'password123',
    ])->json('data.access_token.token');

    $this->user = User::factory()->create([
        'role_id' => $this->userRole->id,
        'password' => 'password123',
    ]);

    $this->userToken = postJson(route('v1.auth.login'), [
        'email' => $this->user->email,
        'password' => 'password123',
    ])->json('data.access_token.token');
});

test('admin com permissão admin.roles.manage lista as permissões cadastradas', function () {
    $response = withToken($this->adminToken)->getJson(route('v1.admin.permissions.index'));

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Permissões recuperadas com sucesso.');

    expect($response->json('data'))->not->toBeEmpty();

    $response->assertJsonFragment(['name' => 'admin.roles.manage']);
});

test('usuário comum sem permissão admin.roles.manage recebe 403 ao listar permissões', function () {
    $response = withToken($this->userToken)->getJson(route('v1.admin.permissions.index'));

    $response->assertStatus(403)->assertJsonPath('success', false);
});

test('requisição sem token recebe 401 ao listar permissões', function () {
    $response = getJson(route('v1.admin.permissions.index'));

    $response->assertStatus(401);
});

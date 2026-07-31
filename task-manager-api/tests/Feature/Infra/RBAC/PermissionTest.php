<?php

use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use function Pest\Laravel\artisan;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withToken;

uses(DatabaseTransactions::class);

beforeEach(function () {
    artisan('optimize:clear');
    artisan('db:seed', ['--class' => 'PermissionSeeder']);
});

test('usuário admin tem todas as permissões e pode acessar rotas protegidas', function () {
    $adminRole = Role::where('slug', 'admin')->first();
    $user = User::factory()->create([
        'email' => 'admin-test@example.com',
        'password' => 'password123',
        'role_id' => $adminRole->id,
    ]);

    // Login
    $response = postJson(route('v1.auth.login'), [
        'email' => 'admin-test@example.com',
        'password' => 'password123',
    ]);

    $token = $response->json('data.access_token.token');

    // Tentar acessar rota protegida por permissão 'admin.users.list'
    withToken($token)
        ->getJson(route('v1.health-auth'))
        ->assertStatus(200)
        ->assertJson(['success' => true]);
});

test('usuário comum sem permissão recebe 403 em rotas admin', function () {
    $userRole = Role::where('slug', 'user')->first();
    $user = User::factory()->create([
        'email' => 'user-test@example.com',
        'password' => 'password123',
        'role_id' => $userRole->id,
    ]);

    // Login
    $response = postJson(route('v1.auth.login'), [
        'email' => 'user-test@example.com',
        'password' => 'password123',
    ]);
    $token = $response->json('data.access_token.token');

    // Tentar acessar rota protegida por permissão 'admin.users.list' (usuário comum não tem)
    withToken($token)
        ->getJson(route('v1.health-auth'))
        ->assertStatus(403)
        ->assertJsonFragment(['message' => 'Você não possui permissão para acessar esse recurso!']);
});

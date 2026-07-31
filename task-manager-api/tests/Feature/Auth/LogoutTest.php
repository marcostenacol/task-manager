<?php

use App\Packages\Admin\Users\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use function Pest\Laravel\postJson;
use function Pest\Laravel\withToken;

uses(DatabaseTransactions::class);

test('should logout successfully', function () {
    // 1. Cria usuário e faz login para obter token
    $user = User::factory()->create([
        'email' => 'logout@example.com',
        'password' => 'password123',
    ]);

    $loginResponse = postJson(route('v1.auth.login'), [
        'email' => 'logout@example.com',
        'password' => 'password123',
    ]);

    $token = $loginResponse->json('data.access_token.token');

    // 2. Tenta acessar rota protegida (health) para garantir que funciona
    withToken($token)->getJson(route('v1.health'))
        ->assertStatus(200);

    // 3. Faz logout
    withToken($token)->postJson(route('v1.auth.logout'))
        ->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Logout realizado com sucesso.');

    // 4. Tenta acessar novamente e deve falhar (não autorizado)
    withToken($token)->getJson(route('v1.health'))
        ->assertStatus(401);
});

test('should not logout without token', function () {
    postJson(route('v1.auth.logout'))
        ->assertStatus(401);
});

<?php

use App\Packages\Admin\Users\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use function Pest\Laravel\postJson;
use function Pest\Laravel\withToken;

uses(DatabaseTransactions::class);

test('should refresh token successfully', function () {
    // 1. Login
    User::factory()->create([
        'email' => 'refresh@example.com',
        'password' => 'password123',
    ]);

    $loginResponse = postJson(route('v1.auth.login'), [
        'email' => 'refresh@example.com',
        'password' => 'password123',
    ]);

    $oldAccessToken = $loginResponse->json('data.access_token.token');
    $refreshToken = $loginResponse->json('data.refresh_token.token');

    // 2. Refresh
    $refreshResponse = postJson(route('v1.auth.refresh'), [
        'refresh_token' => $refreshToken,
    ]);

    $refreshResponse->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Token atualizado com sucesso.')
        ->assertJsonStructure([
            'data' => [
                'access_token',
                'refresh_token',
                'user',
            ],
        ]);

    $newAccessToken = $refreshResponse->json('data.access_token.token');

    // 3. O novo token deve funcionar
    withToken($newAccessToken)->getJson(route('v1.health'))
        ->assertStatus(200);

    // 4. O token antigo deve ter sido invalidado (pelo processo de refresh que expira o access token pai)
    withToken($oldAccessToken)->getJson(route('v1.health'))
        ->assertStatus(401);
});

test('should detect refresh token reuse (security)', function () {
    // 1. Login
    User::factory()->create([
        'email' => 'security@example.com',
        'password' => 'password123',
    ]);

    $loginResponse = postJson(route('v1.auth.login'), [
        'email' => 'security@example.com',
        'password' => 'password123',
    ]);

    $refreshToken = $loginResponse->json('data.refresh_token.token');

    // 2. Primeiro Refresh (OK)
    postJson(route('v1.auth.refresh'), [
        'refresh_token' => $refreshToken,
    ])->assertStatus(200);

    // 3. Segundo Refresh com o MESMO token (Reuso detectado!)
    // O banco deve disparar um erro e invalidar tudo para este usuário
    postJson(route('v1.auth.refresh'), [
        'refresh_token' => $refreshToken,
    ])->assertStatus(401)
        ->assertJsonPath('message', 'Aviso de Segurança: Token de atualização inválido ou reciclado, logue novamente nas suas sessões!');
});

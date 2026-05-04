<?php

use App\Packages\Admin\Users\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use function Pest\Laravel\postJson;

uses(DatabaseTransactions::class);

test('should login successfully with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'login@example.com',
        'password' => 'password123',
    ]);
    $response = postJson(route('v1.auth.login'), [
        'email' => 'login@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Login realizado com sucesso.')
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'access_token' => ['token', 'created_at'],
                'refresh_token' => ['token', 'created_at'],
                'user' => [
                    'id',
                    'name',
                    'email',
                    'status',
                    'role',
                    'permissions'
                ]
            ]
        ]);
});

test('should not login with invalid password', function () {
    User::factory()->create([
        'email' => 'wrong-pass@example.com',
        'password' => 'correct-password',
    ]);

    $payload = [
        'email' => 'wrong-pass@example.com',
        'password' => 'wrong-password',
    ];

    $response = postJson(route('v1.auth.login'), $payload);

    $response->assertStatus(400)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Usuário ou senha incorretos!');
});

test('should not login with non-existent user', function () {
    $payload = [
        'email' => 'non-existent@example.com',
        'password' => 'password123',
    ];

    $response = postJson(route('v1.auth.login'), $payload);

    $response->assertStatus(400)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Usuário não encontrado!');
});

test('should validate required fields on login', function () {
    $response = postJson(route('v1.auth.login'), []);

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'errors' => ['email', 'password']
            ]
        ]);
});

<?php

use App\Packages\Admin\Users\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

uses(DatabaseTransactions::class);

test('should register a new user successfully', function () {
    $payload = [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'cpf' => '52998224725',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $response = postJson(route('v1.auth.register'), $payload);

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Usuário registrado com sucesso.')
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'name',
                'email',
                'role_slug',
                'created_at',
            ],
        ]);

    assertDatabaseHas('admin.users', [
        'email' => 'test@example.com',
        'name' => 'Test User',
        'cpf' => '52998224725',
    ]);
});

test('should not register a user with an existing email', function () {
    User::factory()->create([
        'email' => 'existing@example.com',
    ]);

    $payload = [
        'name' => 'Another User',
        'email' => 'existing@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $response = postJson(route('v1.auth.register'), $payload);

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('data.errors.email.0', 'O campo email já está sendo utilizado.');
});

test('should validate required fields', function () {
    $response = postJson(route('v1.auth.register'), []);

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'errors' => ['name', 'email', 'password'],
            ],
        ]);
});

test('should not register when password confirmation does not match', function () {
    $payload = [
        'name' => 'Mismatch User',
        'email' => 'mismatch@example.com',
        'password' => 'password123',
        'password_confirmation' => 'differentpass',
    ];

    $response = postJson(route('v1.auth.register'), $payload);

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['data' => ['errors' => ['password']]]);
});

test('should not register with a cpf already in use', function () {
    User::factory()->create(['cpf' => '52998224725']);

    $payload = [
        'name' => 'CPF Duplicado',
        'email' => 'cpf.duplicado@example.com',
        'cpf' => '52998224725',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    $response = postJson(route('v1.auth.register'), $payload);

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['data' => ['errors' => ['cpf']]]);
});

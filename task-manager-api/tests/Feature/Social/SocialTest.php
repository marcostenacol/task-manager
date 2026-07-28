<?php

namespace Tests\Feature\Social;

use App\Packages\Admin\Users\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\postJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\putJson;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\withToken;
use function Pest\Laravel\artisan;

uses(DatabaseTransactions::class);

beforeEach(function () {
    artisan('optimize:clear');
    $this->user = User::factory()->create(['password' => 'password123']);
    
    $response = postJson(route('v1.auth.login'), [
        'email' => $this->user->email,
        'password' => 'password123',
    ]);
    
    $this->token = $response->json('data.access_token.token');
});

test('deve recuperar o perfil do usuário', function () {
    $response = withToken($this->token)
        ->getJson('/api/v1/social/profile');

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.email', $this->user->email);
});

test('deve atualizar a bio do usuário', function () {
    $response = withToken($this->token)
        ->putJson('/api/v1/social/profile', [
            'name' => 'Novo Nome',
            'bio' => 'Minha nova bio estilosa'
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.bio', 'Minha nova bio estilosa');

    $this->assertDatabaseHas('admin.users', [
        'id' => $this->user->id,
        'bio' => 'Minha nova bio estilosa'
    ]);
});

test('deve fazer upload de avatar', function () {
    $file = UploadedFile::fake()->create('avatar.jpg', 100);

    $response = withToken($this->token)
        ->postJson('/api/v1/social/profile/avatar', [
            'avatar' => $file
        ]);

    $response->assertStatus(200);
    $this->user->refresh();
    $this->assertNotNull($this->user->avatar_path);
    Storage::disk('public')->assertExists($this->user->avatar_path);
    
    // Limpeza
    Storage::disk('public')->delete($this->user->avatar_path);
});

test('deve gerenciar contatos', function () {
    // Criar um contato
    $response = withToken($this->token)
        ->postJson('/api/v1/social/contacts', [
            'type' => 'linkedin',
            'value' => 'linkedin.com/in/user',
            'is_primary' => true
        ]);

    $response->assertStatus(200);
    $contactId = $response->json('data.id');

    // Sincronizar contatos (bulk update)
    $responseUpdate = withToken($this->token)
        ->putJson('/api/v1/social/contacts', [
            'contacts' => [
                ['type' => 'github', 'value' => 'github.com/user', 'is_primary' => true],
                ['type' => 'whatsapp', 'value' => '11999999999', 'is_primary' => false]
            ]
        ]);

    $responseUpdate->assertStatus(200);
    $this->assertDatabaseHas('social.user_contacts', ['type' => 'github', 'user_id' => $this->user->id]);
    $this->assertDatabaseHas('social.user_contacts', ['type' => 'whatsapp', 'user_id' => $this->user->id]);
    // O contato antigo (linkedin) deve ter sido removido pelo UpdateContactsService
    $this->assertDatabaseMissing('social.user_contacts', ['id' => $contactId]);
});

test('deve remover um contato individualmente', function () {
    $response = withToken($this->token)
        ->postJson('/api/v1/social/contacts', [
            'type' => 'email',
            'value' => 'contato@exemplo.com',
            'is_primary' => false,
        ]);

    $response->assertStatus(200);
    $contactId = $response->json('data.id');

    $responseDelete = withToken($this->token)
        ->deleteJson("/api/v1/social/contacts/{$contactId}");

    $responseDelete->assertStatus(200)
        ->assertJsonPath('success', true);

    $this->assertDatabaseMissing('social.user_contacts', ['id' => $contactId]);
});

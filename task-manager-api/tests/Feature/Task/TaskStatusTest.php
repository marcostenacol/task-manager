<?php

namespace Tests\Feature\Task;

use App\Packages\Admin\Users\Models\User;
use App\Packages\Task\Statuses\Models\TaskStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use function Pest\Laravel\artisan;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withToken;

uses(DatabaseTransactions::class);

beforeEach(function () {
    artisan('optimize:clear');

    $this->user = User::factory()->create(['password' => 'password123']);

    $this->token = postJson(route('v1.auth.login'), [
        'email' => $this->user->email,
        'password' => 'password123',
    ])->json('data.access_token.token');
});

test('usuário autenticado lista os status de tarefa cadastrados', function () {
    $response = withToken($this->token)->getJson(route('v1.task-statuses.index'));

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Status recuperados com sucesso.');

    $slugsCadastrados = TaskStatus::orderBy('slug')->pluck('slug')->all();

    expect($response->json('data'))->toHaveCount(count($slugsCadastrados));
    $response->assertJsonFragment(['slug' => 'pending']);
    $response->assertJsonFragment(['slug' => 'done']);
});

test('requisição sem token recebe 401 ao listar status de tarefa', function () {
    $response = getJson(route('v1.task-statuses.index'));

    $response->assertStatus(401);
});

<?php

namespace Tests\Feature\Task;

use App\Packages\Admin\Users\Models\User;
use App\Packages\Task\Priorities\Models\TaskPriority;
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

test('usuário autenticado lista as prioridades de tarefa cadastradas, ordenadas por order', function () {
    $response = withToken($this->token)->getJson(route('v1.task-priorities.index'));

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Prioridades recuperadas com sucesso.');

    $orderEsperada = TaskPriority::orderBy('order')->pluck('slug')->all();
    $orderRecebida = array_column($response->json('data'), 'slug');

    expect($orderRecebida)->toEqual($orderEsperada);
});

test('requisição sem token recebe 401 ao listar prioridades de tarefa', function () {
    $response = getJson(route('v1.task-priorities.index'));

    $response->assertStatus(401);
});

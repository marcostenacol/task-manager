<?php

use App\Packages\Admin\Users\Models\User;
use App\Packages\Task\Priorities\Models\TaskPriority;
use App\Packages\Task\Statuses\Models\TaskStatus;
use App\Packages\Task\Tasks\Models\Task;
use Illuminate\Foundation\Testing\DatabaseTransactions;

use function Pest\Laravel\artisan;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withToken;

uses(DatabaseTransactions::class);

beforeEach(function () {
    artisan('optimize:clear');
    $this->user = User::factory()->create(['password' => 'password123']);

    $response = postJson(route('v1.auth.login'), [
        'email' => $this->user->email,
        'password' => 'password123',
    ]);

    $this->token = $response->json('data.access_token.token');

    $this->statusPending = TaskStatus::where('slug', 'pending')->first();
    $this->statusDone = TaskStatus::where('slug', 'done')->first();
    $this->priorityHigh = TaskPriority::where('slug', 'high')->first();
});

test('deve criar uma tarefa com sucesso', function () {
    $payload = [
        'title' => 'Minha primeira tarefa',
        'description' => 'Descrição detalhada da tarefa',
        'status_id' => $this->statusPending->id,
        'priority_id' => $this->priorityHigh->id,
        'due_date' => now()->addDays(2)->toIso8601String(),
    ];

    $response = withToken($this->token)
        ->postJson('/api/v1/tasks', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.title', 'Minha primeira tarefa');

    $this->assertDatabaseHas('tasks', [
        'title' => 'Minha primeira tarefa',
        'user_id' => $this->user->id,
    ]);
});

test('deve listar tarefas do usuário com filtros', function () {
    // Criar tarefas para o usuário
    Task::create([
        'user_id' => $this->user->id,
        'status_id' => $this->statusPending->id,
        'priority_id' => $this->priorityHigh->id,
        'title' => 'Tarefa urgente',
    ]);

    $response = withToken($this->token)
        ->getJson('/api/v1/tasks?search=urgente');

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data.data');
});

test('deve atualizar uma tarefa', function () {
    $task = Task::create([
        'user_id' => $this->user->id,
        'status_id' => $this->statusPending->id,
        'priority_id' => $this->priorityHigh->id,
        'title' => 'Tarefa original',
    ]);

    $payload = ['title' => 'Tarefa atualizada'];

    $response = withToken($this->token)
        ->putJson("/api/v1/tasks/{$task->id}", $payload);

    $response->assertStatus(200)
        ->assertJsonPath('data.title', 'Tarefa atualizada');
});

test('deve mudar o status de uma tarefa', function () {
    $task = Task::create([
        'user_id' => $this->user->id,
        'status_id' => $this->statusPending->id,
        'priority_id' => $this->priorityHigh->id,
        'title' => 'Tarefa para concluir',
    ]);

    $response = withToken($this->token)
        ->patchJson("/api/v1/tasks/{$task->id}/status", [
            'status_id' => $this->statusDone->id,
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.status.slug', $this->statusDone->slug);

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'status_id' => $this->statusDone->id,
    ]);
});

test('deve deletar uma tarefa', function () {
    $task = Task::create([
        'user_id' => $this->user->id,
        'status_id' => $this->statusPending->id,
        'priority_id' => $this->priorityHigh->id,
        'title' => 'Tarefa para deletar',
    ]);

    $response = withToken($this->token)
        ->deleteJson("/api/v1/tasks/{$task->id}");

    $response->assertStatus(200);
    $this->assertSoftDeleted('tasks', ['id' => $task->id]);
});

test('com cache ligado, a listagem reflete uma tarefa recém-criada imediatamente (regressão de invalidação de cache)', function () {
    config(['api.cache.use_cache' => true]);

    // Aquece o cache da listagem (vazia).
    withToken($this->token)->getJson('/api/v1/tasks?search=Tarefa Pós Cache');

    $createResponse = withToken($this->token)->postJson('/api/v1/tasks', [
        'title' => 'Tarefa Pós Cache',
        'status_id' => $this->statusPending->id,
        'priority_id' => $this->priorityHigh->id,
    ]);
    $createResponse->assertStatus(201);
    $newTaskId = $createResponse->json('data.id');

    $response = withToken($this->token)->getJson('/api/v1/tasks?search=Tarefa Pós Cache');
    $ids = collect($response->json('data.data'))->pluck('id');

    expect($ids)->toContain($newTaskId);
});

test('com cache ligado, a listagem reflete a exclusão de uma tarefa imediatamente (regressão de invalidação de cache)', function () {
    config(['api.cache.use_cache' => true]);

    $task = Task::create([
        'user_id' => $this->user->id,
        'status_id' => $this->statusPending->id,
        'priority_id' => $this->priorityHigh->id,
        'title' => 'Tarefa Que Vai Ser Excluída',
    ]);

    // Aquece o cache da listagem (com a task ainda presente).
    withToken($this->token)->getJson('/api/v1/tasks?search=Tarefa Que Vai Ser Excluída');

    withToken($this->token)->deleteJson("/api/v1/tasks/{$task->id}")->assertStatus(200);

    $response = withToken($this->token)->getJson('/api/v1/tasks?search=Tarefa Que Vai Ser Excluída');
    $ids = collect($response->json('data.data'))->pluck('id');

    expect($ids)->not->toContain($task->id);
});

test('listagem padrão não mostra tarefas concluídas', function () {
    Task::create([
        'user_id' => $this->user->id,
        'status_id' => $this->statusPending->id,
        'priority_id' => $this->priorityHigh->id,
        'title' => 'Tarefa ativa',
    ]);

    Task::create([
        'user_id' => $this->user->id,
        'status_id' => $this->statusDone->id,
        'priority_id' => $this->priorityHigh->id,
        'title' => 'Tarefa concluída',
    ]);

    $response = withToken($this->token)->getJson('/api/v1/tasks');

    $titles = collect($response->json('data.data'))->pluck('title');
    expect($titles)->toContain('Tarefa ativa');
    expect($titles)->not->toContain('Tarefa concluída');
});

test('filtro completed=1 só mostra tarefas concluídas', function () {
    Task::create([
        'user_id' => $this->user->id,
        'status_id' => $this->statusPending->id,
        'priority_id' => $this->priorityHigh->id,
        'title' => 'Tarefa ativa',
    ]);

    Task::create([
        'user_id' => $this->user->id,
        'status_id' => $this->statusDone->id,
        'priority_id' => $this->priorityHigh->id,
        'title' => 'Tarefa concluída',
    ]);

    $response = withToken($this->token)->getJson('/api/v1/tasks?completed=1');

    $titles = collect($response->json('data.data'))->pluck('title');
    expect($titles)->toContain('Tarefa concluída');
    expect($titles)->not->toContain('Tarefa ativa');
});

test('completed=false (string, como vem de query string) não é tratado como truthy', function () {
    Task::create([
        'user_id' => $this->user->id,
        'status_id' => $this->statusPending->id,
        'priority_id' => $this->priorityHigh->id,
        'title' => 'Tarefa ativa',
    ]);

    Task::create([
        'user_id' => $this->user->id,
        'status_id' => $this->statusDone->id,
        'priority_id' => $this->priorityHigh->id,
        'title' => 'Tarefa concluída',
    ]);

    $response = withToken($this->token)->getJson('/api/v1/tasks?completed=false');

    $titles = collect($response->json('data.data'))->pluck('title');
    expect($titles)->toContain('Tarefa ativa');
    expect($titles)->not->toContain('Tarefa concluída');
});

test('view=all mostra tarefas ativas e concluídas juntas (usado pelo Kanban)', function () {
    Task::create([
        'user_id' => $this->user->id,
        'status_id' => $this->statusPending->id,
        'priority_id' => $this->priorityHigh->id,
        'title' => 'Tarefa ativa',
    ]);

    Task::create([
        'user_id' => $this->user->id,
        'status_id' => $this->statusDone->id,
        'priority_id' => $this->priorityHigh->id,
        'title' => 'Tarefa concluída',
    ]);

    $response = withToken($this->token)->getJson('/api/v1/tasks?view=all');

    $titles = collect($response->json('data.data'))->pluck('title');
    expect($titles)->toContain('Tarefa ativa');
    expect($titles)->toContain('Tarefa concluída');
});

test('paginação real: limit menor que o total respeita o limite e o total geral', function () {
    foreach (range(1, 5) as $i) {
        Task::create([
            'user_id' => $this->user->id,
            'status_id' => $this->statusPending->id,
            'priority_id' => $this->priorityHigh->id,
            'title' => "Tarefa paginação {$i}",
        ]);
    }

    $response = withToken($this->token)->getJson('/api/v1/tasks?limit=2&page=1');

    $response->assertStatus(200);
    expect($response->json('data.data'))->toHaveCount(2);
    expect($response->json('data.total'))->toBe(5);
    expect($response->json('data.last_page'))->toBe(3);
});

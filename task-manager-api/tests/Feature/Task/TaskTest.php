<?php

use App\Packages\Admin\Users\Models\User;
use App\Packages\Task\Statuses\Models\TaskStatus;
use App\Packages\Task\Priorities\Models\TaskPriority;
use App\Packages\Task\Tasks\Models\Task;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\patchJson;
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
        'due_date' => now()->addDays(2)->toIso8601String()
    ];

    $response = withToken($this->token)
        ->postJson('/api/v1/tasks', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.title', 'Minha primeira tarefa');

    $this->assertDatabaseHas('tasks', [
        'title' => 'Minha primeira tarefa',
        'user_id' => $this->user->id
    ]);
});

test('deve listar tarefas do usuário com filtros', function () {
    // Criar tarefas para o usuário
    Task::create([
        'user_id' => $this->user->id,
        'status_id' => $this->statusPending->id,
        'priority_id' => $this->priorityHigh->id,
        'title' => 'Tarefa urgente'
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
        'title' => 'Tarefa original'
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
        'title' => 'Tarefa para concluir'
    ]);

    $response = withToken($this->token)
        ->patchJson("/api/v1/tasks/{$task->id}/status", [
            'status_id' => $this->statusDone->id
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.status.slug', $this->statusDone->slug);
    
    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'status_id' => $this->statusDone->id
    ]);
});

test('deve deletar uma tarefa', function () {
    $task = Task::create([
        'user_id' => $this->user->id,
        'status_id' => $this->statusPending->id,
        'priority_id' => $this->priorityHigh->id,
        'title' => 'Tarefa para deletar'
    ]);

    $response = withToken($this->token)
        ->deleteJson("/api/v1/tasks/{$task->id}");

    $response->assertStatus(200);
    $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
});

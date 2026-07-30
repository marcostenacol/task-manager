<?php

use App\Packages\Admin\Organizations\Models\Organization;
use App\Packages\Admin\Organizations\Models\UserOrganization;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use App\Packages\Task\Priorities\Models\TaskPriority;
use App\Packages\Task\Statuses\Models\TaskStatus;
use App\Packages\Task\Tasks\Models\Task;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;

use function Pest\Laravel\artisan;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withToken;

uses(DatabaseTransactions::class);

beforeEach(function () {
    artisan('optimize:clear');

    $this->status_pending = TaskStatus::where('slug', 'pending')->first();
    $this->priority_high = TaskPriority::where('slug', 'high')->first();

    $this->org_admin_role = Role::where('slug', 'org-admin')->first();
    $this->user_role = Role::where('slug', 'user')->first();
    $this->global_admin_role = Role::where('slug', 'admin')->first();

    $this->org = Organization::create(['id' => (string) Str::uuid(), 'name' => 'Task Org', 'slug' => 'task-org-'.Str::random(6)]);

    $this->member_a = User::factory()->create([
        'role_id' => $this->org_admin_role->id,
        'active_organization_id' => $this->org->id,
        'password' => 'password123',
    ]);
    UserOrganization::create(['user_id' => $this->member_a->id, 'organization_id' => $this->org->id, 'role_id' => $this->org_admin_role->id]);

    $this->member_b = User::factory()->create([
        'role_id' => $this->user_role->id,
        'active_organization_id' => $this->org->id,
        'password' => 'password123',
    ]);
    UserOrganization::create(['user_id' => $this->member_b->id, 'organization_id' => $this->org->id, 'role_id' => $this->user_role->id]);

    $this->outsider = User::factory()->create(['role_id' => $this->user_role->id, 'password' => 'password123']);

    $this->global_admin = User::factory()->create([
        'role_id' => $this->global_admin_role->id,
        'global_role_id' => $this->global_admin_role->id,
        'password' => 'password123',
    ]);

    $this->token_a = postJson(route('v1.auth.login'), ['email' => $this->member_a->email, 'password' => 'password123'])->json('data.access_token.token');
    $this->token_b = postJson(route('v1.auth.login'), ['email' => $this->member_b->email, 'password' => 'password123'])->json('data.access_token.token');
    $this->token_outsider = postJson(route('v1.auth.login'), ['email' => $this->outsider->email, 'password' => 'password123'])->json('data.access_token.token');
    $this->token_global = postJson(route('v1.auth.login'), ['email' => $this->global_admin->email, 'password' => 'password123'])->json('data.access_token.token');
});

test('cria task pessoal por padrão (sem visibility informado)', function () {
    $response = withToken($this->token_a)->postJson('/api/v1/tasks', [
        'title' => 'Task pessoal padrão',
        'status_id' => $this->status_pending->id,
        'priority_id' => $this->priority_high->id,
    ]);

    $response->assertStatus(201)->assertJsonPath('data.visibility', 'personal');

    $this->assertDatabaseHas('tasks', [
        'title' => 'Task pessoal padrão',
        'visibility' => 'personal',
        'organization_id' => null,
    ]);
});

test('cria task de organization explicitamente', function () {
    $response = withToken($this->token_a)->postJson('/api/v1/tasks', [
        'title' => 'Task da organization',
        'status_id' => $this->status_pending->id,
        'priority_id' => $this->priority_high->id,
        'visibility' => 'organization',
    ]);

    $response->assertStatus(201)->assertJsonPath('data.visibility', 'organization');

    $this->assertDatabaseHas('tasks', [
        'title' => 'Task da organization',
        'visibility' => 'organization',
        'organization_id' => $this->org->id,
    ]);
});

test('outro membro da mesma organization vê a task de organization na listagem', function () {
    Task::create([
        'user_id' => $this->member_a->id,
        'organization_id' => $this->org->id,
        'visibility' => 'organization',
        'status_id' => $this->status_pending->id,
        'priority_id' => $this->priority_high->id,
        'title' => 'Task compartilhada',
    ]);

    $response = withToken($this->token_b)->getJson('/api/v1/tasks');

    $titles = collect($response->json('data.data'))->pluck('title');
    expect($titles)->toContain('Task compartilhada');
});

test('membro de outra organization não vê a task de organization de terceiros', function () {
    Task::create([
        'user_id' => $this->member_a->id,
        'organization_id' => $this->org->id,
        'visibility' => 'organization',
        'status_id' => $this->status_pending->id,
        'priority_id' => $this->priority_high->id,
        'title' => 'Task compartilhada isolada',
    ]);

    $response = withToken($this->token_outsider)->getJson('/api/v1/tasks');

    $titles = collect($response->json('data.data'))->pluck('title');
    expect($titles)->not->toContain('Task compartilhada isolada');
});

test('task pessoal de um membro não é vista por outro membro da mesma organization', function () {
    Task::create([
        'user_id' => $this->member_a->id,
        'organization_id' => null,
        'visibility' => 'personal',
        'status_id' => $this->status_pending->id,
        'priority_id' => $this->priority_high->id,
        'title' => 'Task privada do member A',
    ]);

    $response = withToken($this->token_b)->getJson('/api/v1/tasks');

    $titles = collect($response->json('data.data'))->pluck('title');
    expect($titles)->not->toContain('Task privada do member A');
});

test('admin global vê tasks de organization de qualquer organization, mas não tasks pessoais de terceiros', function () {
    Task::create([
        'user_id' => $this->member_a->id,
        'organization_id' => $this->org->id,
        'visibility' => 'organization',
        'status_id' => $this->status_pending->id,
        'priority_id' => $this->priority_high->id,
        'title' => 'Task de organization visível pro global',
    ]);

    Task::create([
        'user_id' => $this->member_a->id,
        'organization_id' => null,
        'visibility' => 'personal',
        'status_id' => $this->status_pending->id,
        'priority_id' => $this->priority_high->id,
        'title' => 'Task pessoal invisível pro global',
    ]);

    $response = withToken($this->token_global)->getJson('/api/v1/tasks');

    $titles = collect($response->json('data.data'))->pluck('title');
    expect($titles)->toContain('Task de organization visível pro global');
    expect($titles)->not->toContain('Task pessoal invisível pro global');
});

test('outro membro da organization consegue atualizar uma task de organization', function () {
    $task = Task::create([
        'user_id' => $this->member_a->id,
        'organization_id' => $this->org->id,
        'visibility' => 'organization',
        'status_id' => $this->status_pending->id,
        'priority_id' => $this->priority_high->id,
        'title' => 'Task editável em grupo',
    ]);

    $response = withToken($this->token_b)->putJson("/api/v1/tasks/{$task->id}", ['title' => 'Título atualizado pelo member B']);

    $response->assertStatus(200)->assertJsonPath('data.title', 'Título atualizado pelo member B');
});

test('outro membro da organization não consegue excluir a task de organization de outra pessoa', function () {
    $task = Task::create([
        'user_id' => $this->member_a->id,
        'organization_id' => $this->org->id,
        'visibility' => 'organization',
        'status_id' => $this->status_pending->id,
        'priority_id' => $this->priority_high->id,
        'title' => 'Task protegida contra exclusão',
    ]);

    $response = withToken($this->token_b)->deleteJson("/api/v1/tasks/{$task->id}");

    $response->assertStatus(404);
    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'deleted_at' => null]);
});

test('admin global consegue excluir a task de organization de outra pessoa', function () {
    $task = Task::create([
        'user_id' => $this->member_a->id,
        'organization_id' => $this->org->id,
        'visibility' => 'organization',
        'status_id' => $this->status_pending->id,
        'priority_id' => $this->priority_high->id,
        'title' => 'Task excluível pelo global',
    ]);

    $response = withToken($this->token_global)->deleteJson("/api/v1/tasks/{$task->id}");

    $response->assertStatus(200);
    $this->assertSoftDeleted('tasks', ['id' => $task->id]);
});

test('membro de outra organization não consegue ver o detalhe de uma task de organization alheia', function () {
    $task = Task::create([
        'user_id' => $this->member_a->id,
        'organization_id' => $this->org->id,
        'visibility' => 'organization',
        'status_id' => $this->status_pending->id,
        'priority_id' => $this->priority_high->id,
        'title' => 'Task de organization protegida',
    ]);

    $response = withToken($this->token_outsider)->getJson("/api/v1/tasks/{$task->id}");

    $response->assertStatus(404);
});

test('dono consegue mudar uma task pessoal para organization', function () {
    $task = Task::create([
        'user_id' => $this->member_a->id,
        'organization_id' => null,
        'visibility' => 'personal',
        'status_id' => $this->status_pending->id,
        'priority_id' => $this->priority_high->id,
        'title' => 'Task que vira compartilhada',
    ]);

    $response = withToken($this->token_a)->putJson("/api/v1/tasks/{$task->id}", ['visibility' => 'organization']);

    $response->assertStatus(200)->assertJsonPath('data.visibility', 'organization');

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'visibility' => 'organization',
        'organization_id' => $this->org->id,
    ]);
});

test('dono consegue mudar uma task de organization de volta para pessoal', function () {
    $task = Task::create([
        'user_id' => $this->member_a->id,
        'organization_id' => $this->org->id,
        'visibility' => 'organization',
        'status_id' => $this->status_pending->id,
        'priority_id' => $this->priority_high->id,
        'title' => 'Task que vira pessoal de novo',
    ]);

    $response = withToken($this->token_a)->putJson("/api/v1/tasks/{$task->id}", ['visibility' => 'personal']);

    $response->assertStatus(200)->assertJsonPath('data.visibility', 'personal');

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'visibility' => 'personal',
        'organization_id' => null,
    ]);
});

test('outro membro não consegue mudar o escopo de uma task de organization alheia, mesmo podendo editar o resto', function () {
    $task = Task::create([
        'user_id' => $this->member_a->id,
        'organization_id' => $this->org->id,
        'visibility' => 'organization',
        'status_id' => $this->status_pending->id,
        'priority_id' => $this->priority_high->id,
        'title' => 'Task com escopo protegido',
    ]);

    $response = withToken($this->token_b)->putJson("/api/v1/tasks/{$task->id}", ['visibility' => 'personal']);

    $response->assertStatus(400)->assertJsonPath('success', false);

    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'visibility' => 'organization']);
});

test('admin global consegue transformar uma task de organization alheia em pessoal', function () {
    $task = Task::create([
        'user_id' => $this->member_a->id,
        'organization_id' => $this->org->id,
        'visibility' => 'organization',
        'status_id' => $this->status_pending->id,
        'priority_id' => $this->priority_high->id,
        'title' => 'Task rebaixada pelo global',
    ]);

    $response = withToken($this->token_global)->putJson("/api/v1/tasks/{$task->id}", ['visibility' => 'personal']);

    $response->assertStatus(200)->assertJsonPath('data.visibility', 'personal');

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'visibility' => 'personal',
        'organization_id' => null,
    ]);
});

test('org admin (por permissão admin.organizations.manage-members) consegue transformar task de organization de outro membro da própria organization', function () {
    $task = Task::create([
        'user_id' => $this->member_b->id,
        'organization_id' => $this->org->id,
        'visibility' => 'organization',
        'status_id' => $this->status_pending->id,
        'priority_id' => $this->priority_high->id,
        'title' => 'Task rebaixada pelo org admin',
    ]);

    $response = withToken($this->token_a)->putJson("/api/v1/tasks/{$task->id}", ['visibility' => 'personal']);

    $response->assertStatus(200)->assertJsonPath('data.visibility', 'personal');
});

test('org admin não consegue mudar o escopo de uma task de organization de outra organization', function () {
    $other_org = Organization::create(['id' => (string) Str::uuid(), 'name' => 'Other Task Org', 'slug' => 'other-task-org-'.Str::random(6)]);
    $other_org_member = User::factory()->create([
        'role_id' => $this->user_role->id,
        'active_organization_id' => $other_org->id,
    ]);
    UserOrganization::create(['user_id' => $other_org_member->id, 'organization_id' => $other_org->id, 'role_id' => $this->user_role->id]);

    $task = Task::create([
        'user_id' => $other_org_member->id,
        'organization_id' => $other_org->id,
        'visibility' => 'organization',
        'status_id' => $this->status_pending->id,
        'priority_id' => $this->priority_high->id,
        'title' => 'Task protegida de outra organization',
    ]);

    $response = withToken($this->token_a)->putJson("/api/v1/tasks/{$task->id}", ['visibility' => 'personal']);

    $response->assertStatus(404);
});

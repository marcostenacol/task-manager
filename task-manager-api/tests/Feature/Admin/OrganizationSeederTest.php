<?php

namespace Tests\Feature\Admin;

use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use App\Packages\Task\Priorities\Models\TaskPriority;
use App\Packages\Task\Statuses\Models\TaskStatus;
use App\Packages\Task\Tasks\Models\Task;
use Database\Seeders\OrganizationSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

uses(DatabaseTransactions::class);

test('seeder cria organization root, atribui global_role_id ao owner e membership aos demais', function () {
    $ownerRole = Role::where('slug', 'owner')->first();
    $userRole = Role::where('slug', 'user')->first();

    $owner = User::factory()->create(['role_id' => $ownerRole->id]);
    $normalUser = User::factory()->create(['role_id' => $userRole->id]);

    $status = TaskStatus::first();
    $priority = TaskPriority::first();
    $task = Task::create([
        'user_id' => $normalUser->id,
        'status_id' => $status->id,
        'priority_id' => $priority->id,
        'title' => 'Tarefa de teste',
    ]);

    (new OrganizationSeeder)->run();

    $root = DB::table('admin.organizations')->where('slug', 'root')->first();
    expect($root)->not->toBeNull();
    expect($root->parent_id)->toBeNull();

    $owner->refresh();
    expect($owner->global_role_id)->toBe($ownerRole->id);

    $membership = DB::table('admin.user_organizations')
        ->where('user_id', $normalUser->id)
        ->where('organization_id', $root->id)
        ->first();
    expect($membership)->not->toBeNull();
    expect($membership->role_id)->toBe($userRole->id);

    $task->refresh();
    expect($task->organization_id)->toBe($root->id);
});

test('seeder é idempotente (rodar duas vezes não duplica organization nem membership)', function () {
    $userRole = Role::where('slug', 'user')->first();
    $user = User::factory()->create(['role_id' => $userRole->id]);

    (new OrganizationSeeder)->run();
    (new OrganizationSeeder)->run();

    $rootCount = DB::table('admin.organizations')->where('slug', 'root')->count();
    expect($rootCount)->toBe(1);

    $membershipCount = DB::table('admin.user_organizations')->where('user_id', $user->id)->count();
    expect($membershipCount)->toBe(1);
});

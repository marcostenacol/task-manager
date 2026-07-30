<?php

namespace Tests\Feature\Admin;

use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\RateLimiter;

use function Pest\Laravel\artisan;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withToken;

uses(DatabaseTransactions::class);

test('usuário recém-registrado, sem organization, consegue fundar uma', function () {
    artisan('optimize:clear');

    $userRole = Role::where('slug', 'user')->first();
    $user = User::factory()->create(['role_id' => $userRole->id, 'password' => 'password123']);

    $token = postJson(route('v1.auth.login'), [
        'email' => $user->email,
        'password' => 'password123',
    ])->json('data.access_token.token');

    $response = withToken($token)->postJson('/api/v1/organizations/onboarding', [
        'name' => 'Minha Empresa',
    ]);

    $response->assertStatus(201)->assertJsonPath('success', true);

    $orgId = $response->json('data.id');

    $this->assertDatabaseHas('admin.users', [
        'id' => $user->id,
        'active_organization_id' => $orgId,
    ]);

    $orgAdminRole = Role::where('slug', 'org-admin')->first();
    $this->assertDatabaseHas('admin.user_organizations', [
        'user_id' => $user->id,
        'organization_id' => $orgId,
        'role_id' => $orgAdminRole->id,
    ]);
});

test('usuário que já pertence a uma organization consegue fundar outra', function () {
    artisan('optimize:clear');

    $userRole = Role::where('slug', 'user')->first();
    $user = User::factory()->create(['role_id' => $userRole->id, 'password' => 'password123']);

    $token = postJson(route('v1.auth.login'), [
        'email' => $user->email,
        'password' => 'password123',
    ])->json('data.access_token.token');

    withToken($token)->postJson('/api/v1/organizations/onboarding', ['name' => 'Primeira Empresa'])
        ->assertStatus(201);

    $response = withToken($token)->postJson('/api/v1/organizations/onboarding', ['name' => 'Segunda Empresa']);

    $response->assertStatus(201)->assertJsonPath('success', true);

    $this->assertDatabaseHas('admin.users', [
        'id' => $user->id,
        'active_organization_id' => $response->json('data.id'),
    ]);
});

test('não deve permitir fundar organization além do limite de organizations administradas', function () {
    artisan('optimize:clear');

    $userRole = Role::where('slug', 'user')->first();
    $user = User::factory()->create(['role_id' => $userRole->id, 'password' => 'password123']);

    $token = postJson(route('v1.auth.login'), [
        'email' => $user->email,
        'password' => 'password123',
    ])->json('data.access_token.token');

    $rateLimitKey = md5('organization-onboarding'.$user->id);

    for ($i = 1; $i <= 5; $i++) {
        RateLimiter::clear($rateLimitKey);
        withToken($token)->postJson('/api/v1/organizations/onboarding', ['name' => "Empresa {$i}"])
            ->assertStatus(201);
    }

    RateLimiter::clear($rateLimitKey);

    $response = withToken($token)->postJson('/api/v1/organizations/onboarding', ['name' => 'Empresa 6']);

    $response->assertStatus(400)->assertJsonPath('success', false);
});

test('não deve permitir que usuário com role global faça onboarding', function () {
    artisan('optimize:clear');

    $adminRole = Role::where('slug', 'admin')->first();
    $admin = User::factory()->create(['role_id' => $adminRole->id, 'global_role_id' => $adminRole->id, 'password' => 'password123']);

    $token = postJson(route('v1.auth.login'), [
        'email' => $admin->email,
        'password' => 'password123',
    ])->json('data.access_token.token');

    $response = withToken($token)->postJson('/api/v1/organizations/onboarding', ['name' => 'Empresa Fantasma']);

    $response->assertStatus(400)->assertJsonPath('success', false);
});

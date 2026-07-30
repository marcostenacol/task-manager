<?php

namespace Tests\Feature\Admin;

use App\Packages\Admin\Organizations\Models\Organization;
use App\Packages\Admin\Organizations\Models\UserOrganization;
use App\Packages\Admin\Roles\Models\Role;
use App\Packages\Admin\Users\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;

use function Pest\Laravel\artisan;
use function Pest\Laravel\postJson;
use function Pest\Laravel\withToken;

uses(DatabaseTransactions::class);

beforeEach(function () {
    artisan('optimize:clear');

    $this->orgAdminRole = Role::where('slug', 'org-admin')->first();
    $this->userRole = Role::where('slug', 'user')->first();

    $this->org = Organization::create(['id' => (string) Str::uuid(), 'name' => 'Transfer Org', 'slug' => 'transfer-org-'.Str::random(6)]);

    $this->owner = User::factory()->create([
        'role_id' => $this->orgAdminRole->id,
        'active_organization_id' => $this->org->id,
        'password' => 'password123',
    ]);
    UserOrganization::create(['user_id' => $this->owner->id, 'organization_id' => $this->org->id, 'role_id' => $this->orgAdminRole->id]);

    $this->member = User::factory()->create([
        'role_id' => $this->userRole->id,
        'active_organization_id' => $this->org->id,
        'password' => 'password123',
    ]);
    UserOrganization::create(['user_id' => $this->member->id, 'organization_id' => $this->org->id, 'role_id' => $this->userRole->id]);

    $this->ownerToken = postJson(route('v1.auth.login'), [
        'email' => $this->owner->email,
        'password' => 'password123',
    ])->json('data.access_token.token');
});

test('titular transfere a organization para outro membro e vira user comum', function () {
    $response = withToken($this->ownerToken)->postJson('/api/v1/organizations/transfer-ownership', [
        'new_owner_user_id' => $this->member->id,
    ]);

    $response->assertStatus(200)->assertJsonPath('success', true);

    $this->assertDatabaseHas('admin.user_organizations', [
        'user_id' => $this->member->id,
        'organization_id' => $this->org->id,
        'role_id' => $this->orgAdminRole->id,
    ]);

    $this->assertDatabaseHas('admin.user_organizations', [
        'user_id' => $this->owner->id,
        'organization_id' => $this->org->id,
        'role_id' => $this->userRole->id,
    ]);
});

test('não deve permitir transferir para si mesmo', function () {
    $response = withToken($this->ownerToken)->postJson('/api/v1/organizations/transfer-ownership', [
        'new_owner_user_id' => $this->owner->id,
    ]);

    $response->assertStatus(400)->assertJsonPath('success', false);
});

test('não deve permitir transferir para um usuário que não é membro da organization', function () {
    $outsider = User::factory()->create(['role_id' => $this->userRole->id]);

    $response = withToken($this->ownerToken)->postJson('/api/v1/organizations/transfer-ownership', [
        'new_owner_user_id' => $outsider->id,
    ]);

    $response->assertStatus(404)->assertJsonPath('success', false);
});

test('membro comum não consegue transferir a titularidade da organization', function () {
    $memberToken = postJson(route('v1.auth.login'), [
        'email' => $this->member->email,
        'password' => 'password123',
    ])->json('data.access_token.token');

    $anotherMember = User::factory()->create([
        'role_id' => $this->userRole->id,
        'active_organization_id' => $this->org->id,
    ]);
    UserOrganization::create(['user_id' => $anotherMember->id, 'organization_id' => $this->org->id, 'role_id' => $this->userRole->id]);

    $response = withToken($memberToken)->postJson('/api/v1/organizations/transfer-ownership', [
        'new_owner_user_id' => $anotherMember->id,
    ]);

    $response->assertStatus(403)->assertJsonPath('success', false);
});

<?php

namespace Tests\Feature;

use App\Models\BusinessEntity;
use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthSessionTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        $entity = BusinessEntity::create([
            'name' => 'Test Entity',
            'code' => 'TEST',
            'is_active' => true,
        ]);

        $this->department = Department::create([
            'business_entity_id' => $entity->id,
            'name' => 'Procurement',
            'code' => 'PROC',
            'is_active' => true,
        ]);
    }

    public function test_an_active_user_can_log_in_read_their_session_and_log_out(): void
    {
        Role::create(['name' => 'requester', 'guard_name' => 'web']);

        $user = User::factory()->create([
            'department_id' => $this->department->id,
            'email' => 'requester@example.com',
            'password' => 'password',
            'is_active' => true,
        ]);
        $user->assignRole('requester');

        $this->postJson('/auth/login', [
            'email' => 'requester@example.com',
            'password' => 'password',
            'remember' => true,
        ])->assertOk()
            ->assertJsonPath('data.email', 'requester@example.com')
            ->assertJsonPath('data.roles.0', 'requester');

        $this->assertAuthenticatedAs($user);

        $this->getJson('/auth/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);

        $this->postJson('/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Signed out successfully.');

        $this->assertGuest();
    }

    public function test_inactive_users_cannot_log_in(): void
    {
        User::factory()->create([
            'department_id' => $this->department->id,
            'email' => 'inactive@example.com',
            'password' => 'password',
            'is_active' => false,
        ]);

        $this->postJson('/auth/login', [
            'email' => 'inactive@example.com',
            'password' => 'password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertGuest();
    }

    public function test_demo_role_buttons_list_seeded_accounts_and_create_a_session_without_a_password(): void
    {
        Role::create(['name' => 'gm', 'guard_name' => 'web']);
        $gm = User::factory()->create([
            'name' => 'General Manager',
            'department_id' => $this->department->id,
            'email' => 'gm@hq.test',
            'is_active' => true,
        ]);
        $gm->assignRole('gm');

        $this->getJson('/auth/demo-users')
            ->assertOk()
            ->assertJsonPath('data.0.key', 'gm')
            ->assertJsonPath('data.0.label', 'General Manager')
            ->assertJsonMissingPath('data.0.email')
            ->assertJsonMissingPath('data.0.password');

        $this->postJson('/auth/demo-login', ['account' => 'gm'])
            ->assertOk()
            ->assertJsonPath('data.id', $gm->id)
            ->assertJsonPath('data.roles.0', 'gm');

        $this->assertAuthenticatedAs($gm);
    }

    public function test_demo_login_rejects_unknown_account_keys(): void
    {
        $this->postJson('/auth/demo-login', ['account' => 'administrator'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('account');
    }
}

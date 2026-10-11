<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admins_can_access_account_management(): void
    {
        $this->getJson('/api/admin/users')->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/admin/users')->assertForbidden();
    }

    public function test_admin_can_filter_accounts_by_search_role_and_status(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $target = User::factory()->create([
            'name' => 'Lan Nguyen',
            'email' => 'lan.nguyen@example.com',
            'is_active' => false,
        ]);
        User::factory()->create(['name' => 'Lan Active', 'is_active' => true]);
        User::factory()->admin()->create(['name' => 'Other Admin']);

        $this->getJson('/api/admin/users?search=lan.nguyen&role=customer&status=inactive')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $target->id)
            ->assertJsonPath('data.0.is_active', false)
            ->assertJsonPath('data.0.orders_count', 0);
    }

    public function test_admin_can_update_account_details_and_role(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $customer = User::factory()->create([
            'name' => 'Customer One',
            'email' => 'customer.one@example.com',
        ]);

        $this->putJson("/api/admin/users/{$customer->id}", [
            'name' => 'Updated Customer',
            'email' => 'updated.customer@example.com',
            'role' => UserRole::Admin->value,
            'is_active' => true,
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Customer')
            ->assertJsonPath('data.email', 'updated.customer@example.com')
            ->assertJsonPath('data.role', UserRole::Admin->value)
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('users', [
            'id' => $customer->id,
            'role' => UserRole::Admin->value,
            'email' => 'updated.customer@example.com',
        ]);
    }

    public function test_deactivating_account_revokes_tokens_and_blocks_login(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $customer = User::factory()->create([
            'email' => 'inactive.customer@example.com',
            'password' => 'password123',
        ]);
        $token = $customer->createToken('auth')->plainTextToken;

        $this->putJson("/api/admin/users/{$customer->id}", [
            'name' => $customer->name,
            'email' => $customer->email,
            'role' => UserRole::Customer->value,
            'is_active' => false,
        ])->assertOk()->assertJsonPath('data.is_active', false);

        $this->assertNull(PersonalAccessToken::findToken($token));
        $this->assertDatabaseHas('users', ['id' => $customer->id, 'is_active' => false]);

        $this->postJson('/api/auth/login', [
            'email' => $customer->email,
            'password' => 'password123',
        ])->assertUnprocessable()->assertJsonValidationErrors(['email']);
    }

    public function test_admin_cannot_demote_or_deactivate_their_own_account(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->putJson("/api/admin/users/{$admin->id}", [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => UserRole::Customer->value,
            'is_active' => false,
        ])->assertUnprocessable()->assertJsonValidationErrors(['role']);

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => UserRole::Admin->value,
            'is_active' => true,
        ]);
    }

    public function test_inactive_account_cannot_use_authenticated_routes(): void
    {
        $inactiveUser = User::factory()->create(['is_active' => false]);
        Sanctum::actingAs($inactiveUser);

        $this->getJson('/api/preferences')->assertUnauthorized();
        $this->postJson('/api/orders')->assertUnauthorized();
    }

    public function test_admin_cannot_assign_an_existing_email_to_another_account(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $first = User::factory()->create(['email' => 'first@example.com']);
        $second = User::factory()->create(['email' => 'second@example.com']);

        $this->putJson("/api/admin/users/{$second->id}", [
            'name' => $second->name,
            'email' => $first->email,
            'role' => UserRole::Customer->value,
            'is_active' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors(['email']);
    }
}

<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Nguyễn Văn A',
            'email' => 'vana@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.email', 'vana@example.com')
            ->assertJsonPath('data.role', UserRole::Customer->value)
            ->assertJsonStructure(['data' => ['id', 'name', 'email', 'role'], 'token']);

        $this->assertDatabaseHas('users', [
            'email' => 'vana@example.com',
            'role' => UserRole::Customer->value,
        ]);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'vana@example.com']);

        $this->postJson('/api/auth/register', [
            'name' => 'Người khác',
            'email' => 'vana@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertUnprocessable();
    }

    public function test_user_can_login(): void
    {
        User::factory()->create([
            'email' => 'vana@example.com',
            'password' => 'password123',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'vana@example.com',
            'password' => 'password123',
        ])
            ->assertOk()
            ->assertJsonPath('data.email', 'vana@example.com')
            ->assertJsonStructure(['data', 'token']);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'vana@example.com',
            'password' => 'password123',
        ]);

        $this->postJson('/api/auth/login', [
            'email' => 'vana@example.com',
            'password' => 'sai-mat-khau',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_authenticated_user_can_view_profile_and_logout(): void
    {
        $user = User::factory()->create(['password' => 'password123']);

        $token = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertOk()->json('token');

        $this->withToken($token)->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();

        $this->assertNull(PersonalAccessToken::findToken($token));

        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/auth/me')->assertUnauthorized();
    }
}

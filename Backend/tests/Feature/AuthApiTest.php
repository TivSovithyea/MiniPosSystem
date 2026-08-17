<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_and_access_profile(): void
    {
        User::factory()->create(['email' => 'admin@example.com', 'password' => 'secret123']);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'secret123',
        ])->assertOk()->assertJsonPath('user.email', 'admin@example.com');

        $this->withToken($response->json('token'))
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('email', 'admin@example.com');
    }

    public function test_protected_routes_reject_guests(): void
    {
        $this->getJson('/api/products')->assertUnauthorized();
    }
}

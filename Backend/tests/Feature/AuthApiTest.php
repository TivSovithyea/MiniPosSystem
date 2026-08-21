<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'admin@example.com', 'password' => 'secret123']);

        $this->postJson('/api/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_second_login_requires_confirmation_then_revokes_previous_session(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);
        $credentials = ['email' => $user->email, 'password' => 'secret123'];
        $firstToken = $this->postJson('/api/auth/login', $credentials)->assertOk()->json('token');
        $this->postJson('/api/auth/login', $credentials)->assertConflict()
            ->assertJsonPath('code', 'SESSION_CONFLICT');
        $secondToken = $this->postJson('/api/auth/login', [...$credentials, 'force_session' => true])
            ->assertOk()->json('token');

        $this->assertDatabaseCount('personal_access_tokens', 1);
        $this->app['auth']->forgetGuards();
        $this->withToken($firstToken)->getJson('/api/auth/me')->assertUnauthorized();
        $this->app['auth']->forgetGuards();
        $this->withToken($secondToken)->getJson('/api/auth/me')->assertOk()
            ->assertJsonPath('email', $user->email);
    }

    public function test_user_can_logout_and_token_is_revoked(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);
        $token = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'secret123',
        ])->assertOk()->json('token');

        $this->withToken($token)->postJson('/api/auth/logout')
            ->assertOk()->assertJsonPath('message', 'Logged out successfully.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_keycloak_sso_redirect_and_callback_create_a_session(): void
    {
        config([
            'services.keycloak.enabled' => true,
            'services.keycloak.base_url' => 'http://keycloak.test',
            'services.keycloak.public_url' => 'http://keycloak.test',
            'services.keycloak.realm' => 'minipos',
            'services.keycloak.client_id' => 'minipos-web',
            'services.keycloak.client_secret' => 'secret',
            'services.keycloak.redirect_uri' => 'http://localhost/api/auth/keycloak/callback',
            'services.keycloak.frontend_callback' => 'http://localhost:5173/auth/sso/callback',
        ]);
        Http::fake([
            'http://keycloak.test/realms/minipos/protocol/openid-connect/token' => Http::response(['access_token' => 'keycloak-access-token']),
            'http://keycloak.test/realms/minipos/protocol/openid-connect/userinfo' => Http::response([
                'sub' => 'keycloak-user-1', 'email' => 'sso@minipos.test',
                'email_verified' => true, 'name' => 'SSO User',
            ]),
        ]);

        $redirect = $this->get('/api/auth/keycloak/redirect')->assertRedirect();
        parse_str(parse_url($redirect->headers->get('Location'), PHP_URL_QUERY), $query);

        $callback = $this->get('/api/auth/keycloak/callback?'.http_build_query([
            'code' => 'authorization-code', 'state' => $query['state'],
        ]))->assertRedirect();

        $this->assertStringStartsWith('http://localhost:5173/auth/sso/callback#token=', $callback->headers->get('Location'));
        $this->assertDatabaseHas('users', ['email' => 'sso@minipos.test', 'keycloak_id' => 'keycloak-user-1']);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }
}

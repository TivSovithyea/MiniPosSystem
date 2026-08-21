<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'force_session' => ['sometimes', 'boolean'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'The provided credentials are incorrect.']);
        }

        if (! $request->boolean('force_session') && $this->hasActiveSession($user)) {
            return response()->json([
                'message' => 'This user is currently signed in on another device. Do you want to continue and disconnect that session?',
                'code' => 'SESSION_CONFLICT',
            ], 409);
        }

        return response()->json([
            'user' => $user,
            'token' => $this->createSingleSessionToken($user, 'minipos-web'),
        ]);
    }

    public function keycloakRedirect()
    {
        abort_unless(config('services.keycloak.enabled'), 404);
        $state = Str::random(64);
        Cache::put('keycloak-state:'.$state, true, now()->addMinutes(10));
        $realmUrl = config('services.keycloak.public_url').'/realms/'.config('services.keycloak.realm');

        return redirect()->away($realmUrl.'/protocol/openid-connect/auth?'.http_build_query([
            'client_id' => config('services.keycloak.client_id'),
            'redirect_uri' => config('services.keycloak.redirect_uri'),
            'response_type' => 'code',
            'scope' => 'openid profile email',
            'state' => $state,
        ]));
    }

    public function keycloakCallback(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string'], 'state' => ['required', 'string']]);
        abort_unless(Cache::pull('keycloak-state:'.$data['state']), 403, 'Invalid or expired SSO state.');
        $realmUrl = config('services.keycloak.base_url').'/realms/'.config('services.keycloak.realm');
        $tokens = Http::asForm()->post($realmUrl.'/protocol/openid-connect/token', [
            'grant_type' => 'authorization_code',
            'client_id' => config('services.keycloak.client_id'),
            'client_secret' => config('services.keycloak.client_secret'),
            'redirect_uri' => config('services.keycloak.redirect_uri'),
            'code' => $data['code'],
        ])->throw()->json();
        $profile = Http::withToken($tokens['access_token'])->get($realmUrl.'/protocol/openid-connect/userinfo')->throw()->json();
        abort_unless(($profile['email_verified'] ?? false) && filter_var($profile['email'] ?? null, FILTER_VALIDATE_EMAIL), 422, 'Keycloak must provide a verified email.');

        $user = User::firstOrNew(['email' => $profile['email']]);
        $user->fill(['keycloak_id' => $profile['sub'], 'name' => $profile['name'] ?? $profile['preferred_username'] ?? $profile['email']]);
        if (! $user->exists) {
            $user->password = Hash::make(Str::random(40));
        }
        $user->save();
        if ($this->hasActiveSession($user)) {
            $challenge = Str::random(64);
            Cache::put('session-takeover:'.$challenge, $user->id, now()->addMinutes(5));

            return redirect()->away(config('services.keycloak.frontend_callback').'#session_conflict='.rawurlencode($challenge));
        }
        $token = $this->createSingleSessionToken($user, 'keycloak-sso');

        return redirect()->away(config('services.keycloak.frontend_callback').'#token='.rawurlencode($token));
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function continueSession(Request $request): JsonResponse
    {
        $data = $request->validate(['challenge' => ['required', 'string']]);
        $userId = Cache::pull('session-takeover:'.$data['challenge']);
        abort_unless($userId, 403, 'Invalid or expired session confirmation.');
        $user = User::findOrFail($userId);

        return response()->json([
            'user' => $user,
            'token' => $this->createSingleSessionToken($user, 'keycloak-sso'),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    private function createSingleSessionToken(User $user, string $name): string
    {
        return DB::transaction(function () use ($user, $name) {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $lockedUser->tokens()->delete();

            return $lockedUser->createToken($name)->plainTextToken;
        });
    }

    private function hasActiveSession(User $user): bool
    {
        $activeSince = now()->subMinutes((int) config('sanctum.active_session_minutes', 15));

        return $user->tokens()->where(function ($query) use ($activeSince) {
            $query->where('last_used_at', '>=', $activeSince)
                ->orWhere(function ($inner) use ($activeSince) {
                    $inner->whereNull('last_used_at')->where('created_at', '>=', $activeSince);
                });
        })->exists();
    }
}

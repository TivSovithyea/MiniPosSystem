<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->when($request->filled('search'), fn ($query) => $query->where(fn ($inner) => $inner
                ->where('name', 'like', '%'.$request->string('search').'%')
                ->orWhere('email', 'like', '%'.$request->string('search').'%')))
            ->latest()
            ->paginate(min(max($request->integer('per_page', 20), 1), 100));

        return response()->json($users);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(User::create($this->validated($request)), 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json($user);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $user->update($this->validated($request, $user));

        return response()->json($user);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()->is($user)) {
            return response()->json(['message' => 'You cannot delete your own account.'], 409);
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(null, 204);
    }

    private function validated(Request $request, ?User $user = null): array
    {
        $data = $request->validate([
            'name' => [$user ? 'sometimes' : 'required', 'string', 'max:255'],
            'email' => [$user ? 'sometimes' : 'required', 'email', 'max:255', Rule::unique('users')->ignore($user)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'max:255'],
        ]);

        if ($user && empty($data['password'])) {
            unset($data['password']);
        }

        return $data;
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
        ]);

        $role = Role::query()
            ->where('name', $request->string('role')->toString())
            ->firstOrFail();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'role_id' => $role->id,
        ]);

        $scopes = $this->resolveScopesByRole($role->name);

        $token = $user->createToken('auth_token', $scopes)->accessToken;

        return response()->json([
            'message' => 'Usuario registrado correctamente',
            'user' => $user->load('role'),
            'token' => $token,
            'scopes' => $scopes,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::with('role')->where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales son incorrectas.'],
            ]);
        }

        $scopes = $this->resolveScopesByRole($user->role?->name);

        $token = $user->createToken('auth_token', $scopes)->accessToken;

        return response()->json([
            'message' => 'Inicio de sesion correcto',
            'user' => $user,
            'token' => $token,
            'scopes' => $scopes,
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user()->load('role'),
        ]);
    }

    private function resolveScopesByRole(?string $role): array
    {
        return match ($role) {
            'admin' => ['users:read', 'users:create'],
            'viewer' => ['users:read'],
            default => [],
        };
    }
}
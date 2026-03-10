<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiTokenController extends Controller
{
    use ApiResponse;

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return $this->validationErrorResponse(
                ['email' => ['The provided credentials are incorrect.']],
                'The provided credentials are incorrect.',
            );
        }

        $abilities = $user->getAllPermissions()->pluck('name')->values()->all();
        $token = $user->createToken($validated['device_name'], $abilities)->plainTextToken;

        return $this->createdResponse(
            [
                'token' => $token,
                'token_type' => 'Bearer',
                'abilities' => $abilities,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ],
            'Token created',
        );
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->successResponse([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'permissions' => $user->getAllPermissions()->pluck('name')->values(),
            'token' => $request->user()->currentAccessToken()
                ? [
                    'name' => $request->user()->currentAccessToken()->name,
                    'abilities' => $request->user()->currentAccessToken()->abilities,
                    'last_used_at' => $request->user()->currentAccessToken()->last_used_at,
                    'expires_at' => $request->user()->currentAccessToken()->expires_at,
                ]
                : null,
        ]);
    }

    public function destroyCurrent(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->successResponse(message: 'Token revoked');
    }
}

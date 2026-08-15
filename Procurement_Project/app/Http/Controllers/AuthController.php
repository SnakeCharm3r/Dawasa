<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function csrf(Request $request): JsonResponse
    {
        return response()->json(['token' => csrf_token()]);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        if (! Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
            'is_active' => true,
        ], (bool) ($credentials['remember'] ?? false))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect or the account is inactive.'],
            ]);
        }

        $request->session()->regenerate();

        return response()->json(['data' => $this->userPayload($request->user())]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->userPayload($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Signed out successfully.']);
    }

    private function userPayload(User $user): array
    {
        $user->loadMissing(['roles', 'department.businessEntity']);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'job_title' => $user->job_title,
            'is_line_manager' => $user->is_line_manager,
            'roles' => $user->getRoleNames()->values(),
            'department' => $user->department ? [
                'id' => $user->department->id,
                'name' => $user->department->name,
                'business_entity' => $user->department->businessEntity ? [
                    'id' => $user->department->businessEntity->id,
                    'name' => $user->department->businessEntity->name,
                ] : null,
            ] : null,
        ];
    }
}

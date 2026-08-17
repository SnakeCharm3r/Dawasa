<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const DEMO_ACCOUNTS = [
        'requester' => ['email' => 'requester@hq.test', 'label' => 'Requester', 'description' => 'Create and track department requisitions'],
        'line_manager' => ['email' => 'line_manager@hq.test', 'label' => 'Line Manager · Operations', 'description' => 'Approve Operations requests from Rehema'],
        'gm' => ['email' => 'gm@hq.test', 'label' => 'General Manager', 'description' => 'Final requisition and award approval'],
        'ceo' => ['email' => 'ceo@hq.test', 'label' => 'CEO', 'description' => 'Full system oversight and every report'],
        'procurement' => ['email' => 'procurement@hq.test', 'label' => 'Procurement', 'description' => 'Sourcing, tenders and purchase orders'],
    ];

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

    public function demoUsers(): JsonResponse
    {
        $this->ensureDemoEnvironment();
        $users = User::query()->whereIn('email', array_column(self::DEMO_ACCOUNTS, 'email'))->where('is_active', true)->get()->keyBy('email');

        return response()->json(['data' => collect(self::DEMO_ACCOUNTS)->map(function (array $account, string $key) use ($users) {
            $user = $users->get($account['email']);

            return $user ? [
                'key' => $key,
                'label' => $account['label'],
                'description' => $account['description'],
                'name' => $user->name,
            ] : null;
        })->filter()->values()]);
    }

    public function demoLogin(Request $request): JsonResponse
    {
        $this->ensureDemoEnvironment();
        $data = $request->validate(['account' => ['required', 'string', 'in:'.implode(',', array_keys(self::DEMO_ACCOUNTS))]]);
        $user = User::query()->where('email', self::DEMO_ACCOUNTS[$data['account']]['email'])->where('is_active', true)->firstOrFail();

        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['data' => $this->userPayload($user)]);
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
        $user->loadMissing(['roles', 'department.businessEntity', 'supplier:id,user_id,application_reference,portal_status,name']);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at?->toDateTimeString(),
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
            'supplier' => $user->supplier ? [
                'id' => $user->supplier->id,
                'name' => $user->supplier->name,
                'application_reference' => $user->supplier->application_reference,
                'status' => $user->supplier->portal_status,
            ] : null,
        ];
    }

    private function ensureDemoEnvironment(): void
    {
        abort_unless(app()->environment('local', 'testing'), 404);
    }
}

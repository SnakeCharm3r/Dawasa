<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $query = User::with(['roles', 'department.businessEntity', 'lineManager'])->withCount('directReports');

        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('middle_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('job_title', 'like', "%{$search}%");

                if (is_numeric($search)) {
                    $query->orWhere('id', $search);
                }
            });
        }

        if ($request->filled('entity_id')) {
            $query->whereHas('department.businessEntity', fn ($query) => $query->where('id', $request->input('entity_id')));
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $request->input('role')));
        }

        if ($request->has('is_line_manager')) {
            $query->where('is_line_manager', $request->boolean('is_line_manager'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $users = $query->paginate($request->input('per_page', 15));

        return response()->json($users);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        if ($data['is_line_manager']) {
            $data['line_manager_id'] = null;
        }

        $data['name'] = trim($data['first_name'].' '.($data['middle_name'] ? $data['middle_name'].' ' : '').$data['last_name']);
        $data['password'] = Hash::make(Str::random(16));

        $user = null;
        DB::transaction(function () use ($data, &$user) {
            $user = User::create($data);
            $user->syncRoles($data['roles']);
            ActivityLog::record(auth()->user(), 'user.created', $user, [], $user->only(['first_name', 'middle_name', 'last_name', 'department_id', 'line_manager_id', 'job_title', 'is_line_manager', 'email', 'phone', 'is_active']));
        });

        return response()->json([
            'message' => 'User created successfully.',
            'data' => $user,
        ], 201);
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        $user->load(['department.businessEntity', 'roles', 'lineManager', 'directReports']);

        return response()->json($user);
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $data = $request->validated();
        if ($data['is_line_manager']) {
            $data['line_manager_id'] = null;
        }

        $data['name'] = trim($data['first_name'].' '.($data['middle_name'] ? $data['middle_name'].' ' : '').$data['last_name']);

        $oldValues = $user->only(['first_name', 'middle_name', 'last_name', 'department_id', 'line_manager_id', 'job_title', 'is_line_manager', 'email', 'phone', 'is_active']);

        DB::transaction(function () use ($user, $data, $oldValues) {
            $user->update($data);
            $user->syncRoles($data['roles']);
            ActivityLog::record(auth()->user(), 'user.updated', $user, $oldValues, $user->only(['first_name', 'middle_name', 'last_name', 'department_id', 'line_manager_id', 'job_title', 'is_line_manager', 'email', 'phone', 'is_active']));
        });

        return response()->json(['message' => 'User updated successfully.', 'data' => $user]);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        return response()->json([
            'message' => 'Users cannot be deleted. Deactivate the account instead to maintain audit integrity.',
        ], 405);
    }

    public function activate(User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $oldValues = ['is_active' => $user->is_active];
        $user->update(['is_active' => true]);
        ActivityLog::record(auth()->user(), 'user.activated', $user, $oldValues, ['is_active' => true]);

        return response()->json(['message' => 'User activated.', 'data' => $user]);
    }

    public function deactivate(User $user): JsonResponse
    {
        $this->authorize('update', $user);

        if (auth()->id() === $user->id) {
            return response()->json(['message' => 'You cannot deactivate your own account.'], 403);
        }

        $oldValues = ['is_active' => $user->is_active];
        $user->update(['is_active' => false]);
        ActivityLog::record(auth()->user(), 'user.deactivated', $user, $oldValues, ['is_active' => false]);

        return response()->json(['message' => 'User deactivated.', 'data' => $user]);
    }
}

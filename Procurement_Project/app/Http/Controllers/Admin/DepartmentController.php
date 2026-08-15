<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\ActivityLog;
use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Department::class);

        $query = Department::with('businessEntity')->withCount('users');

        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('business_entity_id')) {
            $query->where('business_entity_id', $request->input('business_entity_id'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $departments = $query->paginate($request->input('per_page', 15));

        return response()->json($departments);
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $data = $request->validated();

        $department = null;
        DB::transaction(function () use ($data, &$department) {
            $department = Department::create($data);
            ActivityLog::record(auth()->user(), 'department.created', $department, [], $department->toArray());
        });

        return response()->json([
            'message' => 'Department created successfully.',
            'data' => $department,
        ], 201);
    }

    public function show(Department $department): JsonResponse
    {
        $this->authorize('view', $department);

        $department->load(['businessEntity', 'users.roles', 'users.lineManager']);
        $lineManagers = $department->users()->where('is_line_manager', true)->get();

        return response()->json([
            'department' => $department,
            'line_managers' => $lineManagers,
        ]);
    }

    public function update(UpdateDepartmentRequest $request, Department $department): JsonResponse
    {
        $this->authorize('update', $department);

        $data = $request->validated();

        if ($department->business_entity_id !== $data['business_entity_id'] && $department->users()->where('is_active', true)->exists()) {
            return response()->json([
                'message' => 'Active users must be transferred before moving this department to another business entity.',
            ], 422);
        }

        $oldValues = $department->only(['business_entity_id', 'name', 'code', 'is_active']);

        DB::transaction(function () use ($department, $data, $oldValues) {
            $department->update($data);
            ActivityLog::record(auth()->user(), 'department.updated', $department, $oldValues, $department->only(['business_entity_id', 'name', 'code', 'is_active']));
        });

        return response()->json([
            'message' => 'Department updated successfully.',
            'data' => $department,
        ]);
    }

    public function destroy(Department $department): JsonResponse
    {
        $this->authorize('delete', $department);

        if ($department->users()->exists()) {
            ActivityLog::record(auth()->user(), 'department.delete_failed', $department, $department->toArray(), []);

            return response()->json([
                'message' => 'This department cannot be deleted while users still belong to it. Deactivate it instead.',
            ], 422);
        }

        $oldValues = $department->toArray();

        DB::transaction(function () use ($department, $oldValues) {
            ActivityLog::record(auth()->user(), 'department.deleted', $department, $oldValues, []);
            $department->delete();
        });

        return response()->json(['message' => 'Department deleted successfully.']);
    }

    public function activate(Department $department): JsonResponse
    {
        $this->authorize('update', $department);

        $oldValues = ['is_active' => $department->is_active];
        $department->update(['is_active' => true]);
        ActivityLog::record(auth()->user(), 'department.activated', $department, $oldValues, ['is_active' => true]);

        return response()->json(['message' => 'Department activated.', 'data' => $department]);
    }

    public function deactivate(Department $department): JsonResponse
    {
        $this->authorize('update', $department);

        $oldValues = ['is_active' => $department->is_active];
        $department->update(['is_active' => false]);
        ActivityLog::record(auth()->user(), 'department.deactivated', $department, $oldValues, ['is_active' => false]);

        return response()->json(['message' => 'Department deactivated.', 'data' => $department]);
    }
}

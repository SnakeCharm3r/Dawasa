<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBusinessEntityRequest;
use App\Http\Requests\UpdateBusinessEntityRequest;
use App\Models\ActivityLog;
use App\Models\BusinessEntity;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BusinessEntityController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', BusinessEntity::class);

        $query = BusinessEntity::query();

        if ($search = trim($request->input('search', ''))) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $entities = $query->withCount('departments')->paginate($request->input('per_page', 15));

        return response()->json($entities);
    }

    public function store(StoreBusinessEntityRequest $request): JsonResponse
    {
        $data = $request->validated();

        $entity = null;
        DB::transaction(function () use ($data, &$entity) {
            $entity = BusinessEntity::create($data);
            ActivityLog::record(auth()->user(), 'business_entity.created', $entity, [], $entity->toArray());
        });

        return response()->json([
            'message' => 'Business entity created successfully.',
            'data' => $entity,
        ], 201);
    }

    public function show(BusinessEntity $businessEntity): JsonResponse
    {
        $this->authorize('view', $businessEntity);

        $businessEntity->load(['departments' => function ($query) {
            $query->withCount('users');
        }]);

        return response()->json($businessEntity);
    }

    public function update(UpdateBusinessEntityRequest $request, BusinessEntity $businessEntity): JsonResponse
    {
        $data = $request->validated();
        $this->authorize('update', $businessEntity);

        $oldValues = $businessEntity->only(['name', 'code', 'is_active']);

        DB::transaction(function () use ($businessEntity, $data, $oldValues) {
            $businessEntity->update($data);
            ActivityLog::record(auth()->user(), 'business_entity.updated', $businessEntity, $oldValues, $businessEntity->only(['name', 'code', 'is_active']));
        });

        return response()->json([
            'message' => 'Business entity updated successfully.',
            'data' => $businessEntity,
        ]);
    }

    public function destroy(BusinessEntity $businessEntity): JsonResponse
    {
        $this->authorize('delete', $businessEntity);

        if ($businessEntity->departments()->exists()) {
            return response()->json([
                'message' => 'The entity cannot be deleted while it still has departments. Deactivate it instead or remove departments first.',
            ], 422);
        }

        $oldValues = $businessEntity->toArray();

        DB::transaction(function () use ($businessEntity, $oldValues) {
            ActivityLog::record(auth()->user(), 'business_entity.deleted', $businessEntity, $oldValues, []);
            $businessEntity->delete();
        });

        return response()->json(['message' => 'Business entity deleted successfully.']);
    }

    public function activate(BusinessEntity $businessEntity): JsonResponse
    {
        $this->authorize('update', $businessEntity);

        $oldValues = ['is_active' => $businessEntity->is_active];
        $businessEntity->update(['is_active' => true]);
        ActivityLog::record(auth()->user(), 'business_entity.activated', $businessEntity, $oldValues, ['is_active' => true]);

        return response()->json(['message' => 'Business entity activated.', 'data' => $businessEntity]);
    }

    public function deactivate(BusinessEntity $businessEntity): JsonResponse
    {
        $this->authorize('update', $businessEntity);

        $oldValues = ['is_active' => $businessEntity->is_active];
        $businessEntity->update(['is_active' => false]);
        ActivityLog::record(auth()->user(), 'business_entity.deactivated', $businessEntity, $oldValues, ['is_active' => false]);

        return response()->json(['message' => 'Business entity deactivated.', 'data' => $businessEntity]);
    }
}

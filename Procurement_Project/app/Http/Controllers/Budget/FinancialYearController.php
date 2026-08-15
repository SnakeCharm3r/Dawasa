<?php

namespace App\Http\Controllers\Budget;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFinancialYearRequest;
use App\Http\Requests\UpdateFinancialYearRequest;
use App\Models\ActivityLog;
use App\Models\FinancialYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinancialYearController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', FinancialYear::class);

        $query = FinancialYear::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->input('search').'%');
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $years = $query->orderBy('start_date', 'desc')->paginate($request->input('per_page', 15));

        return response()->json($years);
    }

    public function store(StoreFinancialYearRequest $request): JsonResponse
    {
        $this->authorize('create', FinancialYear::class);

        $data = $request->validated();

        $year = null;
        DB::transaction(function () use ($data, &$year) {
            $year = FinancialYear::create($data);
            ActivityLog::record(Auth::user(), 'financial_year.created', $year, [], $year->toArray());
        });

        return response()->json(['message' => 'Financial year created successfully.', 'data' => $year], 201);
    }

    public function show(FinancialYear $financialYear): JsonResponse
    {
        $this->authorize('view', $financialYear);

        return response()->json($financialYear);
    }

    public function update(UpdateFinancialYearRequest $request, FinancialYear $financialYear): JsonResponse
    {
        $this->authorize('update', $financialYear);

        $data = $request->validated();

        if (($data['is_active'] ?? false) && ! $financialYear->is_active) {
            $activeYearExists = FinancialYear::query()
                ->where('is_active', true)
                ->where('id', '<>', $financialYear->id)
                ->exists();

            if ($activeYearExists) {
                return response()->json(['message' => 'Another financial year is already active. Deactivate or close it before activating this one.'], 422);
            }
        }

        $oldValues = $financialYear->only(['name', 'start_date', 'end_date', 'is_active']);

        DB::transaction(function () use ($financialYear, $data, $oldValues) {
            $financialYear->update($data);
            ActivityLog::record(Auth::user(), 'financial_year.updated', $financialYear, $oldValues, $financialYear->only(['name', 'start_date', 'end_date', 'is_active']));
        });

        return response()->json(['message' => 'Financial year updated successfully.', 'data' => $financialYear]);
    }

    public function activate(FinancialYear $financialYear): JsonResponse
    {
        $this->authorize('update', $financialYear);

        if ($financialYear->is_active) {
            return response()->json(['message' => 'Financial year is already active.', 'data' => $financialYear]);
        }

        $activeYearExists = FinancialYear::query()
            ->where('is_active', true)
            ->where('id', '<>', $financialYear->id)
            ->exists();

        if ($activeYearExists) {
            return response()->json(['message' => 'Another financial year is already active. Deactivate or close it before activating this one.'], 422);
        }

        DB::transaction(function () use ($financialYear) {
            $oldValues = ['is_active' => $financialYear->is_active];
            $financialYear->update(['is_active' => true]);
            ActivityLog::record(Auth::user(), 'financial_year.activated', $financialYear, $oldValues, ['is_active' => true]);
        });

        return response()->json(['message' => 'Financial year activated.', 'data' => $financialYear]);
    }

    public function destroy(FinancialYear $financialYear): JsonResponse
    {
        return response()->json(['message' => 'Financial years cannot be permanently deleted from this interface.'], 405);
    }
}

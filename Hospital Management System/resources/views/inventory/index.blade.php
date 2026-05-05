@extends('layouts.app')

@section('title', 'Inventory - Hospital Management System')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-2">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                            <li class="breadcrumb-item active">Inventory</li>
                        </ol>
                    </nav>
                    <h1 class="h3 mb-1">Inventory</h1>
                    <p class="text-muted mb-0">Manage medical supplies and equipment</p>
                </div>
                @can('inventory.create')
                <a href="{{ route('inventory.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-2"></i>Add Item
                </a>
                @endcan
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        @can('inventory.view_low_stock')
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-danger bg-opacity-10 rounded-3 p-3">
                                <i class="ti ti-alert-triangle text-danger fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Low Stock Items</h6>
                            <h3 class="mb-0">{{ $lowStockCount ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endcan

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 rounded-3 p-3">
                                <i class="ti ti-package text-success fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Items</h6>
                            <h3 class="mb-0">{{ $totalItems ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 rounded-3 p-3">
                                <i class="ti ti-truck text-info fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Pending Orders</h6>
                            <h3 class="mb-0">{{ $pendingOrders ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 rounded-3 p-3">
                                <i class="ti ti-currency-dollar text-warning fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Inventory Value</h6>
                            <h3 class="mb-0">{{ $inventoryValue ?? '$0.00' }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('inventory.index') }}" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search by name or ID..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <option value="ppe" {{ request('category') == 'ppe' ? 'selected' : '' }}>PPE</option>
                        <option value="equipment" {{ request('category') == 'equipment' ? 'selected' : '' }}>Equipment</option>
                        <option value="medication" {{ request('category') == 'medication' ? 'selected' : '' }}>Medication</option>
                        <option value="supplies" {{ request('category') == 'supplies' ? 'selected' : '' }}>Supplies</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="in_stock" {{ request('status') == 'in_stock' ? 'selected' : '' }}>In Stock</option>
                        <option value="low_stock" {{ request('status') == 'low_stock' ? 'selected' : '' }}>Low Stock</option>
                        <option value="out_of_stock" {{ request('status') == 'out_of_stock' ? 'selected' : '' }}>Out of Stock</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="ti ti-search me-2"></i>Search
                    </button>
                    <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-refresh me-2"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Inventory Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Item ID</th>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($inventory ?? [] as $item)
                        <tr>
                            <td><strong>#{{ $item->id ?? 'INV' . str_pad($loop->iteration, 4, '0', STR_PAD_LEFT) }}</strong></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="bg-primary bg-opacity-10 rounded d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                        <i class="ti ti-package text-primary"></i>
                                    </div>
                                    <span>{{ $item->name ?? 'Item Name' }}</span>
                                </div>
                            </td>
                            <td>{{ $item->category ?? 'General' }}</td>
                            <td>{{ $item->quantity ?? 0 }} {{ $item->unit ?? 'units' }}</td>
                            <td>{{ $item->unit_price ?? '$0.00' }}</td>
                            <td>
                                <span class="badge bg-{{ $item->status_color ?? 'success' }}-subtle text-{{ $item->status_color ?? 'success' }} border border-{{ $item->status_color ?? 'success' }}">
                                    {{ $item->status ?? 'In Stock' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @can('inventory.view')
                                        <li><a class="dropdown-item" href="{{ route('inventory.show', $item->id) }}">
                                            <i class="ti ti-eye me-2"></i>View
                                        </a></li>
                                        @endcan
                                        @can('inventory.edit')
                                        <li><a class="dropdown-item" href="{{ route('inventory.edit', $item->id) }}">
                                            <i class="ti ti-edit me-2"></i>Edit
                                        </a></li>
                                        @endcan
                                        @can('inventory.adjust_stock')
                                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#adjustStockModal{{ $item->id }}">
                                            <i class="ti ti-adjustments me-2"></i>Adjust Stock
                                        </a></li>
                                        @endcan
                                        @can('inventory.delete')
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" action="{{ route('inventory.destroy', $item->id) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="ti ti-trash me-2"></i>Delete
                                                </button>
                                            </form>
                                        </li>
                                        @endcan
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="ti ti-package fs-1 mb-2 d-block"></i>
                                    <p class="mb-0">No inventory items found</p>
                                    @can('inventory.create')
                                    <a href="{{ route('inventory.create') }}" class="btn btn-primary btn-sm mt-2">
                                        <i class="ti ti-plus me-2"></i>Add First Item
                                    </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if(isset($inventory) && $inventory->hasPages())
        <div class="card-footer">
            {{ $inventory->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

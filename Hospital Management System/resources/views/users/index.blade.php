@extends('layouts.app')

@section('title', 'User Management - Hospital Management System')

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
                            <li class="breadcrumb-item active">Users</li>
                        </ol>
                    </nav>
                    <h1 class="h3 mb-1">User Management</h1>
                    <p class="text-muted mb-0">Manage hospital staff and system users</p>
                </div>
                @can('users.create')
                <a href="{{ route('users.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-2"></i>Add New User
                </a>
                @endcan
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('users.index') }}" class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="is_active" class="form-select">
                        <option value="">All Status</option>
                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="ti ti-filter"></i> Filter
                    </button>
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-refresh"></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Roles</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <span class="text-primary fw-bold">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                                    </div>
                                    <div>
                                        <p class="mb-0 fw-semibold">{{ $user->name }}</p>
                                        <small class="text-muted">ID: #{{ $user->id }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->phone ?? 'N/A' }}</td>
                            <td>
                                @foreach($user->roles as $role)
                                    <span class="badge bg-info-subtle text-info border border-info">{{ $role->name }}</span>
                                @endforeach
                                @if($user->roles->isEmpty())
                                    <span class="badge bg-secondary-subtle text-secondary">No Role</span>
                                @endif
                            </td>
                            <td>
                                @if($user->is_active)
                                    <span class="badge bg-success-subtle text-success border border-success">Active</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger border border-danger">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @can('users.view')
                                        <li><a class="dropdown-item" href="{{ route('users.show', $user) }}">
                                            <i class="ti ti-eye me-2"></i>View
                                        </a></li>
                                        @endcan
                                        @can('users.edit')
                                        <li><a class="dropdown-item" href="{{ route('users.edit', $user) }}">
                                            <i class="ti ti-edit me-2"></i>Edit
                                        </a></li>
                                        @endcan
                                        @can('users.assign_roles')
                                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#assignRoleModal{{ $user->id }}">
                                            <i class="ti ti-user-shield me-2"></i>Assign Roles
                                        </a></li>
                                        @endcan
                                        @can('users.activate')
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" action="{{ route('users.toggle-active', $user) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="dropdown-item {{ $user->is_active ? 'text-warning' : 'text-success' }}">
                                                    <i class="ti ti-{{ $user->is_active ? 'lock' : 'lock-open' }} me-2"></i>
                                                    {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                        </li>
                                        @endcan
                                        @can('users.delete')
                                        <li>
                                            <form method="POST" action="{{ route('users.destroy', $user) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
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

                        <!-- Assign Role Modal -->
                        @can('users.assign_roles')
                        <div class="modal fade" id="assignRoleModal{{ $user->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('users.assign-role', $user) }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Assign Roles to {{ $user->name }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Select Roles</label>
                                                <select name="roles[]" class="form-select" multiple size="5">
                                                    @foreach($roles as $role)
                                                        <option value="{{ $role->id }}" {{ $user->hasRole($role->name) ? 'selected' : '' }}>
                                                            {{ $role->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <small class="text-muted">Hold Ctrl/Cmd to select multiple roles</small>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endcan
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="ti ti-users fs-1 text-muted mb-2"></i>
                                <p class="text-muted mb-0">No users found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($users->hasPages())
        <div class="card-footer">
            {{ $users->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

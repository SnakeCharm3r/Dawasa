@extends('layouts.app')

@section('title', 'Role Management - Hospital Management System')

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
                            <li class="breadcrumb-item active">Roles</li>
                        </ol>
                    </nav>
                    <h1 class="h3 mb-1">Role Management</h1>
                    <p class="text-muted mb-0">Manage user roles and their permissions</p>
                </div>
                @can('roles.create')
                <a href="{{ route('roles.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-2"></i>Create New Role
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

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Role Name</th>
                            <th>Permissions</th>
                            <th>Users</th>
                            <th>Created</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roles as $role)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-{{ ['primary', 'success', 'info', 'warning', 'secondary'][$loop->index % 5] }} bg-opacity-10 rounded d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <i class="ti ti-shield text-{{ ['primary', 'success', 'info', 'warning', 'secondary'][$loop->index % 5] }}"></i>
                                    </div>
                                    <div>
                                        <p class="mb-0 fw-semibold">{{ $role->name }}</p>
                                        <small class="text-muted">Guard: {{ $role->guard_name }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info-subtle text-info border border-info">
                                    {{ $role->permissions->count() }} permissions
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary">
                                    {{ $role->users_count }} users
                                </span>
                            </td>
                            <td>{{ $role->created_at->format('M d, Y') }}</td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @can('roles.view')
                                        <li><a class="dropdown-item" href="{{ route('roles.show', $role) }}">
                                            <i class="ti ti-eye me-2"></i>View Details
                                        </a></li>
                                        @endcan
                                        @can('roles.edit')
                                        <li><a class="dropdown-item" href="{{ route('roles.edit', $role) }}">
                                            <i class="ti ti-edit me-2"></i>Edit Role
                                        </a></li>
                                        @endcan
                                        @can('permissions.assign')
                                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#permissionsModal{{ $role->id }}">
                                            <i class="ti ti-key me-2"></i>Manage Permissions
                                        </a></li>
                                        @endcan
                                        @can('roles.delete')
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" action="{{ route('roles.destroy', $role) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this role?');">
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
                            <td colspan="5" class="text-center py-4">
                                <i class="ti ti-shield fs-1 text-muted mb-2"></i>
                                <p class="text-muted mb-0">No roles found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($roles->hasPages())
        <div class="card-footer">
            {{ $roles->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Permission Modals -->
@foreach($roles as $role)
<div class="modal fade" id="permissionsModal{{ $role->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('roles.permissions.update', $role) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Manage Permissions - {{ $role->name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Select permissions for this role:</p>
                    <div class="row">
                        @foreach($role->permissions->chunk(ceil($role->permissions->count() / 2)) as $chunk)
                        <div class="col-md-6">
                            @foreach($chunk as $permission)
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->id }}" checked>
                                <label class="form-check-label">{{ $permission->name }}</label>
                            </div>
                            @endforeach
                        </div>
                        @endforeach
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
@endforeach
@endsection

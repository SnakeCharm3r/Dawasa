@extends('layouts.app')

@section('title', 'Permissions - Hospital Management System')

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
                            <li class="breadcrumb-item active">Permissions</li>
                        </ol>
                    </nav>
                    <h1 class="h3 mb-1">Permissions</h1>
                    <p class="text-muted mb-0">View all system permissions and their assignments</p>
                </div>
                <a href="{{ route('permissions.by-module') }}" class="btn btn-outline-primary">
                    <i class="ti ti-layout-grid me-2"></i>View by Module
                </a>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Permission Name</th>
                            <th>Module</th>
                            <th>Assigned Roles</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($permissions as $permission)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="bg-info bg-opacity-10 rounded d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <i class="ti ti-key text-info"></i>
                                    </div>
                                    <div>
                                        <p class="mb-0 fw-semibold">{{ $permission->name }}</p>
                                        <small class="text-muted">Guard: {{ $permission->guard_name }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary text-capitalize">
                                    {{ explode('.', $permission->name)[0] }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-primary-subtle text-primary border border-primary">
                                    {{ $permission->roles_count }} roles
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('permissions.roles', $permission) }}" class="btn btn-light btn-sm">
                                    <i class="ti ti-eye"></i> View Roles
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4">
                                <i class="ti ti-key fs-1 text-muted mb-2"></i>
                                <p class="text-muted mb-0">No permissions found</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($permissions->hasPages())
        <div class="card-footer">
            {{ $permissions->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

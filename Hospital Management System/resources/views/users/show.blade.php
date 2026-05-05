@extends('layouts.app')

@section('title', 'User Details - Hospital Management System')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                            <li class="breadcrumb-item active">User Details</li>
                        </ol>
                    </nav>
                    <h1 class="fs-3 mb-1">User Details</h1>
                </div>
                <div>
                    @can('users.edit')
                    <a href="{{ route('users.edit', $user) }}" class="btn btn-primary">
                        <i class="ti ti-edit"></i> Edit User
                    </a>
                    @endcan
                    <a href="{{ route('users.index') }}" class="btn btn-secondary">
                        <i class="ti ti-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center p-4">
                    <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px;">
                        <span class="text-primary fw-bold fs-1">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                    </div>
                    <h4 class="mb-1">{{ $user->name }}</h4>
                    <p class="text-muted mb-3">{{ $user->email }}</p>
                    <div class="d-flex justify-content-center gap-2">
                        @if($user->is_active)
                            <span class="badge bg-success-subtle text-success border border-success">Active</span>
                        @else
                            <span class="badge bg-danger-subtle text-danger border border-danger">Inactive</span>
                        @endif
                    </div>
                </div>
                <div class="card-footer">
                    <div class="row text-center">
                        <div class="col-6 border-end">
                            <h6 class="mb-0">{{ $user->roles->count() }}</h6>
                            <small class="text-muted">Roles</small>
                        </div>
                        <div class="col-6">
                            <h6 class="mb-0">{{ $user->created_at->format('M Y') }}</h6>
                            <small class="text-muted">Joined</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">User Information</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted">Full Name</label>
                            <p class="mb-0 fw-semibold">{{ $user->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Email Address</label>
                            <p class="mb-0 fw-semibold">{{ $user->email }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Phone Number</label>
                            <p class="mb-0 fw-semibold">{{ $user->phone ?? 'Not provided' }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Account Status</label>
                            <p class="mb-0">
                                @if($user->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-danger">Inactive</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">User ID</label>
                            <p class="mb-0 fw-semibold">#{{ $user->id }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted">Created At</label>
                            <p class="mb-0 fw-semibold">{{ $user->created_at->format('F d, Y h:i A') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Assigned Roles</h5>
                </div>
                <div class="card-body">
                    @if($user->roles->isNotEmpty())
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($user->roles as $role)
                                <div class="card border">
                                    <div class="card-body p-3">
                                        <h6 class="mb-1">{{ $role->name }}</h6>
                                        <small class="text-muted">{{ $role->permissions->count() }} permissions</small>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted mb-0">No roles assigned to this user.</p>
                    @endif
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Direct Permissions</h5>
                </div>
                <div class="card-body">
                    @if($user->permissions->isNotEmpty())
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($user->permissions as $permission)
                                <span class="badge bg-info-subtle text-info border border-info">{{ $permission->name }}</span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted mb-0">No direct permissions assigned. User inherits permissions through roles.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

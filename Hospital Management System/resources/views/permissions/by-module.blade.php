@extends('layouts.app')

@section('title', 'Permissions by Module - Hospital Management System')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('permissions.index') }}">Permissions</a></li>
                            <li class="breadcrumb-item active">By Module</li>
                        </ol>
                    </nav>
                    <h1 class="fs-3 mb-1">Permissions by Module</h1>
                    <p>Permissions organized by functional modules</p>
                </div>
                <a href="{{ route('permissions.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left"></i> Back to List
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        @forelse($permissions as $module => $modulePermissions)
        <div class="col-lg-6 col-xl-4 mb-4">
            <div class="card h-100">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-capitalize">{{ $module }}</h5>
                    <span class="badge bg-primary">{{ $modulePermissions->count() }}</span>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        @foreach($modulePermissions as $permission)
                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                            <span>{{ $permission->name }}</span>
                            <span class="badge bg-info-subtle text-info border border-info">
                                {{ $permission->roles->count() }} roles
                            </span>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="ti ti-key fs-1 text-muted mb-3"></i>
                    <p class="text-muted">No permissions found</p>
                </div>
            </div>
        </div>
        @endforelse
    </div>
</div>
@endsection

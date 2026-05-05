@extends('layouts.app')

@section('title', 'Create Role - Hospital Management System')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="mb-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">Roles</a></li>
                        <li class="breadcrumb-item active">Create Role</li>
                    </ol>
                </nav>
                <h1 class="fs-3 mb-1">Create New Role</h1>
                <p>Define a new role and assign permissions</p>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('roles.store') }}">
        @csrf
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body p-4">
                        <div class="mb-4">
                            <label for="name" class="form-label">Role Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g., Receptionist">
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label d-block">Permissions</label>
                            <p class="text-muted small">Select the permissions for this role:</p>

                            @foreach($permissions as $module => $modulePermissions)
                            <div class="card mb-3 border">
                                <div class="card-header bg-light">
                                    <div class="form-check">
                                        <input class="form-check-input module-checkbox" type="checkbox" data-module="{{ $module }}">
                                        <label class="form-check-label fw-semibold text-capitalize">{{ $module }}</label>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        @foreach($modulePermissions->chunk(ceil($modulePermissions->count() / 2)) as $chunk)
                                        <div class="col-md-6">
                                            @foreach($chunk as $permission)
                                            <div class="form-check mb-2 permission-check" data-module="{{ $module }}">
                                                <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->id }}" id="perm{{ $permission->id }}">
                                                <label class="form-check-label" for="perm{{ $permission->id }}">{{ $permission->name }}</label>
                                            </div>
                                            @endforeach
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Role Summary</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">Create a new role with selected permissions. Users assigned to this role will inherit all selected permissions.</p>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100 mb-2">
                            <i class="ti ti-check"></i> Create Role
                        </button>
                        <a href="{{ route('roles.index') }}" class="btn btn-secondary w-100">
                            <i class="ti ti-x"></i> Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    document.querySelectorAll('.module-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const module = this.dataset.module;
            const checked = this.checked;
            document.querySelectorAll('.permission-check[data-module="' + module + '"] input').forEach(perm => {
                perm.checked = checked;
            });
        });
    });
</script>
@endsection

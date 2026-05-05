@extends('layouts.app')

@section('title', 'Create User - Hospital Management System')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="mb-4">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
                        <li class="breadcrumb-item active">Create User</li>
                    </ol>
                </nav>
                <h1 class="fs-3 mb-1">Create New User</h1>
                <p>Add a new staff member to the system</p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('users.store') }}">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="roles" class="form-label">Roles <span class="text-danger">*</span></label>
                                <select class="form-select @error('roles') is-invalid @enderror" id="roles" name="roles[]" multiple required>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}" {{ in_array($role->id, old('roles', [])) ? 'selected' : '' }}>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Hold Ctrl/Cmd to select multiple roles</small>
                                @error('roles')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-check"></i> Create User
                            </button>
                            <a href="{{ route('users.index') }}" class="btn btn-secondary">
                                <i class="ti ti-x"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Role Information</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2">
                            <span class="badge bg-primary">System Admin</span>
                            <small class="d-block text-muted">Full system access, manages users and settings</small>
                        </li>
                        <li class="mb-2">
                            <span class="badge bg-success">Hospital Manager</span>
                            <small class="d-block text-muted">Manages staff, reports, and operations</small>
                        </li>
                        <li class="mb-2">
                            <span class="badge bg-info">Doctor</span>
                            <small class="d-block text-muted">Patient care, prescriptions, assessments</small>
                        </li>
                        <li class="mb-2">
                            <span class="badge bg-warning">Nurse</span>
                            <small class="d-block text-muted">Patient care, vitals, medication administration</small>
                        </li>
                        <li class="mb-0">
                            <span class="badge bg-secondary">Cashier</span>
                            <small class="d-block text-muted">Patient onboarding, payments, receipts</small>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

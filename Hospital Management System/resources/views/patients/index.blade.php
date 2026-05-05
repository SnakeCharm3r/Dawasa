@extends('layouts.app')

@section('title', 'Patients - Hospital Management System')

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
                            <li class="breadcrumb-item active">Patients</li>
                        </ol>
                    </nav>
                    <h1 class="h3 mb-1">Patients</h1>
                    <p class="text-muted mb-0">Manage patient records and information</p>
                </div>
                @can('patients.create')
                <a href="{{ route('patients.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-2"></i>Add Patient
                </a>
                @endcan
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('patients.index') }}" class="row g-3">
                <div class="col-md-4">
                    <input type="text" name="search" class="form-control" placeholder="Search by name or ID..." value="{{ request('search') }}">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="admitted" {{ request('status') == 'admitted' ? 'selected' : '' }}>Admitted</option>
                        <option value="outpatient" {{ request('status') == 'outpatient' ? 'selected' : '' }}>Outpatient</option>
                        <option value="discharged" {{ request('status') == 'discharged' ? 'selected' : '' }}>Discharged</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="department" class="form-select">
                        <option value="">All Departments</option>
                        <option value="cardiology" {{ request('department') == 'cardiology' ? 'selected' : '' }}>Cardiology</option>
                        <option value="emergency" {{ request('department') == 'emergency' ? 'selected' : '' }}>Emergency</option>
                        <option value="general" {{ request('department') == 'general' ? 'selected' : '' }}>General</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="ti ti-search me-2"></i>Search
                    </button>
                    <a href="{{ route('patients.index') }}" class="btn btn-outline-secondary">
                        <i class="ti ti-refresh me-2"></i>Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Patients Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Patient ID</th>
                            <th>Name</th>
                            <th>Gender</th>
                            <th>Age</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($patients ?? [] as $patient)
                        <tr>
                            <td><strong>#{{ $patient->id ?? 'PT' . str_pad($loop->iteration, 4, '0', STR_PAD_LEFT) }}</strong></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                        <span class="text-primary fw-bold small">{{ strtoupper(substr($patient->name ?? 'Patient', 0, 1)) }}</span>
                                    </div>
                                    <span>{{ $patient->name ?? 'Patient Name' }}</span>
                                </div>
                            </td>
                            <td>{{ $patient->gender ?? 'N/A' }}</td>
                            <td>{{ $patient->age ?? 'N/A' }}</td>
                            <td>{{ $patient->department ?? 'General' }}</td>
                            <td>
                                <span class="badge bg-{{ $patient->status_color ?? 'primary' }}-subtle text-{{ $patient->status_color ?? 'primary' }} border border-{{ $patient->status_color ?? 'primary' }}">
                                    {{ $patient->status ?? 'Unknown' }}
                                </span>
                            </td>
                            <td class="text-end">
                                <div class="dropdown">
                                    <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="ti ti-dots"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @can('patients.view')
                                        <li><a class="dropdown-item" href="{{ route('patients.show', $patient->id) }}">
                                            <i class="ti ti-eye me-2"></i>View
                                        </a></li>
                                        @endcan
                                        @can('patients.edit')
                                        <li><a class="dropdown-item" href="{{ route('patients.edit', $patient->id) }}">
                                            <i class="ti ti-edit me-2"></i>Edit
                                        </a></li>
                                        @endcan
                                        @can('patients.delete')
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form method="POST" action="{{ route('patients.destroy', $patient->id) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this patient?');">
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
                                    <i class="ti ti-users fs-1 mb-2 d-block"></i>
                                    <p class="mb-0">No patients found</p>
                                    @can('patients.create')
                                    <a href="{{ route('patients.create') }}" class="btn btn-primary btn-sm mt-2">
                                        <i class="ti ti-plus me-2"></i>Add First Patient
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
        @if(isset($patients) && $patients->hasPages())
        <div class="card-footer">
            {{ $patients->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

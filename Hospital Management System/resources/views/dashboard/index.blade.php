@extends('layouts.app')

@section('title', 'Dashboard - Hospital Management System')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1">Dashboard</h1>
                    <p class="text-muted mb-0">Welcome back, {{ auth()->user()->name }}</p>
                </div>
                <div class="text-end">
                    <span class="text-muted small">{{ now()->format('l, F d, Y') }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        @can('patients.view')
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                                <i class="ti ti-users text-primary fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Total Patients</h6>
                            <h3 class="mb-0">{{ $totalPatients ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endcan

        @can('appointments.view')
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 rounded-3 p-3">
                                <i class="ti ti-calendar-event text-success fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Today's Appointments</h6>
                            <h3 class="mb-0">{{ $todayAppointments ?? 0 }}</h3>
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
                            <div class="bg-info bg-opacity-10 rounded-3 p-3">
                                <i class="ti ti-bed text-info fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Available Beds</h6>
                            <h3 class="mb-0">{{ $availableBeds ?? 0 }}</h3>
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
                                <i class="ti ti-urgent text-warning fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="text-muted mb-1">Emergency Cases</h6>
                            <h3 class="mb-0">{{ $emergencyCases ?? 0 }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions & Summary -->
    <div class="row g-3">
        <!-- Quick Actions -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom-0 pt-4">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        @can('patients.create')
                        <a href="{{ route('patients.create') }}" class="btn btn-outline-primary d-flex align-items-center justify-content-between">
                            <span><i class="ti ti-user-plus me-2"></i>New Patient</span>
                            <i class="ti ti-arrow-right"></i>
                        </a>
                        @endcan

                        @can('appointments.create')
                        <a href="#" class="btn btn-outline-success d-flex align-items-center justify-content-between">
                            <span><i class="ti ti-calendar-plus me-2"></i>New Appointment</span>
                            <i class="ti ti-arrow-right"></i>
                        </a>
                        @endcan

                        @can('billing.create')
                        <a href="#" class="btn btn-outline-info d-flex align-items-center justify-content-between">
                            <span><i class="ti ti-receipt-2 me-2"></i>Create Bill</span>
                            <i class="ti ti-arrow-right"></i>
                        </a>
                        @endcan

                        @can('inventory.view')
                        <a href="{{ route('inventory.index') }}" class="btn btn-outline-warning d-flex align-items-center justify-content-between">
                            <span><i class="ti ti-packages me-2"></i>View Inventory</span>
                            <i class="ti ti-arrow-right"></i>
                        </a>
                        @endcan

                        @canany(['reports.view_sales', 'reports.view_patients', 'reports.view_staff', 'reports.view_financial'])
                        <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary d-flex align-items-center justify-content-between">
                            <span><i class="ti ti-chart-bar me-2"></i>View Reports</span>
                            <i class="ti ti-arrow-right"></i>
                        </a>
                        @endcanany
                    </div>
                </div>
            </div>
        </div>

        <!-- System Overview -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom-0 pt-4">
                    <h5 class="mb-0">System Overview</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @can('users.view')
                        <div class="col-md-6">
                            <div class="d-flex align-items-center p-3 bg-light rounded-3">
                                <div class="flex-shrink-0">
                                    <i class="ti ti-user-check text-primary fs-3"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1">Staff Members</h6>
                                    <p class="mb-0 text-muted">{{ $totalStaff ?? 0 }} active users</p>
                                </div>
                            </div>
                        </div>
                        @endcan

                        @can('inventory.view')
                        <div class="col-md-6">
                            <div class="d-flex align-items-center p-3 bg-light rounded-3">
                                <div class="flex-shrink-0">
                                    <i class="ti ti-package text-success fs-3"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1">Inventory Items</h6>
                                    <p class="mb-0 text-muted">{{ $totalInventory ?? 0 }} items in stock</p>
                                </div>
                            </div>
                        </div>
                        @endcan

                        @can('billing.view')
                        <div class="col-md-6">
                            <div class="d-flex align-items-center p-3 bg-light rounded-3">
                                <div class="flex-shrink-0">
                                    <i class="ti ti-cash text-info fs-3"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1">Today's Revenue</h6>
                                    <p class="mb-0 text-muted">{{ $todayRevenue ?? '$0.00' }}</p>
                                </div>
                            </div>
                        </div>
                        @endcan

                        <div class="col-md-6">
                            <div class="d-flex align-items-center p-3 bg-light rounded-3">
                                <div class="flex-shrink-0">
                                    <i class="ti ti-activity text-warning fs-3"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1">System Status</h6>
                                    <p class="mb-0 text-success"><i class="ti ti-circle-check me-1"></i>Operational</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Dashboard specific scripts can go here
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Dashboard loaded');
    });
</script>
@endpush

@extends('layouts.app')

@section('title', 'Reports - Hospital Management System')

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
                            <li class="breadcrumb-item active">Reports</li>
                        </ol>
                    </nav>
                    <h1 class="h3 mb-1">Reports</h1>
                    <p class="text-muted mb-0">Generate and view hospital reports</p>
                </div>
                @can('reports.export')
                <button class="btn btn-outline-primary" onclick="exportReport()">
                    <i class="ti ti-download me-2"></i>Export
                </button>
                @endcan
            </div>
        </div>
    </div>

    <!-- Report Type Selection -->
    <div class="row g-3 mb-4">
        @can('reports.view_patients')
        <div class="col-xl-3 col-md-6">
            <a href="#" class="card text-decoration-none text-dark h-100 report-card" data-report-type="patients">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                                <i class="ti ti-users text-primary fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">Patient Reports</h6>
                            <p class="text-muted small mb-0">Admissions, discharges, demographics</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endcan

        @can('reports.view_sales')
        <div class="col-xl-3 col-md-6">
            <a href="#" class="card text-decoration-none text-dark h-100 report-card" data-report-type="sales">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-opacity-10 rounded-3 p-3">
                                <i class="ti ti-currency-dollar text-success fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">Sales Reports</h6>
                            <p class="text-muted small mb-0">Revenue, payments, billing</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endcan

        @can('reports.view_staff')
        <div class="col-xl-3 col-md-6">
            <a href="#" class="card text-decoration-none text-dark h-100 report-card" data-report-type="staff">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-opacity-10 rounded-3 p-3">
                                <i class="ti ti-user-check text-info fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">Staff Reports</h6>
                            <p class="text-muted small mb-0">Performance, attendance, schedules</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endcan

        @can('reports.view_financial')
        <div class="col-xl-3 col-md-6">
            <a href="#" class="card text-decoration-none text-dark h-100 report-card" data-report-type="financial">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-opacity-10 rounded-3 p-3">
                                <i class="ti ti-chart-pie text-warning fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">Financial Reports</h6>
                            <p class="text-muted small mb-0">Costs, expenses, profit analysis</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endcan

        @can('reports.view_inventory')
        <div class="col-xl-3 col-md-6">
            <a href="#" class="card text-decoration-none text-dark h-100 report-card" data-report-type="inventory">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-danger bg-opacity-10 rounded-3 p-3">
                                <i class="ti ti-package text-danger fs-4"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-1">Inventory Reports</h6>
                            <p class="text-muted small mb-0">Stock levels, usage, orders</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>
        @endcan
    </div>

    <!-- Report Generation Form -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Generate Report</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('reports.generate') }}" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Report Type</label>
                    <select name="type" class="form-select" required>
                        <option value="">Select Report Type</option>
                        @can('reports.view_patients')
                        <option value="patients">Patient Report</option>
                        @endcan
                        @can('reports.view_sales')
                        <option value="sales">Sales Report</option>
                        @endcan
                        @can('reports.view_staff')
                        <option value="staff">Staff Report</option>
                        @endcan
                        @can('reports.view_financial')
                        <option value="financial">Financial Report</option>
                        @endcan
                        @can('reports.view_inventory')
                        <option value="inventory">Inventory Report</option>
                        @endcan
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date Range</label>
                    <select name="date_range" class="form-select">
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month" selected>This Month</option>
                        <option value="quarter">This Quarter</option>
                        <option value="year">This Year</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From</label>
                    <input type="date" name="from_date" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To</label>
                    <input type="date" name="to_date" class="form-control">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ti ti-file-analytics"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.report-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: all 0.2s ease;
}
</style>
@endpush

@push('scripts')
<script>
function exportReport() {
    alert('Export functionality will be implemented. This will generate PDF/Excel reports based on selected criteria.');
}

document.addEventListener('DOMContentLoaded', function() {
    const reportCards = document.querySelectorAll('.report-card');
    reportCards.forEach(card => {
        card.addEventListener('click', function(e) {
            e.preventDefault();
            const reportType = this.getAttribute('data-report-type');
            const select = document.querySelector('select[name="type"]');
            if (select) {
                select.value = reportType;
                select.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    });
});
</script>
@endpush

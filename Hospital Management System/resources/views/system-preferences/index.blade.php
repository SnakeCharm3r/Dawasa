@extends('layouts.app')

@section('title', 'System Preferences - Hospital Management System')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a></li>
                        <li class="breadcrumb-item active">System Settings</li>
                    </ol>
                </nav>
                <h1 class="h3 mb-1">System Preferences</h1>
                <p class="text-muted mb-0">Manage hospital system settings and branding</p>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    @endif

    <div class="row">
        <!-- General Settings -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">General Settings</h5>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('system-preferences.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="hospital_name" class="form-label">Hospital Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('hospital_name') is-invalid @enderror" id="hospital_name" name="hospital_name" value="{{ old('hospital_name', $preferences->hospital_name) }}" required>
                                @error('hospital_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label">Contact Email</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $preferences->email) }}">
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="phone" class="form-label">Contact Phone</label>
                                <input type="tel" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $preferences->phone) }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="website" class="form-label">Website</label>
                                <input type="url" class="form-control @error('website') is-invalid @enderror" id="website" name="website" value="{{ old('website', $preferences->website) }}" placeholder="https://example.com">
                                @error('website')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address" rows="3">{{ old('address', $preferences->address) }}</textarea>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="description" class="form-label">Hospital Description</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $preferences->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="timezone" class="form-label">Timezone</label>
                                <select class="form-select @error('timezone') is-invalid @enderror" id="timezone" name="timezone">
                                    <option value="UTC" {{ old('timezone', $preferences->timezone) == 'UTC' ? 'selected' : '' }}>UTC</option>
                                    <option value="America/New_York" {{ old('timezone', $preferences->timezone) == 'America/New_York' ? 'selected' : '' }}>Eastern Time (ET)</option>
                                    <option value="America/Chicago" {{ old('timezone', $preferences->timezone) == 'America/Chicago' ? 'selected' : '' }}>Central Time (CT)</option>
                                    <option value="America/Denver" {{ old('timezone', $preferences->timezone) == 'America/Denver' ? 'selected' : '' }}>Mountain Time (MT)</option>
                                    <option value="America/Los_Angeles" {{ old('timezone', $preferences->timezone) == 'America/Los_Angeles' ? 'selected' : '' }}>Pacific Time (PT)</option>
                                    <option value="Europe/London" {{ old('timezone', $preferences->timezone) == 'Europe/London' ? 'selected' : '' }}>London (GMT)</option>
                                    <option value="Europe/Paris" {{ old('timezone', $preferences->timezone) == 'Europe/Paris' ? 'selected' : '' }}>Paris (CET)</option>
                                    <option value="Asia/Tokyo" {{ old('timezone', $preferences->timezone) == 'Asia/Tokyo' ? 'selected' : '' }}>Tokyo (JST)</option>
                                    <option value="Africa/Nairobi" {{ old('timezone', $preferences->timezone) == 'Africa/Nairobi' ? 'selected' : '' }}>Nairobi (EAT)</option>
                                </select>
                                @error('timezone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="date_format" class="form-label">Date Format</label>
                                <select class="form-select @error('date_format') is-invalid @enderror" id="date_format" name="date_format">
                                    <option value="Y-m-d" {{ old('date_format', $preferences->date_format) == 'Y-m-d' ? 'selected' : '' }}>YYYY-MM-DD</option>
                                    <option value="m/d/Y" {{ old('date_format', $preferences->date_format) == 'm/d/Y' ? 'selected' : '' }}>MM/DD/YYYY</option>
                                    <option value="d/m/Y" {{ old('date_format', $preferences->date_format) == 'd/m/Y' ? 'selected' : '' }}>DD/MM/YYYY</option>
                                    <option value="d M Y" {{ old('date_format', $preferences->date_format) == 'd M Y' ? 'selected' : '' }}>DD Mon YYYY</option>
                                </select>
                                @error('date_format')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="currency" class="form-label">Currency</label>
                                <select class="form-select @error('currency') is-invalid @enderror" id="currency" name="currency">
                                    <option value="USD" {{ old('currency', $preferences->currency) == 'USD' ? 'selected' : '' }}>USD ($)</option>
                                    <option value="EUR" {{ old('currency', $preferences->currency) == 'EUR' ? 'selected' : '' }}>EUR (€)</option>
                                    <option value="GBP" {{ old('currency', $preferences->currency) == 'GBP' ? 'selected' : '' }}>GBP (£)</option>
                                    <option value="KES" {{ old('currency', $preferences->currency) == 'KES' ? 'selected' : '' }}>KES (KSh)</option>
                                    <option value="NGN" {{ old('currency', $preferences->currency) == 'NGN' ? 'selected' : '' }}>NGN (₦)</option>
                                </select>
                                @error('currency')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="items_per_page" class="form-label">Items Per Page</label>
                                <input type="number" class="form-control @error('items_per_page') is-invalid @enderror" id="items_per_page" name="items_per_page" value="{{ old('items_per_page', $preferences->items_per_page) }}" min="5" max="100">
                                @error('items_per_page')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" value="1" {{ old('maintenance_mode', $preferences->maintenance_mode) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="maintenance_mode">Maintenance Mode</label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            @can('system_preferences.edit')
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-check"></i> Save Settings
                            </button>
                            @endcan
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Branding -->
        <div class="col-lg-4">
            <!-- Logo -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Hospital Logo</h5>
                </div>
                <div class="card-body text-center p-4">
                    @if($preferences->logo_path)
                        <img src="{{ Storage::url($preferences->logo_path) }}" alt="Hospital Logo" class="img-fluid mb-3" style="max-height: 100px;">
                    @else
                        <div class="bg-light rounded p-4 mb-3">
                            <i class="ti ti-building-hospital fs-1 text-muted"></i>
                            <p class="text-muted mb-0">No logo uploaded</p>
                        </div>
                    @endif

                    @can('system_preferences.manage_logo')
                    <form method="POST" action="{{ route('system-preferences.update-logo') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <input type="file" class="form-control @error('logo') is-invalid @enderror" id="logo" name="logo" accept="image/*">
                            @error('logo')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Recommended: 400x100px, PNG or JPG</small>
                        </div>
                        <button type="submit" class="btn btn-outline-primary btn-sm">
                            <i class="ti ti-upload"></i> Upload Logo
                        </button>
                    </form>
                    @endcan
                </div>
            </div>

            <!-- Favicon -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Favicon</h5>
                </div>
                <div class="card-body text-center p-4">
                    @if($preferences->favicon_path)
                        <img src="{{ Storage::url($preferences->favicon_path) }}" alt="Favicon" class="img-fluid mb-3" style="max-height: 64px;">
                    @else
                        <div class="bg-light rounded p-4 mb-3">
                            <i class="ti ti-heartbeat fs-1 text-muted"></i>
                            <p class="text-muted mb-0">No favicon uploaded</p>
                        </div>
                    @endif

                    @can('system_preferences.manage_favicon')
                    <form method="POST" action="{{ route('system-preferences.update-favicon') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <input type="file" class="form-control @error('favicon') is-invalid @enderror" id="favicon" name="favicon" accept=".ico,.png,.jpg,.svg">
                            @error('favicon')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Recommended: 32x32px, ICO or PNG</small>
                        </div>
                        <button type="submit" class="btn btn-outline-primary btn-sm">
                            <i class="ti ti-upload"></i> Upload Favicon
                        </button>
                    </form>
                    @endcan
                </div>
            </div>

            <!-- System Info -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">System Info</h5>
                </div>
                <div class="card-body">
                    <p class="mb-1"><strong>Laravel:</strong> {{ app()->version() }}</p>
                    <p class="mb-1"><strong>PHP:</strong> {{ phpversion() }}</p>
                    <p class="mb-0"><strong>Environment:</strong> {{ config('app.env') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

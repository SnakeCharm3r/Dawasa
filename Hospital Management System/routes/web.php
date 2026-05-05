<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\SystemPreferenceController;

// Public routes
Route::get('/', function () {
    return redirect()->route('login');
});

// Auth routes
Route::middleware(['guest'])->group(function () {
    Route::get('/login', function () {
        return view('auth.login');
    })->name('login');

    Route::post('/login', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::get('/register', function () {
        return view('auth.register');
    })->name('register');

    Route::post('/register', [\App\Http\Controllers\Auth\RegisteredUserController::class, 'store'])->name('register.store');
});

Route::post('/logout', [\App\Http\Controllers\Auth\AuthenticatedSessionController::class, 'destroy'])
    ->name('logout')
    ->middleware('auth');

// Protected routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Patient routes
    Route::get('/patients', [PatientController::class, 'index'])->name('patients.index');
    Route::get('/patients/create', [PatientController::class, 'create'])->name('patients.create');
    Route::post('/patients', [PatientController::class, 'store'])->name('patients.store');
    Route::get('/patients/{id}', [PatientController::class, 'show'])->name('patients.show');
    Route::get('/patients/{id}/edit', [PatientController::class, 'edit'])->name('patients.edit');
    Route::put('/patients/{id}', [PatientController::class, 'update'])->name('patients.update');
    Route::delete('/patients/{id}', [PatientController::class, 'destroy'])->name('patients.destroy');

    // Inventory routes
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::get('/inventory/create', [InventoryController::class, 'create'])->name('inventory.create');
    Route::post('/inventory', [InventoryController::class, 'store'])->name('inventory.store');
    Route::get('/inventory/{id}', [InventoryController::class, 'show'])->name('inventory.show');
    Route::get('/inventory/{id}/edit', [InventoryController::class, 'edit'])->name('inventory.edit');
    Route::put('/inventory/{id}', [InventoryController::class, 'update'])->name('inventory.update');
    Route::delete('/inventory/{id}', [InventoryController::class, 'destroy'])->name('inventory.destroy');

    // Reports routes
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::post('/reports/generate', [ReportController::class, 'generate'])->name('reports.generate');

    // User Management routes
    Route::prefix('admin')->name('users.')->group(function () {
        Route::get('/users', [UserManagementController::class, 'index'])->name('index');
        Route::get('/users/create', [UserManagementController::class, 'create'])->name('create');
        Route::post('/users', [UserManagementController::class, 'store'])->name('store');
        Route::get('/users/{user}', [UserManagementController::class, 'show'])->name('show');
        Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('edit');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('update');
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])->name('destroy');
        Route::post('/users/{user}/toggle-active', [UserManagementController::class, 'toggleActive'])->name('toggle-active');
        Route::post('/users/{user}/assign-role', [UserManagementController::class, 'assignRole'])->name('assign-role');
    });

    // Role Management routes
    Route::prefix('admin')->name('roles.')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('index');
        Route::get('/roles/create', [RoleController::class, 'create'])->name('create');
        Route::post('/roles', [RoleController::class, 'store'])->name('store');
        Route::get('/roles/{role}', [RoleController::class, 'show'])->name('show');
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('edit');
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('destroy');
        Route::post('/roles/{role}/permissions', [RoleController::class, 'updatePermissions'])->name('permissions.update');
    });

    // Permission Management routes
    Route::prefix('admin')->name('permissions.')->group(function () {
        Route::get('/permissions', [PermissionController::class, 'index'])->name('index');
        Route::get('/permissions/by-module', [PermissionController::class, 'byModule'])->name('by-module');
        Route::get('/permissions/{permission}/roles', [PermissionController::class, 'roles'])->name('roles');
    });

    // System Preferences routes
    Route::prefix('admin')->name('system-preferences.')->group(function () {
        Route::get('/system-preferences', [SystemPreferenceController::class, 'index'])->name('index');
        Route::put('/system-preferences', [SystemPreferenceController::class, 'update'])->name('update');
        Route::post('/system-preferences/logo', [SystemPreferenceController::class, 'updateLogo'])->name('update-logo');
        Route::post('/system-preferences/favicon', [SystemPreferenceController::class, 'updateFavicon'])->name('update-favicon');
    });

    // Public API route for system info
    Route::get('/api/system-info', [SystemPreferenceController::class, 'publicInfo'])->name('system-info.public');
});

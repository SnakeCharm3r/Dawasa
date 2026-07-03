<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // User Management - Owner only
    Route::prefix('users')->name('users.')->middleware('role:owner')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/{user}', [UserController::class, 'show'])->name('show');
        Route::patch('/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('toggle-active');
        Route::get('/{user}/permissions', [UserController::class, 'editPermissions'])->name('permissions');
        Route::patch('/{user}/permissions', [UserController::class, 'updatePermissions'])->name('permissions.update');
    });

    // Categories - permission checked in controller, shows denied notice if insufficient access
    Route::resource('categories', CategoryController::class);

    // Settings - Owner only
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index')->middleware('role:owner');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update')->middleware('role:owner');

    // Products - Owner or permission required
    Route::resource('products', ProductController::class);

    // Ledger
    Route::get('/ledger', [LedgerController::class, 'index'])->name('ledger.index');
    Route::get('/ledger/create', [LedgerController::class, 'create'])->name('ledger.create');
    Route::post('/ledger', [LedgerController::class, 'store'])->name('ledger.store');
    Route::delete('/ledger/{ledgerEntry}', [LedgerController::class, 'destroy'])->name('ledger.destroy');
});

require __DIR__.'/auth.php';

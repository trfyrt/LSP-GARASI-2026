<?php

use App\Http\Controllers\Admin\GarageController;
use App\Http\Controllers\Admin\RentalExtensionController;
use App\Http\Controllers\Admin\RenterVerificationController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Renter\RentalController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(Auth::check() ? 'dashboard' : 'login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/renters', [RenterVerificationController::class, 'index'])->name('renters.index');
        Route::post('/renters/{renter}/approve', [RenterVerificationController::class, 'approve'])->name('renters.approve');
        Route::post('/renters/{renter}/reject', [RenterVerificationController::class, 'reject'])->name('renters.reject');

        Route::get('/garages', [GarageController::class, 'index'])->name('garages.index');
        Route::post('/garages', [GarageController::class, 'store'])->name('garages.store');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

        Route::get('/extensions', [RentalExtensionController::class, 'index'])->name('extensions.index');
        Route::get('/extensions/{rental}', [RentalExtensionController::class, 'edit'])->name('extensions.edit');
        Route::post('/extensions/{rental}', [RentalExtensionController::class, 'update'])->name('extensions.update');
    });

    Route::middleware('renter')->group(function () {
        Route::get('/rentals', [RentalController::class, 'index'])->name('rentals.index');
        Route::get('/rentals/create', [RentalController::class, 'create'])->name('rentals.create');
        Route::post('/rentals', [RentalController::class, 'store'])->name('rentals.store');
    });
});

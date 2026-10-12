<?php

use App\Http\Controllers\CompanyContextController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmploymentEpisodeController;
use App\Http\Controllers\LoginController;
use App\Http\Middleware\RequireCompanyContext;
use App\Http\Middleware\RequireEmployeeManagement;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/company', [CompanyContextController::class, 'show'])->name('company.choose');
    Route::post('/company', [CompanyContextController::class, 'update'])->name('company.switch');
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::middleware(RequireCompanyContext::class)->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::middleware(RequireEmployeeManagement::class)->group(function () {
            Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
            Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
        });
        Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
        Route::middleware(RequireEmployeeManagement::class)->group(function () {
            Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
            Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
            Route::get('/employees/{employee}/episodes/create', [EmploymentEpisodeController::class, 'create'])->name('employees.episodes.create');
            Route::post('/employees/{employee}/episodes', [EmploymentEpisodeController::class, 'store'])->name('employees.episodes.store');
            Route::get('/employees/{employee}/episodes/{episode}/edit', [EmploymentEpisodeController::class, 'edit'])->name('employees.episodes.edit');
            Route::put('/employees/{employee}/episodes/{episode}', [EmploymentEpisodeController::class, 'update'])->name('employees.episodes.update');
        });
    });
});

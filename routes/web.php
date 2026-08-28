<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberPortalController;
use App\Http\Controllers\RevenueReportPdfController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('permission:manage users')->group(function () {
        Route::livewire('users', 'app::users.index')->name('users.index');
        Route::livewire('users/create', 'app::users.create')->name('users.create');
        Route::livewire('users/{user}/edit', 'app::users.edit')->name('users.edit');
    });

    Route::middleware('permission:manage roles and permissions')->group(function () {
        Route::livewire('roles', 'app::roles.index')->name('roles.index');
        Route::livewire('roles/create', 'app::roles.create')->name('roles.create');
        Route::livewire('roles/{role}/edit', 'app::roles.edit')->name('roles.edit');
    });

    Route::middleware('permission:manage members')->group(function () {
        Route::livewire('members', 'app::members.index')->name('members.index');
        Route::livewire('members/create', 'app::members.create')->name('members.create');
        Route::livewire('members/{member}/edit', 'app::members.edit')->name('members.edit');
    });

    Route::middleware('permission:manage packages')->group(function () {
        Route::livewire('packages', 'app::packages.index')->name('packages.index');
        Route::livewire('packages/create', 'app::packages.create')->name('packages.create');
        Route::livewire('packages/{package}/edit', 'app::packages.edit')->name('packages.edit');
    });

    Route::middleware('permission:manage trainers')->group(function () {
        Route::livewire('personal-trainers', 'app::personal-trainers.index')->name('personal-trainers.index');
        Route::livewire('personal-trainers/create', 'app::personal-trainers.create')->name('personal-trainers.create');
        Route::livewire('personal-trainers/{trainer}/edit', 'app::personal-trainers.edit')->name('personal-trainers.edit');
    });

    Route::middleware('permission:manage payments')->group(function () {
        Route::livewire('transactions', 'app::transactions.index')->name('transactions.index');
        Route::livewire('transactions/create', 'app::transactions.create')->name('transactions.create');
        Route::livewire('transactions/{transaction}/edit', 'app::transactions.edit')->name('transactions.edit');
    });

    Route::livewire('reports', 'app::reports.index')
        ->middleware('permission:view reports')
        ->name('reports.index');
    Route::get('reports/pdf', RevenueReportPdfController::class)
        ->middleware('permission:view reports')
        ->name('reports.pdf');

    Route::middleware('permission:view workout catalog')->group(function () {
        Route::livewire('workout-programs', 'app::workout-programs.index')->name('workout-programs.index');
        Route::livewire('workout-programs/{program}', 'app::workout-programs.show')->name('workout-programs.show');
        Route::livewire('workout-programs/{program}/exercises/{programExercise}/edit', 'app::workout-programs.exercises.edit')->name('workout-program-exercises.edit');
    });

    Route::livewire('member-programs/create', 'app::member-programs.create')
        ->middleware('permission:assign workout programs')
        ->name('member-programs.create');
    Route::livewire('member-programs/{memberProgram}/edit', 'app::member-programs.edit')
        ->middleware('permission:assign workout programs')
        ->name('member-programs.edit');

    Route::middleware('permission:view assigned members')->group(function () {
        Route::livewire('trainer-members', 'app::trainer-members.index')->name('trainer-members.index');
        Route::livewire('trainer-members/{subscription}', 'app::trainer-members.show')->name('trainer-members.show');
    });

    Route::get('membership', [MemberPortalController::class, 'membership'])
        ->middleware('permission:view own membership')
        ->name('membership.show');

    Route::get('payments', [MemberPortalController::class, 'payments'])
        ->middleware('permission:view own payments')
        ->name('payments.index');

    Route::livewire('my-program', 'app::my-program.index')
        ->middleware('permission:view own workout program')
        ->name('my-program.index');
});

require __DIR__.'/settings.php';

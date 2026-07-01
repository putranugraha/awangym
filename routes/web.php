<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberPortalController;
use App\Http\Controllers\RevenueReportController;
use App\Livewire\Exercises\CreatePage as ExerciseCreatePage;
use App\Livewire\Exercises\EditPage as ExerciseEditPage;
use App\Livewire\Exercises\IndexPage as ExerciseIndexPage;
use App\Livewire\Members\CreatePage as MemberCreatePage;
use App\Livewire\Members\EditPage as MemberEditPage;
use App\Livewire\Members\IndexPage as MemberIndexPage;
use App\Livewire\Packages\CreatePage as PackageCreatePage;
use App\Livewire\Packages\EditPage as PackageEditPage;
use App\Livewire\Packages\IndexPage as PackageIndexPage;
use App\Livewire\PersonalTrainers\CreatePage as TrainerCreatePage;
use App\Livewire\PersonalTrainers\EditPage as TrainerEditPage;
use App\Livewire\PersonalTrainers\IndexPage as TrainerIndexPage;
use App\Livewire\Transactions\CreatePage as TransactionCreatePage;
use App\Livewire\Transactions\EditPage as TransactionEditPage;
use App\Livewire\Transactions\IndexPage as TransactionIndexPage;
use App\Livewire\WorkoutPrograms\CreatePage as ProgramCreatePage;
use App\Livewire\WorkoutPrograms\EditPage as ProgramEditPage;
use App\Livewire\WorkoutPrograms\IndexPage as ProgramIndexPage;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('members', MemberIndexPage::class)->name('members');
        Route::get('members/create', MemberCreatePage::class)->name('members.create');
        Route::get('members/{member}/edit', MemberEditPage::class)->name('members.edit');
        Route::get('packages', PackageIndexPage::class)->name('packages');
        Route::get('packages/create', PackageCreatePage::class)->name('packages.create');
        Route::get('packages/{package}/edit', PackageEditPage::class)->name('packages.edit');
        Route::get('trainers', TrainerIndexPage::class)->name('trainers');
        Route::get('trainers/create', TrainerCreatePage::class)->name('trainers.create');
        Route::get('trainers/{trainer}/edit', TrainerEditPage::class)->name('trainers.edit');
        Route::get('transactions', TransactionIndexPage::class)->name('transactions');
        Route::get('transactions/create', TransactionCreatePage::class)->name('transactions.create');
        Route::get('transactions/{transaction}/edit', TransactionEditPage::class)->name('transactions.edit');
        Route::get('reports', RevenueReportController::class)->name('reports');
    });

    Route::prefix('trainer')->name('trainer.')->middleware('role:personal_trainer')->group(function () {
        Route::get('exercises', ExerciseIndexPage::class)->name('exercises');
        Route::get('exercises/create', ExerciseCreatePage::class)->name('exercises.create');
        Route::get('exercises/{exercise}/edit', ExerciseEditPage::class)->name('exercises.edit');
        Route::get('programs', ProgramIndexPage::class)->name('programs');
        Route::get('programs/create', ProgramCreatePage::class)->name('programs.create');
        Route::get('programs/{program}/edit', ProgramEditPage::class)->name('programs.edit');
    });

    Route::prefix('member')->name('member.')->middleware('role:member')->group(function () {
        Route::get('membership', [MemberPortalController::class, 'membership'])->name('membership');
        Route::get('payments', [MemberPortalController::class, 'payments'])->name('payments');
        Route::get('programs', [MemberPortalController::class, 'programs'])->name('programs');
    });
});

require __DIR__.'/settings.php';

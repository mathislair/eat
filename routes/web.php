<?php

use App\Http\Controllers\Admin\NationalityController;
use App\Http\Controllers\Admin\NationalityGroupController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Events — specific paths before the {event} wildcard so they aren't captured by it.
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::get('/events/create', [EventController::class, 'create'])->name('events.create');
    Route::post('/events', [EventController::class, 'store'])->name('events.store');
    Route::get('/events/join/{code?}', [EventController::class, 'joinForm'])->name('events.join');
    Route::post('/events/join', [EventController::class, 'join'])->name('events.join.store');
    Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
    Route::delete('/events/{event}', [EventController::class, 'destroy'])->name('events.destroy');
    Route::delete('/events/{event}/leave', [EventController::class, 'leave'])->name('events.leave');
});

// Admin-only management of the nationality catalogue (JSON, no front-end yet).
Route::middleware(['auth', 'can:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::apiResource('nationalities', NationalityController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::apiResource('nationality-groups', NationalityGroupController::class)
        ->only(['index', 'store', 'update', 'destroy']);
});

require __DIR__.'/auth.php';

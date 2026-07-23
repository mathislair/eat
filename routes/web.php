<?php

use App\Http\Controllers\Admin\NationalityController;
use App\Http\Controllers\Admin\NationalityGroupController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventRevealController;
use App\Http\Controllers\EventVoteController;
use App\Http\Controllers\PreferenceController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Living reference for the cartoon design system (tokens, primitives, motion).
Route::get('/design-system', function () {
    return Inertia::render('DesignSystem');
})->name('design-system');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Personal food preferences: a standing taste profile that pre-fills votes
    // and stands in for a member who doesn't cast a ballot.
    Route::get('/preferences', [PreferenceController::class, 'edit'])->name('preferences.edit');
    Route::put('/preferences', [PreferenceController::class, 'update'])->name('preferences.update');

    // Events — specific paths before the {event} wildcard so they aren't captured by it.
    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::get('/events/create', [EventController::class, 'create'])->name('events.create');
    Route::post('/events', [EventController::class, 'store'])->name('events.store');
    Route::get('/events/join/{code?}', [EventController::class, 'joinForm'])->name('events.join');
    Route::post('/events/join', [EventController::class, 'join'])->name('events.join.store');
    // Per-event authorisation lives in the `can:` middleware against EventPolicy,
    // so each route declares the right it needs and controllers stay lean.
    // Opening an event drops you straight into its current phase (vote / reveal);
    // the hub holds the details, invite code, and host controls.
    Route::get('/events/{event}', [EventController::class, 'show'])
        ->middleware('can:view,event')->name('events.show');
    Route::get('/events/{event}/hub', [EventController::class, 'hub'])
        ->middleware('can:view,event')->name('events.hub');
    Route::delete('/events/{event}', [EventController::class, 'destroy'])
        ->middleware('can:delete,event')->name('events.destroy');
    Route::delete('/events/{event}/leave', [EventController::class, 'leave'])
        ->middleware('can:leave,event')->name('events.leave');

    // Voting: attendees cast/update a ballot; the creator validates to close.
    Route::get('/events/{event}/vote', [EventVoteController::class, 'edit'])
        ->middleware('can:vote,event')->name('events.vote.edit');
    Route::post('/events/{event}/vote', [EventVoteController::class, 'store'])
        ->middleware('can:vote,event')->name('events.vote.store');
    Route::post('/events/{event}/validate', [EventController::class, 'validate'])
        ->middleware('can:validate,event')->name('events.validate');

    // Reveal: swipe the ranked restaurant shortlist to land on one for everyone.
    Route::get('/events/{event}/reveal', [EventRevealController::class, 'show'])
        ->middleware('can:view,event')->name('events.reveal');
    Route::post('/events/{event}/reveal/swipe', [EventRevealController::class, 'swipe'])
        ->middleware('can:view,event')->name('events.reveal.swipe');
});

// Admin-only management of the nationality catalogue (JSON, no front-end yet).
Route::middleware(['auth', 'can:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::apiResource('nationalities', NationalityController::class)
        ->only(['index', 'store', 'update', 'destroy']);
    Route::apiResource('nationality-groups', NationalityGroupController::class)
        ->only(['index', 'store', 'update', 'destroy']);
});

require __DIR__.'/auth.php';

<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\EvenementController;
use App\Http\Controllers\ReservationController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/organizer/{id}', [\App\Http\Controllers\OrganizerProfileController::class, 'show'])->name('organizer.profile');
Route::get('/participant/{id}', [\App\Http\Controllers\ParticipantProfileController::class, 'show'])->name('participant.profile');
Route::get('/events/{id}', [EvenementController::class, 'show'])->name('events.show');

Route::get('/discovery', function () {
    return Inertia::render('Events/Discovery');
})->name('discovery');

Route::get('/login', function () {
    return Inertia::render('auth/Login');
})->name('login');

Route::get('/register', function () {
    return Inertia::render('auth/Register');
})->name('register');

// Auth Routes (moved from api.php for session support)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Google Auth Routes
Route::get('/auth/google', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('/auth/google/callback', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

Route::prefix('api')->group(function () {
    Route::get('/events', [EvenementController::class, 'index']);
    Route::get('/events/search', [EvenementController::class, 'search']);
    Route::get('/events/{id}', [EvenementController::class, 'show']);
});

Route::middleware(['auth'])->group(function () {
    // API endpoints masquerading in the web middleware for session persistence
    Route::prefix('api')->group(function () {
        Route::post('/events', [EvenementController::class, 'store']);
        Route::put('/events/{id}', [EvenementController::class, 'update']);
        Route::delete('/events/{id}', [EvenementController::class, 'destroy']);
        Route::patch('/events/{id}/ouvrir', [EvenementController::class, 'ouvrirReservation']);
        Route::patch('/events/{id}/fermer', [EvenementController::class, 'fermerReservation']);

        Route::post('/reservations', [ReservationController::class, 'store']);
        Route::get('/my-reservations', [ReservationController::class, 'chercherReservationParParticipant']);
        Route::patch('/reservations/{reservation}/annuler', [ReservationController::class, 'annuler']);
    });

    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::prefix('dashboard')->group(function () {
        Route::get('/events', function () {
            return Inertia::render('Events/EventsList');
        })->name('events.index');

        Route::get('/events/create', function () {
            return Inertia::render('Events/CreateEvent');
        })->name('events.create');

        Route::get('/events/{id}/edit', function ($id) {
            return Inertia::render('Events/EditEvent', ['id' => $id]);
        })->name('events.edit');

        Route::get('/reservations', function () {
            return Inertia::render('Events/MyReservations');
        })->name('reservations.index');
    });
});

require __DIR__ . '/settings.php';
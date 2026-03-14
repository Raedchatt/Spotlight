<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\EvenementController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\MediaUploadController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Route::has('register'),
    ]);
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

Route::prefix('web-api')->group(function () {
    Route::get('/events', [EvenementController::class, 'index']);
    Route::get('/events/search', [EvenementController::class, 'search']);
    Route::get('/events/{id}', [EvenementController::class, 'show']);
});

Route::middleware(['auth'])->group(function () {
    // API endpoints masquerading in the web middleware for session persistence
    Route::prefix('web-api')->group(function () {
        Route::post('/events', [EvenementController::class, 'store']);
        Route::put('/events/{id}', [EvenementController::class, 'update']);
        Route::delete('/events/{id}', [EvenementController::class, 'destroy']);
        Route::patch('/events/{id}/ouvrir', [EvenementController::class, 'ouvrirReservation']);
        Route::patch('/events/{id}/fermer', [EvenementController::class, 'fermerReservation']);

        Route::post('/reservations', [ReservationController::class, 'store']);
        Route::get('/my-reservations', [ReservationController::class, 'chercherReservationParParticipant']);
        Route::patch('/reservations/{reservation}/annuler', [ReservationController::class, 'annuler']);
        
        // Organizers Profile Management (via Session)
        Route::put('/organisateurs/{organisateur}', [\App\Http\Controllers\OrganisateurController::class, 'update']);

        // Messages (Session-based for Inertia)
        Route::get('/messages/recent', [MessageController::class, 'recent']);
        Route::post('/messages', [MessageController::class, 'envoyer'])->name('messages.envoyer');

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/{id}', [NotificationController::class, 'show']);
        Route::patch('/notifications/{id}/read', [NotificationController::class, 'marquerCommeLue']);
        Route::patch('/notifications/read-all', [NotificationController::class, 'marquerToutesCommeLues']);
    });

    Route::get('/dashboard', [\App\Http\Controllers\OrganisateurController::class, 'dashboardData'])->name('dashboard');
    // routes/web.php (for Inertia) or api.php

Route::middleware('auth')->group(function () {
    Route::post('/upload/image', [MediaUploadController::class, 'uploadImage']);
    Route::post('/upload/video', [MediaUploadController::class, 'uploadVideo']);
    Route::delete('/upload/media', [MediaUploadController::class, 'deleteMedia']);
});

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

    Route::get('/notifications', function () {
        return Inertia::render('Notifications/Index');
    })->name('notifications.index');

    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/{user}', [MessageController::class, 'show'])->name('messages.show');
});

require __DIR__ . '/settings.php';
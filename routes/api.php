<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EvenementController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\OrganisateurController;
use App\Http\Controllers\NotificationController;

Route::get('/events/search', [EvenementController::class, 'search']);

Route::middleware('auth:sanctum')->group(function () {

    // Messages
    Route::post('/messages/envoyer', [MessageController::class, 'envoyer']);
    Route::get('/messages/conversation/{id}', [MessageController::class, 'conversation']);
    Route::get('/messages/recent', [MessageController::class, 'recent']);

    // Events
    Route::apiResource('/events', EvenementController::class);
    Route::patch('/events/{id}/ouvrir', [EvenementController::class, 'ouvrirReservation']);
    Route::patch('/events/{id}/fermer', [EvenementController::class, 'fermerReservation']);

    // Reservations
    Route::get('/reservations/evenement/{evenement}', [ReservationController::class, 'chercherReservationParEvenement']);
    Route::get('/reservations/participant', [ReservationController::class, 'chercherReservationParParticipant']);
    Route::get('/reservations/{reservation}/montant', [ReservationController::class, 'calculerMontant']);
    Route::patch('/reservations/{reservation}/annuler', [ReservationController::class, 'annuler']);

    // Organizers (Profile & Requests)
    Route::post('/organisateurs', [OrganisateurController::class, 'store']); // Request to become organizer
    Route::put('/organisateurs/{organisateur}', [OrganisateurController::class, 'update']); // Update profile

    // Organizers (Event Management & Stats)
    Route::get('/organisateurs/stats', [OrganisateurController::class, 'consulterStatistiques']);
    Route::get('/organizer/financials', [\App\Http\Controllers\OrganizerFinancialController::class, 'index']);
    Route::post('/organisateurs/events', [OrganisateurController::class, 'creerEvenement']);
    Route::put('/organisateurs/events/{evenement}', [OrganisateurController::class, 'modifierEvenement']);
    Route::patch('/organisateurs/events/{evenement}/annuler', [OrganisateurController::class, 'annulerEvenement']);

    // Notification Routes (Moved to web.php for session auth)

    // Admin Routes
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/financials', [\App\Http\Controllers\Admin\FinancialDashboardController::class, 'index']);
        Route::get('/organisateurs/pending', [\App\Http\Controllers\Admin\AdminUserController::class, 'pendingOrganizers']);
        Route::patch('/organisateurs/{organisateur}/approve', [\App\Http\Controllers\Admin\AdminUserController::class, 'approveOrganizer']);
        Route::patch('/organisateurs/{organisateur}/reject', [\App\Http\Controllers\Admin\AdminUserController::class, 'rejectOrganizer']);
        Route::patch('/events/{id}/valid-event', [\App\Http\Controllers\Admin\AdminUserController::class, 'validEvent']);
        Route::post('/users/{user}/block', [\App\Http\Controllers\Admin\AdminUserController::class, 'bloquerUtilisateur']);
        Route::post('/users/{user}/unblock', [\App\Http\Controllers\Admin\AdminUserController::class, 'debloquerUtilisateur']);
        Route::post('/events/{evenement}/transfer-funds', [\App\Http\Controllers\Admin\AdminUserController::class, 'transfererFondsEvenement']);
    });
});

// Public Organizer Routes
Route::get('/organisateurs', [OrganisateurController::class, 'index']);
Route::get('/organisateurs/{organisateur}', [OrganisateurController::class, 'show']);

// Stripe Webhook
Route::post('/stripe/webhook', [\App\Http\Controllers\StripeController::class, 'webhook'])->name('stripe.webhook');
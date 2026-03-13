<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EvenementController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\OrganisateurController;

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
    Route::post('/organisateurs/events', [OrganisateurController::class, 'creerEvenement']);
    Route::put('/organisateurs/events/{evenement}', [OrganisateurController::class, 'modifierEvenement']);
    Route::patch('/organisateurs/events/{evenement}/annuler', [OrganisateurController::class, 'annulerEvenement']);

    // Admin Routes
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/organisateurs/pending', [OrganisateurController::class, 'pending']);
        Route::patch('/organisateurs/{organisateur}/approve', [OrganisateurController::class, 'approve']);
        Route::patch('/organisateurs/{organisateur}/reject', [OrganisateurController::class, 'reject']);
    });
});

// Public Organizer Routes
Route::get('/organisateurs', [OrganisateurController::class, 'index']);
Route::get('/organisateurs/{organisateur}', [OrganisateurController::class, 'show']);
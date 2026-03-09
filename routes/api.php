<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EvenementController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ReservationController;

Route::get('/events/search', [EvenementController::class, 'search']);

Route::middleware('auth:sanctum')->group(function () {

    // Messages
    Route::post('/messages/envoyer', [MessageController::class, 'envoyer']);
    Route::get('/messages/conversation/{id}', [MessageController::class, 'conversation']);

    // Events
    Route::apiResource('/events', EvenementController::class);
    Route::patch('/events/{id}/ouvrir', [EvenementController::class, 'ouvrirReservation']);
    Route::patch('/events/{id}/fermer', [EvenementController::class, 'fermerReservation']);

    // Reservations
    Route::get('/reservations/evenement/{evenement}', [ReservationController::class, 'chercherReservationParEvenement']);
    Route::get('/reservations/participant', [ReservationController::class, 'chercherReservationParParticipant']);
    Route::get('/reservations/{reservation}/montant', [ReservationController::class, 'calculerMontant']);
    Route::patch('/reservations/{reservation}/annuler', [ReservationController::class, 'annuler']);
});
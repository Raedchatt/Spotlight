<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EvenementController;
use App\Http\Controllers\MessageController;;

Route::get('/events', [EvenementController::class, 'index']);
Route::post('/events', [EvenementController::class, 'store']);
Route::put('/events/{id}', [EvenementController::class, 'update']);
Route::delete('/events/{id}', [EvenementController::class, 'destroy']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/messages/envoyer',          [MessageController::class, 'envoyer']);
    Route::get('/messages/conversation/{id}', [MessageController::class, 'conversation']);
});
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EvenementController;
use App\Http\Controllers\MessageController;
;

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/messages/envoyer', [MessageController::class, 'envoyer']);
    Route::get('/messages/conversation/{id}', [MessageController::class, 'conversation']);
});
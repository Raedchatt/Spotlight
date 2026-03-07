<?php

use Illuminate\Support\Facades\Route;

Route::get('/events', [EvenementController::class, 'index']);
Route::post('/events', [EvenementController::class, 'store']);
Route::put('/events/{id}', [EvenementController::class, 'update']);
Route::delete('/events/{id}', [EvenementController::class, 'destroy']);
<?php

// routes/api.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RoomsController;

Route::post('/rooms/issue', [RoomsController::class, 'issue']);

Route::get('/healthz', fn() => response()->json(['ok'=>true]));

Route::get('/healthz', fn() => response()->json(['ok'=>true]));

Route::get('/healthz', fn() => response()->json(['ok'=>true]));

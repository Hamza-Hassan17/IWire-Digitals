<?php

use App\Http\Controllers\Api\AppConfigController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FilmController;
use App\Http\Controllers\Api\MusicController;
use App\Http\Controllers\MediaController;
use Illuminate\Support\Facades\Route;

// Mobile app API — served under /api/v1
Route::prefix('v1')->group(function () {
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('auth/register', [AuthController::class, 'register']);
        Route::post('auth/login', [AuthController::class, 'login']);
    });
    Route::get('app/config', [AppConfigController::class, 'show']);

    // Signed, expiring URL handed out as stream_url (no bearer token needed by the player).
    Route::get('stream/{content}', [MediaController::class, 'stream'])->middleware('signed')->name('api.stream');

    Route::middleware(['auth:sanctum', 'active', 'throttle:120,1'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        Route::get('music/home', [MusicController::class, 'home']);
        Route::get('music/tracks/{track}', [MusicController::class, 'track'])->whereNumber('track');

        Route::get('films/home', [FilmController::class, 'home']);
        Route::get('films/genres', [FilmController::class, 'genres']);
        Route::get('films/genres/{genre}', [FilmController::class, 'genre'])->whereNumber('genre');
        Route::get('films/{film}', [FilmController::class, 'show'])->whereNumber('film');
    });
});

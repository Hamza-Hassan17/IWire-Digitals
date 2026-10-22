<?php

use App\Http\Controllers\Api\AppConfigController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\CreatorProfileController;
use App\Http\Controllers\Api\CreatorUploadController;
use App\Http\Controllers\Api\FilmController;
use App\Http\Controllers\Api\MusicController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\ShareApiController;
use App\Http\Controllers\MediaController;
use Illuminate\Support\Facades\Route;

// Mobile app API — served under /api/v1
Route::prefix('v1')->group(function () {
    Route::middleware('throttle:api-auth')->group(function () {
        Route::post('auth/register', [AuthController::class, 'register']);
        Route::post('auth/login', [AuthController::class, 'login']);
    });
    Route::get('app/config', [AppConfigController::class, 'show']);
    Route::get('pages/{slug}', [PageController::class, 'show'])->whereIn('slug', ['terms', 'privacy']);

    // Signed, expiring URL handed out as stream_url (no bearer token needed by the player).
    Route::get('stream/{content}', [MediaController::class, 'stream'])->middleware('signed')->name('api.stream');

    Route::middleware(['auth:sanctum', 'active', 'throttle:api'])->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        Route::get('profile', [ProfileController::class, 'show']);
        Route::match(['put', 'patch'], 'profile', [ProfileController::class, 'update']);
        Route::post('profile/avatar', [ProfileController::class, 'avatar']);
        Route::delete('profile/avatar', [ProfileController::class, 'removeAvatar']);
        Route::put('profile/password', [ProfileController::class, 'password']);
        Route::delete('profile', [ProfileController::class, 'destroy']);

        Route::get('search', [SearchController::class, 'search']);
        Route::get('creators/{creator}', [CreatorProfileController::class, 'show'])->whereNumber('creator');

        Route::get('music/home', [MusicController::class, 'home']);
        Route::get('music/genres', [MusicController::class, 'genres']);
        Route::get('music/moods', [MusicController::class, 'moods']);
        Route::get('music/tracks/{track}', [MusicController::class, 'track'])->whereNumber('track');
        Route::get('music/tracks/{id}/comments', [CommentController::class, 'index'])->whereNumber('id');
        Route::post('music/tracks/{id}/comments', [CommentController::class, 'store'])->whereNumber('id')->middleware('throttle:api-comments');
        Route::post('music/tracks/{id}/share', [ShareApiController::class, 'share'])->whereNumber('id');

        Route::get('films/home', [FilmController::class, 'home']);
        Route::get('films/genres', [FilmController::class, 'genres']);
        Route::get('films/genres/{genre}', [FilmController::class, 'genre'])->whereNumber('genre');
        Route::get('films/{film}', [FilmController::class, 'show'])->whereNumber('film');
        Route::get('films/{id}/comments', [CommentController::class, 'index'])->whereNumber('id');
        Route::post('films/{id}/comments', [CommentController::class, 'store'])->whereNumber('id')->middleware('throttle:api-comments');
        Route::post('films/{id}/share', [ShareApiController::class, 'share'])->whereNumber('id');

        Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->whereNumber('comment');

        Route::prefix('creator')->group(function () {
            Route::get('stats', [CreatorUploadController::class, 'stats']);
            Route::get('uploads', [CreatorUploadController::class, 'index']);
            Route::post('uploads', [CreatorUploadController::class, 'store'])->middleware('throttle:api-uploads');
            Route::get('uploads/{upload}', [CreatorUploadController::class, 'show'])->whereNumber('upload');
            Route::delete('uploads/{upload}', [CreatorUploadController::class, 'destroy'])->whereNumber('upload');
        });
    });
});

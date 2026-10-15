<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/context', [Admin\ShellController::class, 'context'])->name('context');
    Route::get('/search', [Admin\ShellController::class, 'search'])->name('search');

    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/queue', [Admin\ReviewController::class, 'index'])->name('queue.index');
    Route::post('/queue/bulk', [Admin\ReviewController::class, 'bulk'])->name('queue.bulk');
    Route::get('/review/{content}', [Admin\ReviewController::class, 'show'])->name('review.show');
    Route::post('/review/{content}/approve', [Admin\ReviewController::class, 'approve'])->name('review.approve');
    Route::post('/review/{content}/reject', [Admin\ReviewController::class, 'reject'])->name('review.reject');

    Route::get('/library', [Admin\LibraryController::class, 'index'])->name('library.index');
    Route::get('/library/{content}', [Admin\LibraryController::class, 'show'])->name('library.show');
    Route::put('/library/{content}', [Admin\LibraryController::class, 'update'])->name('library.update');
    Route::post('/library/{content}/publish', [Admin\LibraryController::class, 'togglePublish'])->name('library.publish');
    Route::post('/library/{content}/cover', [Admin\LibraryController::class, 'cover'])->name('library.cover');
    Route::delete('/library/{content}', [Admin\LibraryController::class, 'destroy'])->name('library.destroy');

    Route::get('/curation', [Admin\CurationController::class, 'index'])->name('curation.index');
    Route::post('/curation', [Admin\CurationController::class, 'save'])->name('curation.save');

    Route::get('/genres', [Admin\GenreController::class, 'index'])->name('genres.index');
    Route::post('/genres', [Admin\GenreController::class, 'store'])->name('genres.store');
    Route::post('/genres/reorder', [Admin\GenreController::class, 'reorder'])->name('genres.reorder');
    Route::put('/genres/{genre}', [Admin\GenreController::class, 'update'])->name('genres.update');
    Route::delete('/genres/{genre}', [Admin\GenreController::class, 'destroy'])->name('genres.destroy');

    Route::get('/listeners', [Admin\ListenerController::class, 'index'])->name('listeners.index');
    Route::get('/listeners/export', [Admin\ListenerController::class, 'export'])->name('listeners.export');
    Route::post('/listeners/{listener}/suspend', [Admin\ListenerController::class, 'toggleSuspend'])->name('listeners.suspend');
    Route::delete('/listeners/{listener}', [Admin\ListenerController::class, 'destroy'])->name('listeners.destroy');

    Route::get('/creators', [Admin\CreatorController::class, 'index'])->name('creators.index');
    Route::get('/creators/{creator}', [Admin\CreatorController::class, 'show'])->name('creators.show');
    Route::post('/creators/{creator}/suspend', [Admin\CreatorController::class, 'toggleSuspend'])->name('creators.suspend');

    Route::get('/reports', [Admin\ReportController::class, 'index'])->name('reports.index');
    Route::post('/reports/{report}/resolve', [Admin\ReportController::class, 'resolve'])->name('reports.resolve');
    Route::post('/reports/{report}/dismiss', [Admin\ReportController::class, 'dismiss'])->name('reports.dismiss');

    Route::get('/comments', [Admin\CommentController::class, 'index'])->name('comments.index');
    Route::post('/comments/{comment}/visibility', [Admin\CommentController::class, 'toggle'])->name('comments.toggle');
    Route::delete('/comments/{comment}', [Admin\CommentController::class, 'destroy'])->name('comments.destroy');

    Route::get('/push', [Admin\PushController::class, 'index'])->name('push.index');
    Route::post('/push', [Admin\PushController::class, 'store'])->name('push.store');

    Route::get('/analytics', [Admin\AnalyticsController::class, 'index'])->name('analytics');
    Route::get('/activity', [Admin\ActivityController::class, 'index'])->name('activity');

    Route::get('/settings', [Admin\SettingsController::class, 'index'])->name('settings');
    Route::put('/settings/profile', [Admin\SettingsController::class, 'profile'])->name('settings.profile');
    Route::put('/settings/password', [Admin\SettingsController::class, 'password'])->name('settings.password');
    Route::put('/settings/platform', [Admin\SettingsController::class, 'platform'])->name('settings.platform');
});

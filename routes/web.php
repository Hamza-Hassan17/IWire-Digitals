<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MediaController;
use Illuminate\Support\Facades\Route;

// Generated cover / banner art as PNG (used by the mobile API until real artwork is uploaded).
Route::get('/media/art/{size}/{art}.png', [MediaController::class, 'art'])->name('media.art');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'show'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

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
    Route::post('/library/{content}/media', [Admin\LibraryController::class, 'media'])->name('library.media');
    Route::delete('/library/{content}', [Admin\LibraryController::class, 'destroy'])->name('library.destroy');
    Route::get('/media/preview/{content}', [MediaController::class, 'preview'])->name('media.preview');

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

    Route::get('/moderation', [Admin\ModerationController::class, 'index'])->name('moderation');
    Route::redirect('/reports', '/moderation?tab=reports');
    Route::redirect('/comments', '/moderation?tab=comments');
    Route::post('/reports/{report}/resolve', [Admin\ReportController::class, 'resolve'])->name('reports.resolve');
    Route::post('/reports/{report}/dismiss', [Admin\ReportController::class, 'dismiss'])->name('reports.dismiss');

    Route::post('/comments/{comment}/visibility', [Admin\CommentController::class, 'toggle'])->name('comments.toggle');
    Route::delete('/comments/{comment}', [Admin\CommentController::class, 'destroy'])->name('comments.destroy');

    Route::get('/push', [Admin\PushController::class, 'index'])->name('push.index');
    Route::post('/push', [Admin\PushController::class, 'store'])->name('push.store');

    Route::get('/analytics', [Admin\AnalyticsController::class, 'index'])->name('analytics');

    Route::get('/settings', [Admin\SettingsController::class, 'index'])->name('settings');
    Route::put('/settings/profile', [Admin\SettingsController::class, 'profile'])->name('settings.profile');
    Route::put('/settings/password', [Admin\SettingsController::class, 'password'])->name('settings.password');
    Route::put('/settings/platform', [Admin\SettingsController::class, 'platform'])->name('settings.platform');
    Route::post('/settings/app', [Admin\SettingsController::class, 'app'])->name('settings.app');
    Route::put('/settings/pages', [Admin\SettingsController::class, 'pages'])->name('settings.pages');
});

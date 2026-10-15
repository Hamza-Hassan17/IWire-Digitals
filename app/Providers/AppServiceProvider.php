<?php

namespace App\Providers;

use App\Models\Content;
use App\Models\Creator;
use App\Models\PushNotification;
use App\Models\Report;
use App\Support\Iw;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Keep indexed utf8mb4 strings under the 1000-byte key limit of older MySQL/MyISAM hosts.
        Schema::defaultStringLength(191);

        View::composer('*', function ($view) {
            $view->with('ctx', session('ctx', 'All'));
        });

        View::composer('layouts.admin', function ($view) {
            $pending = Content::pending()->count();
            $openReports = Report::where('status', 'Open')->count();

            $nav = [
                'MAIN' => [
                    ['route' => 'dashboard', 'match' => ['dashboard'], 'label' => 'Dashboard', 'icon' => 'space_dashboard'],
                    ['route' => 'queue.index', 'match' => ['queue.', 'review.'], 'label' => 'Review Queue', 'icon' => 'inventory', 'badge' => $pending],
                    ['route' => 'reports.index', 'match' => ['reports.'], 'label' => 'Reports & Flags', 'icon' => 'flag', 'badge' => $openReports],
                ],
                'CONTENT' => [
                    ['route' => 'library.index', 'match' => ['library.'], 'label' => 'Library', 'icon' => 'video_library'],
                    ['route' => 'curation.index', 'match' => ['curation.'], 'label' => 'Home Curation', 'icon' => 'dashboard_customize'],
                    ['route' => 'genres.index', 'match' => ['genres.'], 'label' => 'Genres & Moods', 'icon' => 'category'],
                ],
                'PEOPLE' => [
                    ['route' => 'listeners.index', 'match' => ['listeners.'], 'label' => 'Listeners', 'icon' => 'headphones'],
                    ['route' => 'creators.index', 'match' => ['creators.'], 'label' => 'Creators', 'icon' => 'mic_external_on'],
                    ['route' => 'comments.index', 'match' => ['comments.'], 'label' => 'Comments', 'icon' => 'forum'],
                ],
                'PLATFORM' => [
                    ['route' => 'push.index', 'match' => ['push.'], 'label' => 'Push Notifications', 'icon' => 'campaign'],
                    ['route' => 'analytics', 'match' => ['analytics'], 'label' => 'Analytics', 'icon' => 'monitoring'],
                ],
                'SYSTEM' => [
                    ['route' => 'activity', 'match' => ['activity'], 'label' => 'Activity Log', 'icon' => 'history'],
                    ['route' => 'settings', 'match' => ['settings'], 'label' => 'Settings', 'icon' => 'settings'],
                ],
            ];

            $notifications = collect();
            $fresh = Content::pending()->where('submitted_at', '>=', now()->subDay())->count();
            if ($fresh) {
                $notifications->push(['icon' => 'inventory', 'color' => '#6CC4FF', 'bg' => 'rgba(10,155,255,.14)', 'text' => "$fresh new submissions in Review Queue", 'time' => Content::pending()->max('submitted_at') ? \Illuminate\Support\Carbon::parse(Content::pending()->max('submitted_at'))->diffForHumans() : '', 'unread' => true, 'url' => route('queue.index')]);
            }
            if ($r = Report::where('status', 'Open')->orderByDesc('count')->first()) {
                $notifications->push(['icon' => 'flag', 'color' => '#FBA26A', 'bg' => 'rgba(249,128,52,.15)', 'text' => "“{$r->label}” reported {$r->count} times for ".strtolower($r->reason), 'time' => $r->created_at->diffForHumans(), 'unread' => true, 'url' => route('reports.index')]);
            }
            if ($old = Content::pending()->orderBy('submitted_at')->first()) {
                if ($old->wait_hours >= 48) {
                    $notifications->push(['icon' => 'schedule', 'color' => '#F5C46B', 'bg' => 'rgba(245,176,65,.14)', 'text' => "“{$old->title}” has been waiting over 48 hrs", 'time' => Iw::waitShort($old->wait_hours).' in queue', 'unread' => true, 'url' => route('review.show', $old)]);
                }
            }
            $newCreators = Creator::where('created_at', '>=', now()->subWeek())->count();
            if ($newCreators) {
                $notifications->push(['icon' => 'mic_external_on', 'color' => '#5FD99A', 'bg' => 'rgba(52,199,123,.14)', 'text' => "$newCreators creators on the platform this week", 'time' => 'This week', 'unread' => false, 'url' => route('creators.index')]);
            }
            if ($p = PushNotification::where('status', 'Scheduled')->orderBy('send_at')->first()) {
                $notifications->push(['icon' => 'campaign', 'color' => '#A7B4C6', 'bg' => 'rgba(148,163,184,.14)', 'text' => "Scheduled push “{$p->title}” goes out ".$p->send_at->format('M j'), 'time' => 'Upcoming', 'unread' => false, 'url' => route('push.index')]);
            }

            $view->with(compact('nav', 'notifications'));
        });
    }
}

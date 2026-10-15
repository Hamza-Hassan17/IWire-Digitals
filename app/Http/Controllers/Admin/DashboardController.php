<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Content;
use App\Models\Creator;
use App\Models\Listener;
use App\Models\Report;
use App\Support\Iw;

class DashboardController extends Controller
{
    public function index()
    {
        $ctx = session('ctx', 'All');
        $showMusic = $ctx !== 'Films';
        $showFilms = $ctx !== 'Music';

        $live = fn ($type) => Content::where('type', $type)->where('status', 'Live');
        $pending = Content::pending()->ctx($ctx)->with('creator')->orderBy('submitted_at')->get();
        $over36 = $pending->filter(fn ($c) => $c->wait_hours >= 36)->count();

        $streams = (int) Content::library()->ctx($ctx)->sum('plays');
        $kpi = function ($label, $icon, $value, $delta, $up, $seed, $base, $route) {
            $d = Iw::series(14, $seed, $base, $base * .18, $base * .02);

            return ['label' => $label, 'icon' => $icon, 'value' => $value, 'delta' => $delta, 'deltaColor' => $up ? '#5FD99A' : '#F38A8A',
                'iconColor' => '#0A9BFF', 'stroke' => '#0A9BFF', 'fill' => 'rgba(10,155,255,.12)',
                'line' => Iw::path($d, 100, 30), 'area' => Iw::area($d, 100, 30), 'url' => route($route)];
        };
        $kpis = [
            $kpi('Total streams', 'play_circle', Iw::k($streams), '+12.4%', true, 1, 60, 'analytics'),
            $kpi('Active listeners', 'headphones', Iw::k(Listener::where('status', 'Active')->count()), '+6.1%', true, 2, 40, 'listeners.index'),
            $kpi('Live tracks', 'graphic_eq', number_format($live('Music')->count()), '+'.Content::where('type', 'Music')->where('status', 'Live')->where('reviewed_at', '>=', now()->subWeek())->count(), true, 3, 30, 'library.index'),
            $kpi('Live films', 'movie', number_format($live('Films')->count()), '+'.Content::where('type', 'Films')->where('status', 'Live')->where('reviewed_at', '>=', now()->subWeek())->count(), true, 4, 20, 'library.index'),
            $kpi('Creators', 'mic_external_on', number_format(Creator::count()), Creator::where('status', 'Suspended')->count().' suspended', false, 5, 30, 'creators.index'),
            array_merge($kpi('Pending reviews', 'inventory', (string) $pending->count(), "$over36 over 36h", false, 6, 20, 'queue.index'),
                ['iconColor' => '#F5C46B', 'stroke' => '#F5B041', 'fill' => 'rgba(245,176,65,.12)', 'deltaColor' => '#F5C46B']),
        ];

        $max = 160000;
        $mS = Iw::series(30, 1, 95000, 14000, 900);
        $fS = Iw::series(30, 4, 34000, 9000, 500);
        $chart = [
            'mLine' => Iw::path($mS, 600, 226, $max), 'mArea' => Iw::area($mS, 600, 226, $max),
            'fLine' => Iw::path($fS, 600, 226, $max), 'fArea' => Iw::area($fS, 600, 226, $max),
            'mTotal' => Iw::k(array_sum($mS)), 'fTotal' => Iw::k(array_sum($fS)),
            'labels' => collect([29, 22, 15, 8, 0])->map(fn ($d) => now()->subDays($d)->format('M j')),
        ];

        $oldest = $pending->take(4);
        $openReports = Report::where('status', 'Open')->orderByDesc('count')->take(3)->get();
        $attention = $pending->count() + Report::where('status', 'Open')->count();

        $topTracks = Content::with('creator')->where('type', 'Music')->where('status', 'Live')->orderByDesc('plays')->take(5)->get();
        $topFilms = Content::with('creator')->where('type', 'Films')->where('status', 'Live')->orderByDesc('plays')->take(5)->get();
        $activity = Activity::when($ctx !== 'All', fn ($q) => $q->whereIn('context', [$ctx, 'All']))->latest()->take(6)->get();

        $oldestWait = $pending->first()?->wait_hours ?? 0;

        return view('admin.dashboard', compact('ctx', 'showMusic', 'showFilms', 'kpis', 'chart', 'oldest', 'openReports', 'attention', 'topTracks', 'topFilms', 'activity', 'pending', 'oldestWait'));
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\Genre;
use App\Support\Iw;

class AnalyticsController extends Controller
{
    public function index()
    {
        $ctx = session('ctx', 'All');
        $lib = Content::library()->ctx($ctx);

        $mS = Iw::series(90, 1, 95000, 14000, 300);
        $fS = Iw::series(90, 4, 34000, 9000, 180);
        $max = 160000;

        $byGenre = (clone $lib)->selectRaw('type, genre, sum(plays) plays, count(*) n')->groupBy('type', 'genre')->orderByDesc('plays')->get();
        $colors = Genre::pluck('color', 'name');
        $topGenre = max(1, (int) $byGenre->max('plays'));

        $status = (clone $lib)->selectRaw('status, count(*) n')->groupBy('status')->pluck('n', 'status');
        $top = (clone $lib)->with('creator')->orderByDesc('plays')->take(10)->get();

        $totals = [
            ['play_circle', 'Total plays', Iw::k((clone $lib)->sum('plays'))],
            ['share', 'Shares', Iw::k((clone $lib)->sum('shares'))],
            ['chat_bubble', 'Comments', Iw::k((clone $lib)->sum('comments_count'))],
            ['check_circle', 'Approval rate', (function () use ($ctx) {
                $rev = Content::ctx($ctx)->whereNotNull('reviewed_at');
                $n = (clone $rev)->count();

                return $n ? round((clone $rev)->where('status', '!=', 'Rejected')->count() / $n * 100).'%' : '—';
            })()],
        ];

        return view('admin.analytics', [
            'ctx' => $ctx, 'totals' => $totals, 'byGenre' => $byGenre, 'colors' => $colors, 'topGenre' => $topGenre, 'status' => $status, 'top' => $top,
            'mLine' => Iw::path($mS, 600, 226, $max), 'mArea' => Iw::area($mS, 600, 226, $max),
            'fLine' => Iw::path($fS, 600, 226, $max), 'fArea' => Iw::area($fS, 600, 226, $max),
            'labels' => collect([89, 67, 45, 22, 0])->map(fn ($d) => now()->subDays($d)->format('M j')),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Content;
use App\Models\Creator;
use App\Models\Listener;
use App\Models\Report;
use App\Support\Iw;

class DashboardController extends Controller
{
    public function index()
    {
        $pending = Content::pending()->with('creator')->orderBy('submitted_at')->get();
        $reports = Report::where('status', 'Open')->orderByDesc('count')->get();
        $reportedComments = Comment::where('status', 'Reported')->count();

        $stats = [
            ['inventory', 'Waiting for review', $pending->count(), route('queue.index'), $pending->count() ? '#F5C46B' : '#0A9BFF'],
            ['shield', 'Needs moderation', $reports->count() + $reportedComments, route('moderation'), $reports->count() + $reportedComments ? '#FBA26A' : '#0A9BFF'],
            ['graphic_eq', 'Live tracks', Content::where('type', 'Music')->where('status', 'Live')->count(), route('library.index', ['tab' => 'Music']), '#0A9BFF'],
            ['movie', 'Live films', Content::where('type', 'Films')->where('status', 'Live')->count(), route('library.index', ['tab' => 'Films']), '#0A9BFF'],
            ['headphones', 'Listeners', Listener::count(), route('listeners.index'), '#0A9BFF'],
            ['mic_external_on', 'Creators', Creator::count(), route('creators.index'), '#0A9BFF'],
        ];

        return view('admin.dashboard', [
            'stats' => $stats,
            'pending' => $pending->take(6),
            'pendingTotal' => $pending->count(),
            'oldestWait' => $pending->first() ? Iw::waitShort($pending->first()->wait_hours) : null,
            'reports' => $reports->take(6),
        ]);
    }
}

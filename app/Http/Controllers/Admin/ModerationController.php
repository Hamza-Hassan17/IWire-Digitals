<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Content;
use App\Models\Report;
use Illuminate\Http\Request;

/** Reports and comments in one place. */
class ModerationController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab') === 'comments' ? 'comments' : 'reports';
        $openReports = Report::where('status', 'Open')->count();
        $reportedComments = Comment::where('status', 'Reported')->count();

        if ($tab === 'reports') {
            $status = in_array($request->query('status'), ['Open', 'Closed']) ? $request->query('status') : 'Open';
            $reports = Report::with(['content.creator', 'comment'])
                ->when($status === 'Open', fn ($q) => $q->where('status', 'Open'), fn ($q) => $q->whereIn('status', ['Resolved', 'Dismissed']))
                ->orderByDesc($status === 'Open' ? 'count' : 'updated_at')->get();

            return view('admin.moderation', compact('tab', 'openReports', 'reportedComments', 'status', 'reports'));
        }

        $status = in_array($request->query('status'), ['Reported', 'Hidden']) ? $request->query('status') : 'All';
        $content = $request->query('content') ? Content::find($request->query('content')) : null;
        $comments = Comment::with(['user', 'listener', 'content'])
            ->when($status !== 'All', fn ($w) => $w->where('status', $status))
            ->when($content, fn ($w) => $w->where('content_id', $content->id))
            ->latest()->paginate(20)->withQueryString();

        return view('admin.moderation', compact('tab', 'openReports', 'reportedComments', 'status', 'comments', 'content'));
    }
}

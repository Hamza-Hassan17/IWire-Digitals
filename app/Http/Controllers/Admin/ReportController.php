<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $status = in_array($request->query('status'), ['Open', 'Resolved', 'Dismissed', 'All']) ? $request->query('status') : 'Open';
        $counts = Report::selectRaw('status, count(*) n')->groupBy('status')->pluck('n', 'status');
        $reports = Report::with(['content.creator', 'comment'])
            ->when($status !== 'All', fn ($q) => $q->where('status', $status))
            ->orderByRaw("status = 'Open' desc")->orderByDesc('count')->get();

        return view('admin.reports', compact('reports', 'status', 'counts'));
    }

    /**
     * Resolve = take action on the reported item: unpublish content, or hide the comment.
     */
    public function resolve(Report $report)
    {
        if ($report->content && $report->content->status === 'Live') {
            $report->content->update(['status' => 'Unpublished']);
        }
        if ($report->comment) {
            $report->comment->update(['status' => 'Hidden']);
        }
        $report->update(['status' => 'Resolved']);
        $action = $report->comment ? 'comment hidden' : ($report->content ? 'content unpublished' : 'resolved');
        Activity::log('flag', '#FBA26A', 'Report resolved', "“{$report->label}” — $action", $report->kind === 'Film' ? 'Films' : ($report->kind === 'Track' ? 'Music' : 'All'));

        return back()->with('toast', ["Report resolved — $action", 'ok']);
    }

    public function dismiss(Report $report)
    {
        $report->update(['status' => 'Dismissed']);
        if ($report->content && $report->content->status === 'Flagged') {
            $report->content->update(['status' => 'Live']);
        }
        if ($report->comment && $report->comment->status === 'Reported') {
            $report->comment->update(['status' => 'Visible']);
        }

        return back()->with('toast', ['Report dismissed', 'info']);
    }
}

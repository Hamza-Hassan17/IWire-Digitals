<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Content;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request)
    {
        $status = in_array($request->query('status'), ['Visible', 'Reported', 'Hidden']) ? $request->query('status') : 'All';
        $q = (string) $request->query('q', '');
        $content = $request->query('content') ? Content::find($request->query('content')) : null;

        $comments = Comment::with(['listener', 'content'])
            ->when($status !== 'All', fn ($w) => $w->where('status', $status))
            ->when($content, fn ($w) => $w->where('content_id', $content->id))
            ->when($q !== '', fn ($w) => $w->where('body', 'like', "%$q%"))
            ->latest()->paginate(20)->withQueryString();
        $counts = Comment::selectRaw('status, count(*) n')->groupBy('status')->pluck('n', 'status');

        return view('admin.comments', compact('comments', 'status', 'q', 'counts', 'content'));
    }

    public function toggle(Comment $comment)
    {
        $hide = $comment->status !== 'Hidden';
        $comment->update(['status' => $hide ? 'Hidden' : 'Visible']);

        return back()->with('toast', [$hide ? 'Comment hidden' : 'Comment visible again', $hide ? 'info' : 'ok']);
    }

    public function destroy(Comment $comment)
    {
        $comment->delete();

        return back()->with('toast', ['Comment deleted', 'bad']);
    }
}

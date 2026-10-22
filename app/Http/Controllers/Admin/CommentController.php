<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;

/** Comment actions used by the Moderation page. */
class CommentController extends Controller
{
    public function toggle(Comment $comment)
    {
        $hide = $comment->status !== 'Hidden';
        $comment->update(['status' => $hide ? 'Hidden' : 'Visible']);

        return back()->with('toast', [$hide ? 'Comment hidden — removed from the app' : 'Comment visible again', $hide ? 'info' : 'ok']);
    }

    public function destroy(Comment $comment)
    {
        $comment->content?->decrement('comments_count');
        $comment->delete();

        return back()->with('toast', ['Comment deleted', 'bad']);
    }
}

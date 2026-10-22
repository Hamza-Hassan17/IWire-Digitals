<?php

namespace App\Http\Controllers\Api;

use App\Models\Comment;
use App\Models\Content;
use Illuminate\Http\Request;

/**
 * Comments on songs and films. Everything posted here shows up in the control room's
 * Moderation page; comments an admin hides disappear from the app.
 */
class CommentController extends ApiController
{
    public function index(Request $request, int $id)
    {
        $content = $this->content($request, $id);
        $page = Comment::with(['user', 'listener'])->where('content_id', $content->id)
            ->whereIn('status', ['Visible', 'Reported'])->latest()->paginate(20);
        $me = $request->user()->id;

        return $this->paginated($page, fn ($c) => self::payload($c, $me));
    }

    public function store(Request $request, int $id)
    {
        $content = $this->content($request, $id);
        $data = $request->validate(['body' => 'required|string|min:1|max:1000']);
        $user = $request->user();

        $comment = Comment::create([
            'user_id' => $user->id,
            'listener_id' => $user->listener?->id,
            'content_id' => $content->id,
            'body' => trim($data['body']),
            'status' => 'Visible',
        ]);
        $content->increment('comments_count');

        return $this->ok(self::payload($comment->load('user'), $user->id), 'Comment posted', 201);
    }

    public function destroy(Request $request, int $comment)
    {
        $c = Comment::where('user_id', $request->user()->id)->findOrFail($comment);
        $c->content?->decrement('comments_count');
        $c->delete();

        return $this->ok(null, 'Comment deleted');
    }

    /** Comments are only open on live items; the route decides whether it's a track or a film. */
    private function content(Request $request, int $id): Content
    {
        $type = $request->is('api/v1/music/*') ? 'Music' : 'Films';

        return Content::where('type', $type)->where('status', 'Live')->findOrFail($id);
    }

    public static function payload(Comment $c, int $me): array
    {
        return [
            'id' => $c->id,
            'body' => $c->body,
            'created_at' => $c->created_at->toIso8601String(),
            'time_ago' => $c->created_at->diffForHumans(),
            'is_mine' => $c->user_id === $me,
            'user' => ['id' => $c->user_id, 'name' => $c->author_name, 'avatar' => $c->author_avatar],
        ];
    }
}

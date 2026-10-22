<?php

namespace App\Http\Controllers\Api;

use App\Models\Content;
use App\Models\Creator;

/** Public creator profiles (opened from search results, track artist, film creator). */
class CreatorProfileController extends ApiController
{
    public function show(int $creator)
    {
        $c = Creator::with('user')->where('status', 'Active')->findOrFail($creator);
        $live = Content::where('creator_id', $c->id)->where('status', 'Live')->orderByDesc('plays')->get();

        return $this->ok(array_merge(self::card($c), [
            'bio' => $c->user?->bio,
            'total_plays' => (int) $c->total_plays,
            'tracks' => $live->where('type', 'Music')->values()->map(fn ($t) => [
                'id' => $t->id, 'title' => $t->title, 'cover' => $t->cover_url, 'genre' => $t->genre, 'duration' => $t->duration_seconds,
            ]),
            'films' => $live->where('type', 'Films')->values()->map(fn ($f) => FilmController::item($f)),
        ]));
    }

    public static function card(Creator $c): array
    {
        return [
            'id' => $c->id,
            'name' => $c->name,
            'type' => $c->type === 'Filmmaker' ? 'filmmaker' : 'musician',
            'avatar' => $c->user?->avatar_url,
            'uploads' => (int) ($c->uploads ?? $c->contents()->where('status', 'Live')->count()),
        ];
    }
}

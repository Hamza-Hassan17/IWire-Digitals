<?php

namespace App\Http\Controllers\Api;

use App\Models\Content;
use App\Models\CurationRow;
use Illuminate\Support\Str;

class MusicController extends ApiController
{
    /** Sections come straight from the control room's Home Curation → Music home. */
    public function home()
    {
        $rows = CurationRow::where('home', 'Music')->orderBy('position')->get();
        $live = Content::with('creator')->where('type', 'Music')->where('status', 'Live')
            ->whereIn('id', $rows->pluck('content_ids')->flatten()->unique())->get()->keyBy('id');

        $sections = $rows->map(fn ($r) => [
            'key' => Str::snake(preg_replace('/[^A-Za-z0-9 ]/', '', $r->name)),
            'title' => $r->name,
            'items' => collect($r->content_ids)->filter(fn ($id) => $live->has($id))->map(fn ($id) => [
                'id' => $live[$id]->id,
                'title' => $live[$id]->title,
                'subtitle' => Str::limit((string) $live[$id]->description, 80),
                'cover' => $live[$id]->cover_url,
                'artist' => $live[$id]->creator->name,
            ])->values(),
        ])->filter(fn ($s) => $s['items']->isNotEmpty())->values();

        return $this->ok(['sections' => $sections]);
    }

    public function track(int $track)
    {
        $t = Content::with('creator')->where('type', 'Music')->where('status', 'Live')->findOrFail($track);

        return $this->ok([
            'id' => $t->id,
            'title' => $t->title,
            'description' => $t->description,
            'artist' => $t->creator->name,
            'cover' => $t->cover_url,
            'genre' => $t->genre,
            'mood' => $t->mood,
            'explicit' => $t->explicit,
            'duration' => $t->duration_seconds,
            'stream_url' => $t->streamUrl(),
        ]);
    }
}

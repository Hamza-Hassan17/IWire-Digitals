<?php

namespace App\Http\Controllers\Api;

use App\Models\Content;
use App\Models\Creator;
use Illuminate\Http\Request;

class SearchController extends ApiController
{
    /** One call for the search screen: songs, films and creators matching the query. */
    public function search(Request $request)
    {
        $data = $request->validate([
            'q' => 'required|string|min:2|max:100',
            'type' => 'nullable|in:all,music,films,creators',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);
        $q = trim($data['q']);
        $type = $data['type'] ?? 'all';
        $limit = $data['limit'] ?? 10;
        $like = '%'.addcslashes($q, '%_\\').'%';

        $content = fn (string $t) => Content::with('creator')->where('type', $t)->where('status', 'Live')
            ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('genre', 'like', $like)
                ->orWhereHas('creator', fn ($c) => $c->where('name', 'like', $like)))
            ->orderByDesc('plays')->limit($limit)->get();

        $out = ['query' => $q];
        if (in_array($type, ['all', 'music'])) {
            $out['tracks'] = $content('Music')->map(fn ($t) => [
                'id' => $t->id, 'title' => $t->title, 'artist' => $t->creator->name, 'cover' => $t->cover_url,
                'genre' => $t->genre, 'duration' => $t->duration_seconds,
            ]);
        }
        if (in_array($type, ['all', 'films'])) {
            $out['films'] = $content('Films')->map(fn ($f) => FilmController::item($f));
        }
        if (in_array($type, ['all', 'creators'])) {
            $out['creators'] = Creator::with('user')->where('status', 'Active')->where('name', 'like', $like)
                ->withCount(['contents as uploads' => fn ($c) => $c->where('status', 'Live')])
                ->orderByDesc('total_plays')->limit($limit)->get()->map(fn ($c) => CreatorProfileController::card($c));
        }

        return $this->ok($out);
    }
}

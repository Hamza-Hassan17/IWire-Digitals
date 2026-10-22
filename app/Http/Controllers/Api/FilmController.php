<?php

namespace App\Http\Controllers\Api;

use App\Models\Content;
use App\Models\CurationRow;
use App\Models\Genre;
use App\Models\HeroSlide;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FilmController extends ApiController
{
    public function home(Request $request)
    {
        $tab = $request->validate(['tab' => 'nullable|in:for_you,new,trending'])['tab'] ?? 'for_you';
        $live = fn () => Content::where('type', 'Films')->where('status', 'Live');

        $banners = HeroSlide::with('content')->orderBy('position')->get()
            ->filter(fn ($h) => $h->content && $h->content->status === 'Live')
            ->map(fn ($h) => [
                'id' => $h->content->id,
                'title' => $h->content->title,
                'subtitle' => $h->tagline,
                'image' => $h->content->banner_url,
            ])->values();

        $sections = match ($tab) {
            'new' => collect([['key' => 'new_releases', 'title' => 'New Releases',
                'items' => $live()->orderByDesc('reviewed_at')->limit(20)->get()->map(fn ($f) => self::item($f))]]),
            'trending' => collect([['key' => 'trending', 'title' => 'Trending',
                'items' => $live()->orderByDesc('plays')->limit(20)->get()->map(fn ($f) => self::item($f))]]),
            default => $this->curated($live),
        };

        return $this->ok(['banners' => $banners, 'sections' => $sections]);
    }

    /** "For You" sections come from the control room's Home Curation → Films home. */
    private function curated(callable $live)
    {
        $rows = CurationRow::where('home', 'Films')->orderBy('position')->get();
        $films = $live()->whereIn('id', $rows->pluck('content_ids')->flatten()->unique())->get()->keyBy('id');

        return $rows->map(fn ($r) => [
            'key' => Str::snake(preg_replace('/[^A-Za-z0-9 ]/', '', $r->name)),
            'title' => $r->name,
            'items' => collect($r->content_ids)->filter(fn ($id) => $films->has($id))->map(fn ($id) => self::item($films[$id]))->values(),
        ])->filter(fn ($s) => $s['items']->isNotEmpty())->values();
    }

    public function genres()
    {
        return $this->ok(Genre::where('kind', 'films')->orderBy('position')->get()->map(fn ($g) => ['id' => $g->id, 'name' => $g->name]));
    }

    public function genre(int $genre)
    {
        $g = Genre::where('kind', 'films')->findOrFail($genre);
        $page = Content::where('type', 'Films')->where('status', 'Live')->where('genre', $g->name)
            ->orderByDesc('plays')->paginate(20);

        return $this->paginated($page, fn ($f) => self::item($f), $g->name);
    }

    public function show(int $film)
    {
        $f = Content::with('creator')->where('type', 'Films')->where('status', 'Live')->findOrFail($film);

        return $this->ok([
            'id' => $f->id,
            'title' => $f->title,
            'description' => $f->description,
            'poster' => $f->cover_url,
            'banner' => $f->banner_url,
            'genre' => $f->genre,
            'duration' => $f->duration_seconds,
            'creator' => $f->creator->name,
            'explicit' => $f->explicit,
            'stream_url' => $f->streamUrl(),
            'share_url' => $f->share_url,
        ]);
    }

    public static function item(Content $f): array
    {
        return ['id' => $f->id, 'title' => $f->title, 'poster' => $f->cover_url, 'genre' => $f->genre];
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Content;
use App\Models\CurationRow;
use App\Models\HeroSlide;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CurationController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'Music');
        $tab = in_array($tab, ['Music', 'Films']) ? $tab : 'Music';

        $published = [
            'rows' => CurationRow::where('home', $tab)->orderBy('position')->get()->map(fn ($r) => ['name' => $r->name, 'ids' => $r->content_ids])->values(),
            'hero' => $tab === 'Films' ? HeroSlide::orderBy('position')->get()->map(fn ($h) => ['id' => $h->content_id, 'tagline' => $h->tagline])->values() : [],
        ];
        $draft = Setting::get("curation.draft.$tab");

        $pool = Content::with('creator')->where('type', $tab)->where('status', 'Live')->orderByDesc('plays')->get()
            ->mapWithKeys(fn ($c) => [$c->id => ['id' => $c->id, 'title' => $c->title, 'creator' => $c->creator->name, 'genre' => $c->genre, 'playsK' => $c->plays_k, 'art' => $c->art]]);

        // Drop ids that are no longer live (deleted / unpublished) so the editor never shows ghosts.
        $clean = function ($state) use ($pool) {
            $state['rows'] = collect($state['rows'])->map(fn ($r) => ['name' => $r['name'], 'ids' => array_values(array_filter($r['ids'], fn ($id) => isset($pool[$id])))])->values();
            $state['hero'] = collect($state['hero'] ?? [])->filter(fn ($h) => isset($pool[$h['id']]))->values();

            return $state;
        };

        return view('admin.curation', [
            'tab' => $tab,
            'state' => $clean($draft ?? $published),
            'hasDraft' => (bool) $draft,
            'publishedAt' => Setting::get("curation.published.$tab"),
            'pool' => $pool,
        ]);
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'tab' => 'required|in:Music,Films',
            'mode' => 'required|in:draft,publish',
            'payload' => 'required|json',
        ]);
        $tab = $data['tab'];
        $state = json_decode($data['payload'], true);
        $live = Content::where('type', $tab)->where('status', 'Live')->pluck('id')->flip();

        $rows = collect($state['rows'] ?? [])->map(fn ($r) => [
            'name' => mb_substr(trim((string) ($r['name'] ?? '')) ?: 'Untitled row', 0, 80),
            'ids' => array_values(array_unique(array_filter(array_map('intval', $r['ids'] ?? []), fn ($id) => isset($live[$id])))),
        ])->values()->all();
        $hero = collect($state['hero'] ?? [])->filter(fn ($h) => isset($live[(int) ($h['id'] ?? 0)]))
            ->map(fn ($h) => ['id' => (int) $h['id'], 'tagline' => mb_substr((string) ($h['tagline'] ?? ''), 0, 140)])->values()->all();

        if ($data['mode'] === 'draft') {
            Setting::put("curation.draft.$tab", ['rows' => $rows, 'hero' => $hero]);

            return redirect()->route('curation.index', ['tab' => $tab])->with('toast', ['Draft saved', 'info']);
        }

        DB::transaction(function () use ($tab, $rows, $hero) {
            CurationRow::where('home', $tab)->delete();
            foreach ($rows as $i => $r) {
                CurationRow::create(['home' => $tab, 'name' => $r['name'], 'position' => $i, 'content_ids' => $r['ids']]);
            }
            if ($tab === 'Films') {
                HeroSlide::query()->delete();
                foreach ($hero as $i => $h) {
                    HeroSlide::create(['content_id' => $h['id'], 'tagline' => $h['tagline'], 'position' => $i]);
                }
            }
            Setting::put("curation.published.$tab", now()->toIso8601String());
            Setting::where('key', "curation.draft.$tab")->delete();
        });

        $what = $tab === 'Films' ? 'Hero carousel ('.count($hero).' slides) + '.count($rows).' rows' : count($rows).' rows';
        Activity::log('dashboard_customize', '#6CC4FF', 'Curation published', "$tab home · $what", $tab);

        return redirect()->route('curation.index', ['tab' => $tab])->with('toast', ["$tab home published to the app", 'ok']);
    }
}

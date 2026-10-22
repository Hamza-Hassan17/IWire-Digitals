<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Content;
use App\Models\CurationRow;
use App\Models\Genre;
use App\Models\HeroSlide;
use App\Support\Iw;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public const REASONS = ['Copyright', 'Quality', 'Inappropriate', 'Metadata issues', 'Other'];

    public function index(Request $request)
    {
        $tab = in_array($request->query('tab'), ['Music', 'Films']) ? $request->query('tab') : 'All';

        // Oldest first, so nothing waits too long.
        $all = Content::pending()->with('creator')->orderBy('submitted_at')->get();
        $items = $all->filter(fn ($c) => $tab === 'All' || $c->type === $tab)->values();

        $tabs = collect([['All', 'apps'], ['Music', 'graphic_eq'], ['Films', 'movie']])->map(fn ($t) => [
            'label' => $t[0], 'icon' => $t[1], 'n' => $all->filter(fn ($c) => $t[0] === 'All' || $c->type === $t[0])->count(),
        ]);

        return view('admin.queue', ['items' => $items, 'total' => $all->count(), 'tabs' => $tabs, 'tab' => $tab]);
    }

    public function bulk(Request $request)
    {
        $data = $request->validate(['ids' => 'required|array', 'ids.*' => 'integer', 'action' => 'required|in:approve,reject']);
        $items = Content::pending()->with('creator')->whereIn('id', $data['ids'])->get();
        $ok = $data['action'] === 'approve';

        foreach ($items as $c) {
            $ok ? $this->publish($c) : $this->markRejected($c, ['Other'], 'Rejected in bulk review.');
        }

        return back()->with('toast', [$items->count().' submissions '.($ok ? 'approved and now live' : 'rejected'), $ok ? 'ok' : 'bad']);
    }

    public function show(Content $content)
    {
        if ($content->status !== 'Pending') {
            return redirect()->route('library.show', $content);
        }
        $content->load('creator');
        $queue = Content::pending()->orderBy('submitted_at')->pluck('id')->values();
        $pos = $queue->search($content->id);
        $n = $queue->count();
        $prev = $queue[($pos - 1 + $n) % $n];
        $next = $queue[($pos + 1) % $n];

        $isMusic = $content->is_music;
        $checks = $isMusic ? [
            ['File format', 'WAV, 24-bit / 48 kHz', true],
            ['Duration', $content->duration.(Iw::seconds($content->duration) <= 900 && Iw::seconds($content->duration) >= 30 ? ' · within 30s–15m limit' : ' · outside 30s–15m limit'), Iw::seconds($content->duration) <= 900 && Iw::seconds($content->duration) >= 30],
            ['Bitrate', '2,304 kbps lossless', true],
            ['Cover art', '3000 × 3000 JPG', true],
        ] : [
            ['File format', 'MP4 · H.264 / AAC', true],
            ['Duration', $content->duration.' · within limit', true],
            ['Resolution', '3840 × 2160 · 24 fps', true],
            ['Subtitles', 'Missing — recommended', false],
        ];

        // Duplicate detection: compare against the creator's live catalogue by title similarity.
        $dup = null;
        foreach (Content::library()->where('creator_id', $content->creator_id)->get() as $other) {
            similar_text(strtolower($content->title), strtolower($other->title), $pct);
            if ($pct >= 50 && (! $dup || $pct > $dup['score'])) {
                $dup = ['item' => $other, 'score' => (int) round($pct)];
            }
        }

        $genreOpts = Genre::where('kind', $isMusic ? 'music' : 'films')->orderBy('position')->pluck('name');
        $moodOpts = Genre::where('kind', 'moods')->orderBy('position')->pluck('name');

        $rows = CurationRow::where('home', $content->type)->orderBy('position')->get()
            ->map(fn ($r) => ['key' => 'row:'.$r->id, 'label' => $r->name, 'sub' => $content->type.' home · row '.($r->position + 1), 'icon' => $isMusic ? ($r->position === 0 ? 'mood' : 'star') : ($r->position === 0 ? 'trending_up' : 'fiber_new')]);
        if (! $isMusic) {
            $rows->prepend(['key' => 'hero', 'label' => 'Hero banner', 'sub' => 'Films home · carousel', 'icon' => 'view_carousel']);
        }

        $stats = $content->creator->stats;

        return view('admin.review', [
            'c' => $content, 'cr' => $content->creator, 'stats' => $stats, 'pos' => $pos + 1, 'total' => $n,
            'prev' => $prev, 'next' => $next, 'checks' => $checks, 'dup' => $dup,
            'genreOpts' => $genreOpts, 'moodOpts' => $moodOpts, 'rows' => $rows->values(), 'reasons' => self::REASONS,
        ]);
    }

    public function approve(Request $request, Content $content)
    {
        abort_unless($content->status === 'Pending', 409, 'Already reviewed.');
        $data = $request->validate([
            'title' => 'sometimes|required|string|max:160',
            'genre' => 'sometimes|required|string|max:60',
            'mood' => 'nullable|string|max:60',
            'description' => 'nullable|string|max:2000',
            'explicit' => 'sometimes|boolean',
            'feature' => 'nullable|string',
            'return' => 'nullable|in:queue',
        ]);

        $next = $this->nextAfter($content);
        $featured = DB::transaction(function () use ($content, $data) {
            $content->fill(collect($data)->only(['title', 'genre', 'mood', 'description', 'explicit'])->all());
            $this->publish($content);

            if (! empty($data['feature'])) {
                if ($data['feature'] === 'hero') {
                    HeroSlide::create(['content_id' => $content->id, 'tagline' => $content->title.' · '.$content->genre, 'position' => (HeroSlide::max('position') ?? -1) + 1]);

                    return 'Hero banner';
                }
                if (str_starts_with($data['feature'], 'row:') && $row = CurationRow::find((int) substr($data['feature'], 4))) {
                    $row->content_ids = array_values(array_unique([...$row->content_ids, $content->id]));
                    $row->save();

                    return $row->name;
                }
            }

            return null;
        });

        $msg = $featured ? "Live and featured in “{$featured}”" : ($content->is_music ? 'Track' : 'Film').' approved and now live';

        return $this->afterDecision($next, $data['return'] ?? null)->with('toast', [$msg, 'ok']);
    }

    public function reject(Request $request, Content $content)
    {
        abort_unless($content->status === 'Pending', 409, 'Already reviewed.');
        $data = $request->validate([
            'reasons' => 'required|array|min:1',
            'reasons.*' => 'in:'.implode(',', self::REASONS),
            'note' => 'nullable|string|max:2000',
            'return' => 'nullable|in:queue',
        ]);
        $next = $this->nextAfter($content);
        $this->markRejected($content, $data['reasons'], $data['note'] ?? null);

        return $this->afterDecision($next, $data['return'] ?? null)
            ->with('toast', ["“{$content->title}” rejected — {$content->creator->name} notified", 'bad']);
    }

    private function publish(Content $c): void
    {
        $c->status = 'Live';
        $c->reviewed_at = now();
        $c->save();
        Activity::log('check_circle', '#5FD99A', 'Approved', "“{$c->title}” by {$c->creator->name}", $c->type);
    }

    private function markRejected(Content $c, array $reasons, ?string $note): void
    {
        $c->update(['status' => 'Rejected', 'reviewed_at' => now(), 'reject_reasons' => $reasons, 'reject_note' => $note]);
        Activity::log('cancel', '#F38A8A', 'Rejected', "“{$c->title}” by {$c->creator->name} — ".implode(', ', $reasons), $c->type);
    }

    private function nextAfter(Content $c): ?int
    {
        $ids = Content::pending()->orderBy('submitted_at')->pluck('id')->values();
        if ($ids->count() <= 1) {
            return null;
        }
        $pos = $ids->search($c->id);

        return $ids[($pos + 1) % $ids->count()];
    }

    private function afterDecision(?int $next, ?string $return)
    {
        if ($return === 'queue' || ! $next) {
            return $return === 'queue' ? back() : redirect()->route('queue.index');
        }

        return redirect()->route('review.show', $next);
    }
}

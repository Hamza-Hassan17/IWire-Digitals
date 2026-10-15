<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Content;
use App\Models\Genre;
use App\Support\Iw;
use Illuminate\Http\Request;

class LibraryController extends Controller
{
    public const STATUSES = ['Live', 'Unpublished', 'Flagged', 'Rejected'];

    public function index(Request $request)
    {
        $tab = $request->query('tab', session('ctx') === 'Films' ? 'Films' : 'Music');
        $tab = in_array($tab, ['Music', 'Films']) ? $tab : 'Music';
        $f = [
            'q' => (string) $request->query('q', ''),
            'status' => $request->query('status', 'Any status'),
            'genre' => $request->query('genre', 'Any genre'),
            'creator' => $request->query('creator', 'Any creator'),
            'date' => $request->query('date', 'Any time'),
        ];
        $view = $request->query('view', 'grid');

        $base = Content::library()->where('type', $tab)->with('creator');
        $src = (clone $base)->get();
        $items = $base
            ->when($f['q'] !== '', fn ($q) => $q->where(fn ($w) => $w->where('title', 'like', "%{$f['q']}%")->orWhereHas('creator', fn ($c) => $c->where('name', 'like', "%{$f['q']}%"))))
            ->when($f['status'] !== 'Any status', fn ($q) => $q->where('status', $f['status']))
            ->when($f['genre'] !== 'Any genre', fn ($q) => $q->where('genre', $f['genre']))
            ->when($f['creator'] !== 'Any creator', fn ($q) => $q->whereHas('creator', fn ($c) => $c->where('name', $f['creator'])))
            ->when($f['date'] !== 'Any time', fn ($q) => $q->where('submitted_at', '>=', now()->subDays((int) filter_var($f['date'], FILTER_SANITIZE_NUMBER_INT))))
            ->orderByDesc('plays')->get();

        $live = $src->where('status', 'Live')->count();
        $unpub = $src->where('status', 'Unpublished')->count();
        $countLabel = number_format($live).' live '.($tab === 'Music' ? 'tracks' : 'films').' · '.$unpub.' unpublished';

        $filters = [
            ['radio_button_checked', 'status', ['Any status', ...self::STATUSES]],
            ['category', 'genre', ['Any genre', ...$src->pluck('genre')->unique()->sort()->values()]],
            ['person', 'creator', ['Any creator', ...$src->map(fn ($c) => $c->creator->name)->unique()->sort()->values()]],
            ['calendar_month', 'date', ['Any time', 'Last 7 days', 'Last 30 days', 'Last 90 days']],
        ];

        return view('admin.library.index', compact('tab', 'f', 'view', 'items', 'countLabel', 'filters'));
    }

    public function show(Content $content)
    {
        if ($content->status === 'Pending') {
            return redirect()->route('review.show', $content);
        }
        $content->load(['creator', 'comments' => fn ($q) => $q->with('listener')->latest()->limit(4)]);
        $ser = Iw::series(30, mb_strlen($content->title), 100, 22, 2);
        $genreOpts = Genre::where('kind', $content->is_music ? 'music' : 'films')->orderBy('position')->pluck('name');

        return view('admin.library.show', [
            'd' => $content,
            'line' => Iw::path($ser, 600, 160, 160, 40), 'area' => Iw::area($ser, 600, 160, 160, 40),
            'genreOpts' => $genreOpts->push($content->genre)->unique(),
            'moodOpts' => Genre::where('kind', 'moods')->orderBy('position')->pluck('name'),
        ]);
    }

    public function update(Request $request, Content $content)
    {
        $data = $request->validate([
            'title' => 'required|string|max:160',
            'genre' => 'required|string|max:60',
            'mood' => 'nullable|string|max:60',
            'description' => 'nullable|string|max:2000',
            'explicit' => 'boolean',
        ]);
        $content->update($data);

        return back()->with('toast', ['Metadata saved', 'ok']);
    }

    public function togglePublish(Content $content)
    {
        $live = $content->status === 'Live';
        $content->update(['status' => $live ? 'Unpublished' : 'Live']);
        Activity::log($live ? 'visibility_off' : 'publish', $live ? '#A7B4C6' : '#5FD99A', $live ? 'Unpublished' : 'Republished', "“{$content->title}” by {$content->creator->name}", $content->type);

        return back()->with('toast', [$live ? "“{$content->title}” unpublished" : "“{$content->title}” is live again", $live ? 'info' : 'ok']);
    }

    /** Generate a fresh cover (stand-in for an upload pipeline). */
    public function cover(Content $content)
    {
        $content->update(['art_seed' => random_int(0, 11), 'art_style' => random_int(0, 4)]);

        return back()->with('toast', ['Cover updated — syncing to app', 'ok']);
    }

    public function destroy(Content $content)
    {
        $title = $content->title;
        $tab = $content->type;
        Activity::log('delete', '#F38A8A', 'Deleted', "“{$title}” by {$content->creator->name}", $content->type);
        $content->delete();

        return redirect()->route('library.index', ['tab' => $tab])->with('toast', ["“{$title}” deleted", 'bad']);
    }
}

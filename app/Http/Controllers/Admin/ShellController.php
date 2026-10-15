<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\Creator;
use App\Models\Listener;
use Illuminate\Http\Request;

class ShellController extends Controller
{
    public function context(Request $request)
    {
        $request->validate(['ctx' => 'required|in:All,Music,Films']);
        session(['ctx' => $request->ctx]);

        return back();
    }

    /** Command palette results (⌘K). */
    public function search(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $like = '%'.$q.'%';
        $groups = [];

        $screens = [
            ['dashboard', 'Dashboard', 'space_dashboard'], ['queue.index', 'Review Queue', 'inventory'], ['reports.index', 'Reports & Flags', 'flag'],
            ['library.index', 'Library', 'video_library'], ['curation.index', 'Home Curation', 'dashboard_customize'], ['genres.index', 'Genres & Moods', 'category'],
            ['listeners.index', 'Listeners', 'headphones'], ['creators.index', 'Creators', 'mic_external_on'], ['comments.index', 'Comments', 'forum'],
            ['push.index', 'Push Notifications', 'campaign'], ['analytics', 'Analytics', 'monitoring'], ['activity', 'Activity Log', 'history'], ['settings', 'Settings', 'settings'],
        ];
        $nav = collect($screens)->filter(fn ($s) => $q === '' || stripos($s[1], $q) !== false)->take($q ? 6 : 5)
            ->map(fn ($s) => ['icon' => $s[2], 'label' => 'Go to '.$s[1], 'sub' => 'Navigate', 'art' => 'rgba(255,255,255,.06)', 'url' => route($s[0])])->values();
        if ($nav->isNotEmpty()) {
            $groups[] = ['label' => 'NAVIGATE', 'items' => $nav];
        }

        $content = Content::with('creator')
            ->when($q !== '', fn ($w) => $w->where(fn ($w) => $w->where('title', 'like', $like)->orWhereHas('creator', fn ($c) => $c->where('name', 'like', $like))))
            ->orderByRaw("status = 'Pending' desc")->orderByDesc('plays')->limit($q ? 6 : 3)->get()
            ->map(fn ($c) => ['icon' => $c->icon, 'label' => $c->title, 'sub' => $c->creator->name.' · '.$c->status, 'art' => $c->art,
                'url' => $c->status === 'Pending' ? route('review.show', $c) : route('library.show', $c)]);
        if ($content->isNotEmpty()) {
            $groups[] = ['label' => 'CONTENT', 'items' => $content];
        }

        if ($q !== '') {
            $creators = Creator::where('name', 'like', $like)->limit(3)->get()
                ->map(fn ($c) => ['icon' => $c->icon, 'label' => $c->name, 'sub' => $c->type, 'art' => $c->art, 'url' => route('creators.show', $c)]);
            if ($creators->isNotEmpty()) {
                $groups[] = ['label' => 'CREATORS', 'items' => $creators];
            }
            $listeners = Listener::where('name', 'like', $like)->orWhere('email', 'like', $like)->limit(3)->get()
                ->map(fn ($l) => ['icon' => 'headphones', 'label' => $l->name, 'sub' => $l->email, 'art' => $l->art, 'url' => route('listeners.index', ['open' => $l->id])]);
            if ($listeners->isNotEmpty()) {
                $groups[] = ['label' => 'LISTENERS', 'items' => $listeners];
            }
        } else {
            $groups[] = ['label' => 'ACTIONS', 'items' => [
                ['icon' => 'campaign', 'label' => 'Compose push notification', 'sub' => 'Action', 'art' => 'rgba(10,155,255,.16)', 'url' => route('push.index')],
                ['icon' => 'publish', 'label' => 'Publish home curation', 'sub' => 'Action', 'art' => 'rgba(10,155,255,.16)', 'url' => route('curation.index')],
            ]];
        }

        return response()->json($groups);
    }
}

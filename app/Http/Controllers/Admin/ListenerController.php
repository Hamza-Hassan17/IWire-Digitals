<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Content;
use App\Models\Listener;
use Illuminate\Http\Request;

class ListenerController extends Controller
{
    public function index(Request $request)
    {
        $q = (string) $request->query('q', '');
        $status = in_array($request->query('status'), ['Active', 'Suspended']) ? $request->query('status') : 'All';

        $rows = Listener::query()
            ->when($q !== '', fn ($w) => $w->where(fn ($w) => $w->where('name', 'like', "%$q%")->orWhere('email', 'like', "%$q%")))
            ->when($status !== 'All', fn ($w) => $w->where('status', $status))
            ->orderByDesc('last_active_at')->paginate(15)->withQueryString();

        $open = $request->query('open') ? Listener::with(['comments' => fn ($c) => $c->with('content')->latest()->limit(3)])->find($request->query('open')) : null;
        $history = collect();
        if ($open) {
            $pool = Content::with('creator')->where('status', 'Live')->orderByDesc('plays')->get();
            $history = $pool->isEmpty() ? collect() : collect(range(0, 4))->map(fn ($k) => $pool[($open->id * 3 + $k * 5) % $pool->count()]);
        }

        return view('admin.listeners', [
            'rows' => $rows, 'q' => $q, 'status' => $status, 'open' => $open, 'history' => $history,
            'total' => Listener::count(), 'newThisWeek' => Listener::where('joined_at', '>=', now()->subWeek())->count(),
        ]);
    }

    public function toggleSuspend(Listener $listener)
    {
        $sus = $listener->status === 'Suspended';
        $listener->update(['status' => $sus ? 'Active' : 'Suspended']);
        Activity::log($sus ? 'restart_alt' : 'block', $sus ? '#5FD99A' : '#F38A8A', $sus ? 'Reactivated user' : 'Suspended user', "{$listener->name} (listener)");

        return back()->with('toast', [$sus ? "{$listener->name} reactivated" : "{$listener->name} suspended", $sus ? 'ok' : 'bad']);
    }

    public function destroy(Listener $listener)
    {
        $name = $listener->name;
        $listener->delete();
        Activity::log('person_remove', '#F38A8A', 'Deleted user', "$name (listener)");

        return redirect()->route('listeners.index')->with('toast', ["{$name}’s account deleted", 'bad']);
    }

    public function export()
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Email', 'Joined', 'Last active', 'Streams', 'Status', 'Device', 'Location']);
            Listener::orderBy('id')->chunk(500, function ($chunk) use ($out) {
                foreach ($chunk as $l) {
                    fputcsv($out, [$l->name, $l->email, $l->joined_at?->toDateString(), $l->last_active_at?->toDateTimeString(), $l->streams, $l->status, $l->device, $l->location]);
                }
            });
            fclose($out);
        }, 'iwire-listeners-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }
}

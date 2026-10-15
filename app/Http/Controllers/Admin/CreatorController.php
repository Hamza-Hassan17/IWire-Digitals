<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Creator;
use Illuminate\Http\Request;

class CreatorController extends Controller
{
    public function index(Request $request)
    {
        $q = (string) $request->query('q', '');
        $ctx = session('ctx', 'All');
        $creators = Creator::withCount('contents')
            ->when($ctx !== 'All', fn ($w) => $w->where('type', $ctx === 'Music' ? 'Musician' : 'Filmmaker'))
            ->when($q !== '', fn ($w) => $w->where(fn ($w) => $w->where('name', 'like', "%$q%")->orWhere('email', 'like', "%$q%")))
            ->orderByDesc('total_plays')->get();

        return view('admin.creators.index', compact('creators', 'q'));
    }

    public function show(Creator $creator)
    {
        $creator->load(['contents' => fn ($q) => $q->orderByDesc('submitted_at')]);

        return view('admin.creators.show', ['cr' => $creator, 'stats' => $creator->stats]);
    }

    public function toggleSuspend(Creator $creator)
    {
        $sus = $creator->status === 'Suspended';
        $creator->update(['status' => $sus ? 'Active' : 'Suspended']);
        Activity::log($sus ? 'restart_alt' : 'block', $sus ? '#5FD99A' : '#F38A8A', $sus ? 'Reactivated user' : 'Suspended user', "{$creator->name} (creator)", $creator->type === 'Musician' ? 'Music' : 'Films');

        return back()->with('toast', [$sus ? "{$creator->name} reactivated" : "{$creator->name} suspended", $sus ? 'ok' : 'bad']);
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\PushNotification;
use Illuminate\Http\Request;

class PushController extends Controller
{
    public const AUDIENCES = ['All users', 'Music users', 'Films users', 'Creators'];

    public function index()
    {
        return view('admin.push', ['sent' => PushNotification::orderByDesc('send_at')->get(), 'audiences' => self::AUDIENCES]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:65',
            'body' => 'required|string|max:178',
            'audience' => 'required|in:'.implode(',', self::AUDIENCES),
            'when' => 'required|in:now,later',
            'send_at' => 'required_if:when,later|nullable|date|after:now',
        ]);
        $later = $data['when'] === 'later';
        // No push provider is wired up yet: "now" records the send; delivery stats arrive from the provider later.
        PushNotification::create([
            'title' => $data['title'], 'body' => $data['body'], 'audience' => $data['audience'],
            'status' => $later ? 'Scheduled' : 'Sent', 'send_at' => $later ? $data['send_at'] : now(),
        ]);
        if (! $later) {
            Activity::log('campaign', '#6CC4FF', 'Notification sent', "“{$data['title']}” → {$data['audience']}",
                $data['audience'] === 'Music users' ? 'Music' : ($data['audience'] === 'Films users' ? 'Films' : 'All'));
        }

        return back()->with('toast', [$later ? 'Notification scheduled' : 'Notification sent to '.$data['audience'], 'ok']);
    }
}

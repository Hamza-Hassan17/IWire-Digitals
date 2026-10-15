<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public const DEFAULTS = ['auto_flag_threshold' => 10, 'max_film_gb' => 8, 'max_track_minutes' => 15, 'require_explicit_label' => true];

    public function index()
    {
        return view('admin.settings', ['platform' => array_merge(self::DEFAULTS, Setting::get('platform', []))]);
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        $user->update($request->validate([
            'name' => 'required|string|max:80',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
        ]));

        return back()->with('toast', ['Profile updated', 'ok']);
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|string|min:10|confirmed',
        ]);
        $request->user()->update(['password' => $data['password']]);

        return back()->with('toast', ['Password changed', 'ok']);
    }

    public function platform(Request $request)
    {
        $data = $request->validate([
            'auto_flag_threshold' => 'required|integer|min:1|max:1000',
            'max_film_gb' => 'required|integer|min:1|max:100',
            'max_track_minutes' => 'required|integer|min:1|max:60',
            'require_explicit_label' => 'boolean',
        ]);
        Setting::put('platform', $data + ['require_explicit_label' => false]);

        return back()->with('toast', ['Platform settings saved', 'ok']);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Models\Creator;
use App\Models\Listener;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthController extends ApiController
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'email' => 'required|email|max:191|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'account_type' => 'required|in:listener,creator',
            'device_name' => 'nullable|string|max:100',
        ]);

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'], 'email' => $data['email'],
                'password' => $data['password'], 'account_type' => $data['account_type'],
            ]);
            $seed = random_int(0, 11);
            // Mirror the account into the control room's Listeners / Creators screens.
            if ($data['account_type'] === 'creator') {
                Creator::updateOrCreate(['email' => $user->email], [
                    'user_id' => $user->id, 'name' => $user->name, 'type' => 'Musician', 'status' => 'Active', 'art_seed' => $seed, 'joined_at' => now(),
                ]);
            } else {
                Listener::updateOrCreate(['email' => $user->email], [
                    'user_id' => $user->id, 'name' => $user->name, 'status' => 'Active', 'art_seed' => $seed, 'joined_at' => now(), 'last_active_at' => now(),
                ]);
            }

            return $user;
        });

        return $this->ok($this->session($user, $data['device_name'] ?? null), 'Registered', 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:100',
        ]);
        $user = User::where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return $this->fail('Invalid credentials', 401);
        }
        if ($user->isSuspended()) {
            return $this->fail('Your account has been suspended. Contact support.', 403);
        }

        return $this->ok($this->session($user, $data['device_name'] ?? null), 'Logged in');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->ok(null, 'Logged out');
    }

    public function me(Request $request)
    {
        return $this->ok(['user' => self::user($request->user())], 'OK');
    }

    private function session(User $user, ?string $device): array
    {
        return [
            'token' => $user->createToken($device ?: 'mobile')->plainTextToken,
            'user' => self::user($user),
        ];
    }

    public static function user(User $u): array
    {
        return ['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'account_type' => $u->account_type, 'avatar' => $u->avatar_url];
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends ApiController
{
    public const GENDERS = ['male', 'female', 'other', 'prefer_not_to_say'];

    public function show(Request $request)
    {
        return $this->ok(['user' => AuthController::user($request->user())]);
    }

    /** Partial update: send only the fields that changed. */
    public function update(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:80',
            'email' => ['sometimes', 'required', 'email', 'max:191', Rule::unique('users')->ignore($user->id)],
            'gender' => ['sometimes', 'nullable', Rule::in(self::GENDERS)],
            'date_of_birth' => 'sometimes|nullable|date|before:today|after:1900-01-01',
            'country' => 'sometimes|nullable|string|max:80',
            'bio' => 'sometimes|nullable|string|max:500',
        ]);
        $user->update($data);
        $this->syncProfile($user);

        return $this->ok(['user' => AuthController::user($user->fresh())], 'Profile updated');
    }

    /** multipart/form-data with field "avatar". */
    public function avatar(Request $request)
    {
        $request->validate(['avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120']);
        $user = $request->user();
        $old = $user->avatar_path;
        $user->update(['avatar_path' => $request->file('avatar')->store("avatars/{$user->id}", 'public')]);
        if ($old) {
            Storage::disk('public')->delete($old);
        }

        return $this->ok(['user' => AuthController::user($user)], 'Profile picture updated');
    }

    public function removeAvatar(Request $request)
    {
        $user = $request->user();
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->update(['avatar_path' => null]);
        }

        return $this->ok(['user' => AuthController::user($user)], 'Profile picture removed');
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => 'required|current_password:sanctum',
            'password' => 'required|string|min:8|confirmed|different:current_password',
        ]);
        $user = $request->user();
        $user->update(['password' => $data['password']]);
        // Sign out every other device; keep the one making this request.
        $user->tokens()->where('id', '!=', $user->currentAccessToken()->id)->delete();

        return $this->ok(null, 'Password changed');
    }

    /** Permanent account deletion (required by the App Store and Google Play). */
    public function destroy(Request $request)
    {
        $request->validate(['password' => 'required|current_password:sanctum']);
        $user = $request->user();
        Activity::log('person_remove', '#F38A8A', 'Account deleted', "{$user->name} ({$user->account_type}) deleted their account from the app");

        $user->listener?->delete();
        // A creator's published catalogue stays; only the login is removed.
        $user->creator?->update(['user_id' => null]);
        Storage::disk('public')->deleteDirectory("avatars/{$user->id}");
        $user->tokens()->delete();
        $user->delete();

        return $this->ok(null, 'Account deleted');
    }

    /** Keep the control room's Listeners / Creators records in step with the app profile. */
    private function syncProfile($user): void
    {
        $fields = ['name' => $user->name, 'email' => $user->email];
        $user->listener?->update($fields);
        $user->creator?->update($fields);
    }
}

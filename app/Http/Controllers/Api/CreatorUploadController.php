<?php

namespace App\Http\Controllers\Api;

use App\Models\Activity;
use App\Models\Content;
use App\Models\Creator;
use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Creator accounts submit tracks and films from the app. Submissions land in the control room's
 * Review Queue as "Pending"; approval publishes them, rejection returns the admin's reasons here.
 */
class CreatorUploadController extends ApiController
{
    public function store(Request $request)
    {
        $creator = $this->creator($request);
        $type = $request->input('type');
        $music = $type === 'music';
        $data = $request->validate([
            'type' => 'required|in:music,films',
            'title' => 'required|string|max:160',
            'genre' => ['required', Rule::exists('genres', 'name')->where('kind', $music ? 'music' : 'films')],
            'mood' => ['nullable', Rule::exists('genres', 'name')->where('kind', 'moods')],
            'description' => 'nullable|string|max:2000',
            'explicit' => 'nullable|boolean',
            'duration' => 'required|integer|min:1|max:36000',
            'file' => $music
                ? 'required|file|mimetypes:audio/mpeg,audio/mp4,audio/x-m4a,audio/aac,audio/wav,audio/x-wav,audio/flac,audio/x-flac,audio/ogg|max:204800'
                : 'required|file|mimetypes:video/mp4,video/quicktime,video/webm,video/x-matroska,video/x-m4v|max:4194304',
            'cover' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
        ]);

        $c = Content::create([
            'creator_id' => $creator->id,
            'type' => $music ? 'Music' : 'Films',
            'title' => $data['title'],
            'genre' => $data['genre'],
            'mood' => $music ? ($data['mood'] ?? null) : null,
            'description' => $data['description'] ?? null,
            'explicit' => (bool) ($data['explicit'] ?? false),
            'duration' => self::formatDuration((int) $data['duration'], $music),
            'status' => 'Pending',
            'art_seed' => random_int(0, 11),
            'art_style' => random_int(0, 4),
            'submitted_at' => now(),
        ]);
        $c->update([
            'file_path' => $request->file('file')->store("media/{$c->id}", 'local'),
            'cover_path' => $request->file('cover')->store("artwork/{$c->id}", 'public'),
            'banner_path' => $request->hasFile('banner') ? $request->file('banner')->store("artwork/{$c->id}", 'public') : null,
        ]);
        Activity::log('upload', '#6CC4FF', 'New submission', "“{$c->title}” by {$creator->name}", $c->type);

        return $this->ok(self::payload($c), 'Submitted for review', 201);
    }

    public function index(Request $request)
    {
        $creator = $this->creator($request);
        $status = $request->validate(['status' => 'nullable|in:pending,live,rejected,unpublished'])['status'] ?? null;
        $page = Content::where('creator_id', $creator->id)
            ->when($status, fn ($q) => $q->where('status', ucfirst($status)))
            ->latest('submitted_at')->paginate(20);

        return $this->paginated($page, fn ($c) => self::payload($c));
    }

    public function show(Request $request, int $upload)
    {
        $creator = $this->creator($request);

        return $this->ok(self::payload(Content::where('creator_id', $creator->id)->findOrFail($upload)));
    }

    /** Withdraw a submission that hasn't been reviewed yet. */
    public function destroy(Request $request, int $upload)
    {
        $creator = $this->creator($request);
        $c = Content::where('creator_id', $creator->id)->findOrFail($upload);
        if ($c->status !== 'Pending') {
            return $this->fail('Only submissions still pending review can be withdrawn.', 409);
        }
        Storage::disk('public')->deleteDirectory("artwork/{$c->id}");
        Storage::disk('local')->deleteDirectory("media/{$c->id}");
        $c->delete();

        return $this->ok(null, 'Submission withdrawn');
    }

    public function stats(Request $request)
    {
        $creator = $this->creator($request);
        $all = Content::where('creator_id', $creator->id)->get();

        return $this->ok([
            'uploads' => $all->count(),
            'pending' => $all->where('status', 'Pending')->count(),
            'live' => $all->where('status', 'Live')->count(),
            'rejected' => $all->where('status', 'Rejected')->count(),
            'total_plays' => (int) $all->sum('plays'),
            'total_comments' => (int) $all->sum('comments_count'),
        ]);
    }

    private function creator(Request $request): Creator
    {
        $user = $request->user();
        abort_unless($user->account_type === 'creator', 403, 'This feature is for creator accounts.');

        return $user->creator ?? Creator::create([
            'user_id' => $user->id, 'name' => $user->name, 'email' => $user->email,
            'type' => 'Musician', 'status' => 'Active', 'art_seed' => random_int(0, 11), 'joined_at' => now(),
        ]);
    }

    public static function formatDuration(int $s, bool $music): string
    {
        if ($music) {
            return intdiv($s, 60).':'.str_pad((string) ($s % 60), 2, '0', STR_PAD_LEFT);
        }
        $h = intdiv($s, 3600);
        $m = intdiv($s % 3600, 60);

        return $h ? sprintf('%dh %02dm', $h, $m) : "{$m}m";
    }

    public static function payload(Content $c): array
    {
        return [
            'id' => $c->id,
            'type' => $c->is_music ? 'music' : 'films',
            'title' => $c->title,
            'genre' => $c->genre,
            'mood' => $c->mood,
            'description' => $c->description,
            'explicit' => $c->explicit,
            'duration' => $c->duration_seconds,
            'cover' => $c->cover_url,
            'banner' => $c->is_music ? null : $c->banner_url,
            'status' => strtolower($c->status),
            'reject_reasons' => $c->status === 'Rejected' ? ($c->reject_reasons ?? []) : [],
            'reject_note' => $c->status === 'Rejected' ? $c->reject_note : null,
            'plays' => (int) $c->plays,
            'comments' => (int) $c->comments_count,
            'submitted_at' => $c->submitted_at?->toIso8601String(),
            'reviewed_at' => $c->reviewed_at?->toIso8601String(),
        ];
    }
}

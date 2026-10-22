<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Content;
use App\Models\Genre;
use App\Support\Iw;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LibraryController extends Controller
{
    public const STATUSES = ['Live', 'Unpublished', 'Flagged', 'Rejected'];

    public function index(Request $request)
    {
        $tab = $request->query('tab', 'Music');
        $tab = in_array($tab, ['Music', 'Films']) ? $tab : 'Music';
        $q = (string) $request->query('q', '');
        $statuses = ['Any status', ...self::STATUSES];
        $status = in_array($request->query('status'), self::STATUSES) ? $request->query('status') : 'Any status';

        $base = Content::library()->where('type', $tab)->with('creator');
        $counts = (clone $base)->selectRaw('status, count(*) n')->groupBy('status')->pluck('n', 'status');
        $items = $base
            ->when($q !== '', fn ($w) => $w->where(fn ($w) => $w->where('title', 'like', "%$q%")->orWhereHas('creator', fn ($c) => $c->where('name', 'like', "%$q%"))))
            ->when($status !== 'Any status', fn ($w) => $w->where('status', $status))
            ->orderByDesc('plays')->get();

        $countLabel = number_format($counts['Live'] ?? 0).' live '.($tab === 'Music' ? 'tracks' : 'films').' · '.($counts['Unpublished'] ?? 0).' unpublished';

        return view('admin.library.index', compact('tab', 'q', 'status', 'statuses', 'items', 'countLabel'));
    }

    public function show(Content $content)
    {
        if ($content->status === 'Pending') {
            return redirect()->route('review.show', $content);
        }
        $content->load(['creator', 'comments' => fn ($q) => $q->with(['listener', 'user'])->latest()->limit(4)]);
        $genreOpts = Genre::where('kind', $content->is_music ? 'music' : 'films')->orderBy('position')->pluck('name');

        return view('admin.library.show', [
            'd' => $content,
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

    /**
     * Upload artwork and the playable media file. Images go to the public disk; the media file
     * stays private and is only reachable through the API's signed stream URL.
     */
    public function media(Request $request, Content $content)
    {
        $mediaRule = $content->is_music
            ? 'file|mimetypes:audio/mpeg,audio/mp4,audio/x-m4a,audio/aac,audio/wav,audio/x-wav,audio/flac,audio/x-flac,audio/ogg|max:204800'
            : 'file|mimetypes:video/mp4,video/quicktime,video/webm,video/x-matroska,video/x-m4v|max:4194304';
        $request->validate([
            'cover' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:8192',
            'file' => 'nullable|'.$mediaRule,
        ]);

        $done = [];
        foreach (['cover' => 'cover_path', 'banner' => 'banner_path'] as $field => $col) {
            if ($request->hasFile($field)) {
                $old = $content->$col;
                $content->$col = $request->file($field)->store("artwork/{$content->id}", 'public');
                if ($old) {
                    Storage::disk('public')->delete($old);
                }
                $done[] = $field;
            }
        }
        if ($request->hasFile('file')) {
            $old = $content->file_path;
            $content->file_path = $request->file('file')->store("media/{$content->id}", 'local');
            if ($old) {
                Storage::disk('local')->delete($old);
            }
            $done[] = $content->is_music ? 'audio' : 'video';
        }
        $content->save();

        return back()->with('toast', $done ? [ucfirst(implode(', ', $done)).' uploaded — live in the app', 'ok'] : ['Choose a file to upload', 'info']);
    }

    public function destroy(Content $content)
    {
        $title = $content->title;
        $tab = $content->type;
        Activity::log('delete', '#F38A8A', 'Deleted', "“{$title}” by {$content->creator->name}", $content->type);
        Storage::disk('public')->deleteDirectory("artwork/{$content->id}");
        Storage::disk('local')->deleteDirectory("media/{$content->id}");
        $content->delete();

        return redirect()->route('library.index', ['tab' => $tab])->with('toast', ["“{$title}” deleted", 'bad']);
    }
}

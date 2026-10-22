<?php

namespace App\Http\Controllers;

use App\Models\Content;
use App\Support\ArtImage;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    private const SIZES = ['600x600', '400x600', '1280x720'];

    /** Generated placeholder art as PNG, rendered once and cached on disk. */
    public function art(string $size, string $art)
    {
        abort_unless(in_array($size, self::SIZES, true) && preg_match('/^(\d{1,2})-([0-4])$/', $art, $m) && (int) $m[1] < 12, 404);
        $path = "art/$size/$art.png";
        $disk = Storage::disk('local');
        if (! $disk->exists($path)) {
            [$w, $h] = array_map('intval', explode('x', $size));
            $disk->put($path, ArtImage::png($w, $h, (int) $m[1], (int) $m[2]));
        }

        return response()->file($disk->path($path), ['Content-Type' => 'image/png', 'Cache-Control' => 'public, max-age=31536000, immutable']);
    }

    /** Control-room preview of any item's media file, including pending submissions. */
    public function preview(Content $content)
    {
        abort_unless($content->file_path && Storage::disk('local')->exists($content->file_path), 404);

        return response()->file(Storage::disk('local')->path($content->file_path), ['Cache-Control' => 'private, no-store']);
    }

    /** Signed, expiring media stream. Supports HTTP Range requests for seeking. */
    public function stream(Content $content)
    {
        abort_unless($content->status === 'Live' && $content->file_path && Storage::disk('local')->exists($content->file_path), 404);
        // Players seek with Range requests; only count a play when playback starts from the beginning.
        $range = request()->header('Range');
        if (! $range || preg_match('/^bytes=0-/', $range)) {
            $content->increment('plays');
        }

        return response()->file(Storage::disk('local')->path($content->file_path), ['Cache-Control' => 'private, max-age=3600']);
    }
}

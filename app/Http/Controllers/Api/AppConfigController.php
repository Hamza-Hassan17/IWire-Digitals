<?php

namespace App\Http\Controllers\Api;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class AppConfigController extends ApiController
{
    public static function defaults(): array
    {
        return [
            'maintenance_mode' => false,
            'maintenance_message' => null,
            'min_app_version' => '1.0.0',
            'support_email' => 'support@iwire.app',
            'splash_music_title' => 'Slide Down For Music',
            'splash_films_title' => 'Slide Up For Films',
            'splash_music_image' => null,
            'splash_films_image' => null,
        ];
    }

    public static function settings(): array
    {
        return array_merge(self::defaults(), Setting::get('app', []));
    }

    public function show()
    {
        $s = self::settings();
        $img = fn (?string $path, string $fallback) => $path ? Storage::disk('public')->url($path) : asset($fallback);

        return $this->ok([
            'maintenance_mode' => (bool) $s['maintenance_mode'],
            'maintenance_message' => $s['maintenance_mode'] ? $s['maintenance_message'] : null,
            'min_app_version' => $s['min_app_version'],
            'splash' => [
                'music' => ['title' => $s['splash_music_title'], 'image' => $img($s['splash_music_image'], 'images/studio-music.png')],
                'films' => ['title' => $s['splash_films_title'], 'image' => $img($s['splash_films_image'], 'images/studio-films.png')],
            ],
            'support_email' => $s['support_email'],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Models\Setting;

/** Legal pages, edited by the admin in Settings → Terms & Privacy. */
class PageController extends ApiController
{
    public const PAGES = [
        'terms' => 'Terms & Conditions',
        'privacy' => 'Privacy Policy',
    ];

    public static function get(string $slug): array
    {
        $saved = Setting::get("page.$slug", []);

        return [
            'slug' => $slug,
            'title' => $saved['title'] ?? self::PAGES[$slug],
            'content' => $saved['content'] ?? '',
            'updated_at' => $saved['updated_at'] ?? null,
        ];
    }

    public function show(string $slug)
    {
        abort_unless(isset(self::PAGES[$slug]), 404);

        return $this->ok(self::get($slug));
    }
}

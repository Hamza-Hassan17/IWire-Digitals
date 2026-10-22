<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\AppConfigController;
use App\Models\Content;

/** Public landing page behind every share link (WhatsApp/Facebook previews read its meta tags). */
class ShareController extends Controller
{
    public function show(string $kind, int $id)
    {
        $c = Content::with('creator')->where('type', $kind === 'track' ? 'Music' : 'Films')->where('status', 'Live')->find($id);
        if (! $c) {
            return response()->view('share', ['c' => null, 'store' => null], 404);
        }

        return view('share', ['c' => $c, 'store' => AppConfigController::settings()['android_store_url'] ?? null]);
    }
}

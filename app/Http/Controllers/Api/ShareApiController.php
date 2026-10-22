<?php

namespace App\Http\Controllers\Api;

use App\Models\Content;
use Illuminate\Http\Request;

class ShareApiController extends ApiController
{
    /**
     * Call when the user taps Share: counts the share (shown in the admin Library) and returns
     * the link and text to hand to the phone's share sheet.
     */
    public function share(Request $request, int $id)
    {
        $type = $request->is('api/v1/music/*') ? 'Music' : 'Films';
        $c = Content::with('creator')->where('type', $type)->where('status', 'Live')->findOrFail($id);
        $c->increment('shares');

        return $this->ok([
            'share_url' => $c->share_url,
            'text' => ($c->is_music ? 'Listen to' : 'Watch')." “{$c->title}” by {$c->creator->name} on iWire: {$c->share_url}",
        ]);
    }
}

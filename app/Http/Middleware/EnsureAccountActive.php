<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks suspended app accounts (suspension is set from the control room) and keeps the
 * listener's "last active" time fresh for the Listeners screen.
 */
class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user?->isSuspended()) {
            $user->currentAccessToken()?->delete();

            return response()->json(['success' => false, 'message' => 'Your account has been suspended. Contact support.'], 403);
        }
        $listener = $user?->listener;
        if ($listener && (! $listener->last_active_at || $listener->last_active_at->lt(now()->subMinutes(5)))) {
            $listener->forceFill(['last_active_at' => now()])->save();
        }

        return $next($request);
    }
}

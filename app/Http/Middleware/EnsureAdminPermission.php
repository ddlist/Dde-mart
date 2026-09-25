<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/*
 * DDE-Mart Admin — group/ability gate (original middleware).
 * Inspired by: legacy PermissionMiddleware (`permission:<group>,<route>`) — reimplemented.
 * Fixes: no dead Spatie import, null-safe role handling, exact-ability matching.
 *
 * Usage: ->middleware('admin.can:roles')            (any grant in group)
 *        ->middleware('admin.can:roles,edit')       (exact ability)
 * Guests are sent to login; denied users get a 403 view.
 */
class EnsureAdminPermission
{
    public function handle(Request $request, Closure $next, string $group, ?string $ability = null): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->canAccess($group, $ability)) {
            abort(403);
        }

        return $next($request);
    }
}

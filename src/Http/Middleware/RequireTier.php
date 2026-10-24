<?php

namespace RattingManager\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use RattingManager\Support\Access;

class RequireTier
{
    public function handle(Request $request, Closure $next, string $tier)
    {
        if (!Access::atLeast($tier, $request->user())) {
            abort(403);
        }

        return $next($request);
    }
}

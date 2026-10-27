<?php

namespace RattingManager\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use RattingManager\Services\Character\Players;
use RattingManager\Services\Configuration\Settings;
use RattingManager\Support\Access;

class RequireTier
{
    public function handle(Request $request, Closure $next, string $tier)
    {
        if (!Access::atLeast($tier, $request->user())) {
            abort(403);
        }

        // With Settings > Access asking for it, the ratting pages are for
        // players with a character in a tracked corporation now, so a
        // member who left loses them even while SeAT still gives the role.
        // Admins are never locked out, and Help stays open to everyone.
        if ($tier !== 'view' && !Access::isAdmin($request->user()) && $this->leftTheCorporation($request)) {
            abort(403, trans('ratting-manager::common.not_in_corporation'));
        }

        return $next($request);
    }

    private function leftTheCorporation(Request $request): bool
    {
        $settings = app(Settings::class);
        $tracked = $settings->configuredCorporations();

        if (!$settings->get('access.member_needs_corporation') || $tracked === []) {
            return false;
        }

        $characters = app(Players::class)->charactersOfUser($request->user());

        return Access::trackedCorporationsOf($characters, $tracked) === [];
    }
}

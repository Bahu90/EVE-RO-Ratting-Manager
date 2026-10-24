<?php

namespace RattingManager\Support;

/**
 * Where the signed-in user sits in the four tiers.
 *
 * SeAT checks one permission at a time, so a director holding only the
 * director permission would fail a plain can:ratting-manager.member check.
 * Every route and page asks here instead, and a tier includes everything
 * below it.
 */
final class Access
{
    public const TIERS = ['view', 'member', 'director', 'admin'];

    public static function atLeast(string $tier, $user = null): bool
    {
        $user = $user ?? auth()->user();
        $rank = array_search($tier, self::TIERS, true);

        if (!$user || $rank === false) {
            return false;
        }

        foreach (array_slice(self::TIERS, $rank) as $held) {
            if ($user->can('ratting-manager.' . $held)) {
                return true;
            }
        }

        return false;
    }

    public static function isDirector($user = null): bool
    {
        return self::atLeast('director', $user);
    }

    public static function isAdmin($user = null): bool
    {
        return self::atLeast('admin', $user);
    }
}

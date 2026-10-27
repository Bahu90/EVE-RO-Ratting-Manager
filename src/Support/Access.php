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

    /** @var array<string, int[]> characters => corporations they are in now, for this request */
    private static array $corporationsNow = [];

    /** Forget the affiliations read so far, between queued jobs or in tests. */
    public static function forgetAffiliations(): void
    {
        self::$corporationsNow = [];
    }

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

    /**
     * Which of the tracked corporations these characters are in now, by
     * SeAT's character affiliations.
     *
     * @param int[] $characters
     * @param int[] $tracked
     * @return int[]
     */
    public static function trackedCorporationsOf(array $characters, array $tracked): array
    {
        if ($characters === [] || $tracked === []) {
            return [];
        }

        // Asked several times per request (the tier check, the director's
        // corporations, the page head), so each set of characters is read once.
        sort($characters);
        $key = implode(',', $characters);
        if (!array_key_exists($key, self::$corporationsNow)) {
            if (count(self::$corporationsNow) > 50) {
                self::$corporationsNow = [];
            }
            self::$corporationsNow[$key] = \Illuminate\Support\Facades\DB::table('character_affiliations')
                ->whereIn('character_id', $characters)
                ->pluck('corporation_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return array_values(array_intersect($tracked, self::$corporationsNow[$key]));
    }
}

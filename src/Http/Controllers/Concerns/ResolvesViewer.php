<?php

namespace RattingManager\Http\Controllers\Concerns;

use Illuminate\Support\Facades\DB;
use RattingManager\Services\Character\Players;
use RattingManager\Services\Configuration\Settings;
use RattingManager\Support\Access;

trait ResolvesViewer
{
    /** @return int[] */
    protected function viewerCharacters(Players $players): array
    {
        return $players->charactersOfUser(auth()->user());
    }

    protected function viewerMain(Players $players): int
    {
        $user = auth()->user();
        $main = (int) ($user->main_character_id ?? 0);
        $characters = $this->viewerCharacters($players);

        return $main > 0 ? $main : (int) ($characters[0] ?? 0);
    }

    /**
     * The tracked corporations the viewer's characters are in now.
     *
     * @return int[]
     */
    protected function viewerTrackedCorporations(Players $players, Settings $settings): array
    {
        return Access::trackedCorporationsOf($this->viewerCharacters($players), $settings->configuredCorporations());
    }

    /**
     * The configured corporations the viewer's characters are in now. A
     * member's leaderboard is their own corporation's; when none of their
     * characters is in one, the whole configured list stands in.
     *
     * @return int[]
     */
    protected function viewerCorporations(Players $players, Settings $settings): array
    {
        $mine = $this->viewerTrackedCorporations($players, $settings);

        return $mine !== [] ? $mine : $settings->configuredCorporations();
    }

    /**
     * The tracked corporations this director may see: all of them for an
     * admin, or when Settings lets directors see every tracked corporation;
     * otherwise the ones their own characters are in now.
     *
     * @return int[]
     */
    protected function directorCorporations(Settings $settings): array
    {
        $configured = $settings->configuredCorporations();

        if (Access::isAdmin() || $settings->get('access.director_scope') === 'all') {
            return $configured;
        }

        return $this->viewerTrackedCorporations(app(Players::class), $settings);
    }

    /**
     * The corporations a director may see one player's ratting in. A player
     * who neither rats for nor is now in any of them is not theirs to open.
     *
     * @param int[] $characters the player's characters
     * @return int[]
     */
    protected function ratterCorporations(array $characters, Settings $settings): array
    {
        $allowed = $this->directorCorporations($settings);
        abort_if($allowed === [], 403, trans('ratting-manager::common.no_corporation_access'));

        if ($allowed !== $settings->configuredCorporations()) {
            $theirs = Access::trackedCorporationsOf($characters, $allowed) !== []
                || DB::table('ratting_manager_daily_summaries')
                    ->whereIn('character_id', $characters ?: [0])
                    ->whereIn('corporation_id', $allowed)
                    ->exists();
            abort_unless($theirs, 404);
        }

        return $allowed;
    }

    /** Whether the viewer is a director with at least one corporation to see. */
    protected function seesCorporation(Settings $settings): bool
    {
        return Access::isDirector() && $this->directorCorporations($settings) !== [];
    }

    /**
     * Names of the corporations this director may see, for the corporation picker.
     *
     * @return array<int, string>
     */
    protected function corporationNames(Settings $settings): array
    {
        $configured = $this->directorCorporations($settings);
        $names = DB::table('corporation_infos')->whereIn('corporation_id', $configured ?: [0])->pluck('name', 'corporation_id')->all();

        $list = [];
        foreach ($configured as $id) {
            $list[$id] = $names[$id] ?? ('Corporation ' . $id);
        }

        return $list;
    }

    /**
     * The corporations a director asked for: one they may see, or all they
     * may see. A director with none (no character in a tracked corporation,
     * while directors only see their own) is turned away.
     *
     * @return int[]
     */
    protected function pickedCorporations($request, Settings $settings): array
    {
        $allowed = $this->directorCorporations($settings);
        abort_if($allowed === [] && $settings->configuredCorporations() !== [], 403, trans('ratting-manager::common.no_corporation_access'));

        $picked = (int) $request->query('corporation', 0);

        return in_array($picked, $allowed, true) ? [$picked] : $allowed;
    }
}

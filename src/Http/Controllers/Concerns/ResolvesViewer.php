<?php

namespace RattingManager\Http\Controllers\Concerns;

use Illuminate\Support\Facades\DB;
use RattingManager\Services\Character\Players;
use RattingManager\Services\Configuration\Settings;

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
     * The configured corporations the viewer's characters are in now. A
     * member's leaderboard is their own corporation's; when none of their
     * characters is in one, the whole configured list stands in.
     *
     * @return int[]
     */
    protected function viewerCorporations(Players $players, Settings $settings): array
    {
        $configured = $settings->configuredCorporations();
        $characters = $this->viewerCharacters($players);

        $own = $characters === [] ? [] : DB::table('character_affiliations')
            ->whereIn('character_id', $characters)
            ->pluck('corporation_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $mine = array_values(array_intersect($configured, $own));

        return $mine !== [] ? $mine : $configured;
    }

    /**
     * Names of the configured corporations, for the corporation picker.
     *
     * @return array<int, string>
     */
    protected function corporationNames(Settings $settings): array
    {
        $configured = $settings->configuredCorporations();
        $names = DB::table('corporation_infos')->whereIn('corporation_id', $configured ?: [0])->pluck('name', 'corporation_id')->all();

        $list = [];
        foreach ($configured as $id) {
            $list[$id] = $names[$id] ?? ('Corporation ' . $id);
        }

        return $list;
    }

    /** @return int[] the corporations a director asked for: one configured corporation, or all of them */
    protected function pickedCorporations($request, Settings $settings): array
    {
        $configured = $settings->configuredCorporations();
        $picked = (int) $request->query('corporation', 0);

        return in_array($picked, $configured, true) ? [$picked] : $configured;
    }
}

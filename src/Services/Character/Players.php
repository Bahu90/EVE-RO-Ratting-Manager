<?php

namespace RattingManager\Services\Character;

use Illuminate\Support\Facades\DB;

/**
 * Who a character belongs to, and what to call them.
 *
 * A player is a SeAT user: every character with a token on that user, shown
 * under the user's main. A character SeAT holds no token for is a player of
 * its own. Lookups are batched, so a leaderboard of a hundred players costs a
 * handful of queries.
 *
 * Names come from SeAT first, then the plugin's lookups. A character nobody
 * has named yet is asked for and shown as in progress; the background lookup
 * fills it in, and the page refreshes itself once it has.
 */
class Players
{
    public const WALLET_SCOPE = 'esi-wallet.read_character_wallet.v1';

    /** @var array<int, ?int> character_id => user_id */
    private array $userOf = [];

    /** @var array<int, int> user_id => main character_id */
    private array $mainOf = [];

    /** @var array<int, int[]> user_id => character ids */
    private array $charactersOf = [];

    /** @var array<int, string> */
    private array $names = [];

    /** @var array<int, true> characters this page showed as in progress */
    private array $pending = [];

    /** @var array<int, true> looked up, but no name was found anywhere */
    private array $unnamed = [];

    private AffiliationResolutionService $lookups;

    public function __construct(AffiliationResolutionService $lookups)
    {
        $this->lookups = $lookups;
    }

    /** @var array<int, bool> character_id => holds a live token with the wallet scope */
    private array $wallet = [];

    /** @param int[] $characterIds */
    public function load(array $characterIds): void
    {
        $missing = array_values(array_diff(array_map('intval', $characterIds), array_keys($this->userOf)));
        if ($missing === []) {
            return;
        }

        foreach (array_chunk($missing, 1000) as $chunk) {
            foreach ($chunk as $id) {
                $this->userOf[$id] = null;
                $this->wallet[$id] = false;
            }

            // Deleted tokens still say whose character it was, so a player who
            // removed a token keeps their history together.
            $tokens = DB::table('refresh_tokens')
                ->whereIn('character_id', $chunk)
                ->get(['character_id', 'user_id', 'scopes', 'deleted_at']);

            foreach ($tokens as $token) {
                $this->userOf[(int) $token->character_id] = $token->user_id ? (int) $token->user_id : null;
                $scopes = json_decode((string) $token->scopes, true) ?: [];
                $this->wallet[(int) $token->character_id] = $token->deleted_at === null
                    && in_array(self::WALLET_SCOPE, $scopes, true);
            }
        }

        $this->loadUsers(array_filter(array_unique(array_map(fn ($id) => $this->userOf[$id], $missing))));

        // Names in the same batch, but nobody is asked for until a page shows
        // them: a chart that only groups characters by player names nobody.
        $this->loadNames(array_merge($missing, array_values($this->mainOf)), false);
    }

    /** The character a player is shown under: the user's main, or the character itself. */
    public function mainOf(int $characterId): int
    {
        $this->load([$characterId]);
        $user = $this->userOf[$characterId];

        return $user && isset($this->mainOf[$user]) ? $this->mainOf[$user] : $characterId;
    }

    /** @return int[] every character of the player this character belongs to */
    public function charactersOfPlayer(int $characterId): array
    {
        $this->load([$characterId]);
        $user = $this->userOf[$characterId];

        return $user ? ($this->charactersOf[$user] ?? [$characterId]) : [$characterId];
    }

    /** @return int[] */
    public function charactersOfUser($user): array
    {
        if (!$user) {
            return [];
        }

        $this->loadUsers([(int) $user->id]);

        return $this->charactersOf[(int) $user->id] ?? [];
    }

    public function name(int $characterId): string
    {
        $this->loadNames([$characterId]);

        if (isset($this->names[$characterId])) {
            return $this->names[$characterId];
        }

        return isset($this->pending[$characterId])
            ? trans('ratting-manager::common.character_pending')
            : 'Character #' . $characterId;
    }

    /**
     * The name, or "Character #123" while it is still being looked up. For
     * text that is kept or sent, where "in progress" would outlive the lookup
     * it describes.
     */
    public function nameOrId(int $characterId): string
    {
        $this->loadNames([$characterId]);

        return $this->names[$characterId] ?? ('Character #' . $characterId);
    }

    /**
     * Characters this request showed as in progress, for the notice that
     * refreshes the page once they are in.
     *
     * @return int[]
     */
    public function shownPending(): array
    {
        return array_keys($this->pending);
    }

    /** The corporation the character is in now, from SeAT or the lookups. */
    public function currentCorporationName(int $characterId): ?string
    {
        $corporationId = DB::table('character_affiliations')->where('character_id', $characterId)->value('corporation_id');
        if ($corporationId) {
            return $this->lookups->corporationName((int) $corporationId) ?? ('Corporation ' . $corporationId);
        }

        $row = $this->lookups->known([$characterId])[$characterId] ?? null;

        return $row && $row->corporation_id
            ? ($row->corporation_name ?: $this->lookups->corporationName((int) $row->corporation_id))
            : null;
    }

    public function hasWalletToken(int $characterId): bool
    {
        $this->load([$characterId]);

        return $this->wallet[$characterId];
    }

    public function isKnownToSeat(int $characterId): bool
    {
        $this->load([$characterId]);

        return $this->userOf[$characterId] !== null;
    }

    /** @param int[] $userIds */
    private function loadUsers(array $userIds): void
    {
        $missing = array_values(array_diff(array_map('intval', $userIds), array_keys($this->charactersOf)));
        if ($missing === []) {
            return;
        }

        foreach (array_chunk($missing, 1000) as $chunk) {
            $mains = DB::table('users')->whereIn('id', $chunk)->pluck('main_character_id', 'id');

            $tokens = DB::table('refresh_tokens')->whereIn('user_id', $chunk)->get(['character_id', 'user_id', 'scopes', 'deleted_at']);

            foreach ($chunk as $user) {
                $this->charactersOf[$user] = [];
            }

            foreach ($tokens as $token) {
                $id = (int) $token->character_id;
                $user = (int) $token->user_id;
                $this->charactersOf[$user][] = $id;
                $this->userOf[$id] = $user;
                $scopes = json_decode((string) $token->scopes, true) ?: [];
                $this->wallet[$id] = $token->deleted_at === null && in_array(self::WALLET_SCOPE, $scopes, true);
            }

            foreach ($chunk as $user) {
                $main = (int) ($mains[$user] ?? 0);
                $this->mainOf[$user] = $main > 0 ? $main : ($this->charactersOf[$user][0] ?? 0);
            }
        }
    }

    /**
     * @param int[] $characterIds
     * @param bool $ask whether a character nobody has named yet is asked for
     *                  and shown as in progress, or left for when it is shown
     */
    private function loadNames(array $characterIds, bool $ask = true): void
    {
        $missing = array_values(array_filter(
            array_unique(array_map('intval', $characterIds)),
            fn ($id) => $id > 0 && !isset($this->names[$id]) && !isset($this->pending[$id]) && !isset($this->unnamed[$id])
        ));
        if ($missing === []) {
            return;
        }

        foreach (array_chunk($missing, 1000) as $chunk) {
            $this->names += DB::table('character_infos')->whereIn('character_id', $chunk)->pluck('name', 'character_id')->all();

            // Names SeAT has seen for characters it holds no record of.
            $left = array_values(array_diff($chunk, array_keys($this->names)));
            if ($left !== []) {
                $this->names += DB::table('universe_names')
                    ->whereIn('entity_id', $left)
                    ->where('category', 'character')
                    ->pluck('name', 'entity_id')
                    ->all();
            }

            $left = array_values(array_diff($chunk, array_keys($this->names)));
            if ($left === []) {
                continue;
            }

            $known = $this->lookups->known($left);
            $toAsk = [];
            foreach ($left as $id) {
                $row = $known[$id] ?? null;
                if ($row === null) {
                    if ($ask) {
                        $toAsk[] = $id;
                        $this->pending[$id] = true;
                    }
                } elseif (!empty($row->character_name)) {
                    $this->names[$id] = $row->character_name;
                } else {
                    $this->unnamed[$id] = true;
                }
            }

            $this->lookups->request($toAsk);
        }
    }
}

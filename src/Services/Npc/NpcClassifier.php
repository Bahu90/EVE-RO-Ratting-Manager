<?php

namespace RattingManager\Services\Npc;

use Illuminate\Support\Facades\DB;

class NpcClassifier
{
    /**
     * Classify every NPC type a tick has paid for that is not on file yet.
     * A classified type is never changed here, so a later rule never
     * rewrites what was already counted.
     */
    public function sync(): int
    {
        $missing = DB::table('ratting_manager_tick_kills as k')
            ->leftJoin('ratting_manager_npc_types as n', 'n.type_id', '=', 'k.npc_type_id')
            ->whereNull('n.type_id')
            ->distinct()
            ->pluck('k.npc_type_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $written = 0;
        foreach (array_chunk($missing, 500) as $chunk) {
            $sde = DB::table('invTypes as t')
                ->leftJoin('invGroups as g', 'g.groupID', '=', 't.groupID')
                ->whereIn('t.typeID', $chunk)
                ->get(['t.typeID', 't.typeName', 't.mass', 'g.groupID', 'g.groupName'])
                ->keyBy('typeID');

            $now = now();
            $rows = [];
            foreach ($chunk as $typeId) {
                $type = $sde->get($typeId);
                $rows[] = array_merge(
                    [
                        'type_id' => $typeId,
                        'name' => $type->typeName ?? null,
                        'group_id' => isset($type->groupID) ? (int) $type->groupID : null,
                        'group_name' => $type->groupName ?? null,
                        'mass' => isset($type->mass) ? (float) $type->mass : null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                    $this->classify(
                        $typeId,
                        $type->typeName ?? null,
                        isset($type->groupID) ? (int) $type->groupID : null,
                        $type->groupName ?? null,
                        isset($type->mass) ? (float) $type->mass : null
                    )
                );
            }

            DB::table('ratting_manager_npc_types')->insertOrIgnore($rows);
            $written += count($rows);
        }

        return $written;
    }

    /** @return array{faction: string, size: string, rank: string, classified_by: string} */
    public function classify(int $typeId, ?string $name, ?int $groupId, ?string $groupName, ?float $mass): array
    {
        if (isset(NpcRegistry::TYPES[$typeId])) {
            [$faction, $size, $rank] = NpcRegistry::TYPES[$typeId];

            return ['faction' => $faction, 'size' => $size, 'rank' => $rank, 'classified_by' => 'registry'];
        }

        if ($name === null && $groupName === null) {
            return ['faction' => 'other', 'size' => 'unknown', 'rank' => 'regular', 'classified_by' => 'unknown'];
        }

        $faction = NpcRegistry::GROUP_FACTIONS[$groupId] ?? $this->match(NpcRegistry::FACTION_PATTERNS, $groupName, $name) ?? 'other';
        $rank = $this->match(NpcRegistry::RANK_PATTERNS, $groupName, $name) ?? 'regular';

        $size = $this->match(NpcRegistry::SIZE_PATTERNS, $groupName, $name);
        $by = 'group';

        if ($size === null) {
            $size = $this->sizeFromMass($mass);
            $by = $size === 'unknown' ? 'unknown' : 'mass';
        }

        return ['faction' => $faction, 'size' => $size, 'rank' => $rank, 'classified_by' => $by];
    }

    private function match(array $patterns, ?string ...$texts): ?string
    {
        foreach ($texts as $text) {
            if ($text === null || $text === '') {
                continue;
            }
            foreach ($patterns as $key => $pattern) {
                if (preg_match($pattern, $text)) {
                    return $key;
                }
            }
        }

        return null;
    }

    private function sizeFromMass(?float $mass): string
    {
        if (!$mass || $mass <= 0) {
            return 'unknown';
        }

        foreach (NpcRegistry::MASS_BANDS as $size => $limit) {
            if ($mass < $limit) {
                return $size;
            }
        }

        return 'capital';
    }
}

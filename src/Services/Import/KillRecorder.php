<?php

namespace RattingManager\Services\Import;

use Illuminate\Support\Facades\DB;
use RattingManager\Models\Tick;

class KillRecorder
{
    /**
     * Keep the rats a tick paid for. Both journals carry the same kill list,
     * so whichever side arrives first records it and the other leaves it.
     */
    public function record(Tick $tick, ?string $reason): void
    {
        if ($tick->kill_group !== null) {
            return;
        }

        $kills = ReasonParser::kills($reason);
        if ($kills === []) {
            return;
        }

        $tick->kill_group = ReasonParser::killGroup(
            $tick->ticked_at->format('Y-m-d H:i:s'),
            $tick->solar_system_id,
            $kills
        );
        $tick->npc_kills = array_sum($kills);
        $tick->save();

        $rows = [];
        foreach ($kills as $typeId => $quantity) {
            $rows[] = ['tick_id' => $tick->id, 'npc_type_id' => $typeId, 'quantity' => $quantity];
        }

        DB::table('ratting_manager_tick_kills')->insertOrIgnore($rows);
    }
}

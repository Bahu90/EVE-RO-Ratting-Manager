<?php

namespace RattingManager\Console\Commands;

use Illuminate\Console\Command;
use RattingManager\Console\Commands\Concerns\HoldsThePipelineLock;
use RattingManager\Services\Npc\NpcClassifier;

class SyncNpcTypesCommand extends Command
{
    use HoldsThePipelineLock;

    protected $signature = 'ratting-manager:sync-npc-types';

    protected $description = 'Classify NPC types seen in bounty ticks by faction, size and rank';

    public function handle(NpcClassifier $classifier)
    {
        return $this->underLock(1800, function () use ($classifier) {
            $written = $classifier->sync();
            $this->info("Classified {$written} new NPC type(s).");

            return Command::SUCCESS;
        });
    }
}

<?php

namespace RattingManager\Console\Commands\Concerns;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use RattingManager\Services\Ratting\FigureCache;

/**
 * Every command that writes ratting data takes the same lock, so a manual run
 * can never interleave with the scheduled one and pair or rebuild the same
 * rows twice at once. Letting go of it tells the cached figures the data has
 * moved on.
 */
trait HoldsThePipelineLock
{
    protected function underLock(int $seconds, callable $work): int
    {
        $lock = Cache::lock('ratting-manager:pipeline', $seconds);

        if (!$lock->get()) {
            $this->warn('Another Ratting Manager import is running. Try again when it has finished.');
            return Command::SUCCESS;
        }

        try {
            return $work();
        } finally {
            FigureCache::changed();
            $lock->release();
        }
    }

    protected function since(): \Carbon\Carbon
    {
        if ($this->hasOption('from') && $this->option('from')) {
            return \Carbon\Carbon::parse($this->option('from'))->startOfDay();
        }

        return now()->subDays(max(1, (int) $this->option('days')))->startOfDay();
    }
}

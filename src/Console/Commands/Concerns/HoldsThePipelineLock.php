<?php

namespace RattingManager\Console\Commands\Concerns;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use RattingManager\Services\Ratting\FigureCache;

/**
 * Every command that writes ratting data takes the same lock, so a manual run
 * can never interleave with the scheduled one and pair or rebuild the same
 * rows twice at once. Letting go of it tells the cached figures the data has
 * moved on, unless the command marked its run as having changed nothing.
 */
trait HoldsThePipelineLock
{
    /**
     * Set by a command whose run turned out to write nothing the figures
     * are built from, so the cached figures are kept instead of thrown away.
     */
    protected bool $dataUnchanged = false;

    protected function underLock(int $seconds, callable $work): int
    {
        $this->dataUnchanged = false;

        $lock = Cache::lock('ratting-manager:pipeline', $seconds);

        if (!$lock->get()) {
            $this->warn('Another Ratting Manager import is running. Try again when it has finished.');
            return Command::SUCCESS;
        }

        try {
            return $work();
        } finally {
            if (!$this->dataUnchanged) {
                FigureCache::changed();
            }
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

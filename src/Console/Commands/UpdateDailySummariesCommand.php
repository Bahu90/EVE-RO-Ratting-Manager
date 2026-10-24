<?php

namespace RattingManager\Console\Commands;

use Illuminate\Console\Command;
use RattingManager\Console\Commands\Concerns\HoldsThePipelineLock;
use RattingManager\Services\Ratting\DailySummaryBuilder;

class UpdateDailySummariesCommand extends Command
{
    use HoldsThePipelineLock;

    protected $signature = 'ratting-manager:update-daily-summaries
                            {--days=2 : How many days back to read}
                            {--from= : Read from this date instead (YYYY-MM-DD)}';

    protected $description = 'Rebuild the daily ratting summaries';

    public function handle(DailySummaryBuilder $builder)
    {
        return $this->underLock(1800, function () use ($builder) {
            $result = $builder->rebuild($this->since());
            $this->info("Wrote {$result['summaries']} summary row(s) and {$result['kills']} kill row(s).");

            return Command::SUCCESS;
        });
    }
}

<?php

namespace RattingManager\Console\Commands;

use Illuminate\Console\Command;
use RattingManager\Console\Commands\Concerns\HoldsThePipelineLock;
use RattingManager\Services\Ratting\FigureWarmer;
use RattingManager\Services\Ratting\Pipeline;
use Throwable;

/**
 * The scheduled run: reads the last two days of both journals and brings
 * every figure built on them up to date.
 *
 * Two days, because SeAT can take a while to pull a journal and a tick near
 * midnight can land after the day it belongs to has been built once.
 */
class ProcessCommand extends Command
{
    use HoldsThePipelineLock;

    protected $signature = 'ratting-manager:process
                            {--days=2 : How many days back to read}
                            {--from= : Read from this date instead (YYYY-MM-DD)}';

    protected $description = 'Import bounty ticks and ESS payouts and update sessions and daily summaries';

    public function handle(Pipeline $pipeline, FigureWarmer $warmer)
    {
        $ran = false;

        $status = $this->underLock(1800, function () use ($pipeline, &$ran) {
            $ran = true;
            $since = $this->since();
            $this->info('Ratting Manager - reading from ' . $since->toDateString());

            $report = $pipeline->run($since, fn ($step) => $this->line('  ' . $step . '...'));

            if (isset($report['note'])) {
                $this->warn($report['note']);
                return Command::SUCCESS;
            }

            foreach (['corporation_journal' => 'Corporation journal', 'character_journal' => 'Character journals'] as $key => $label) {
                foreach (['bounty' => 'ticks', 'ess' => 'ESS payouts'] as $stream => $name) {
                    $r = $report[$key][$stream];
                    $this->line(sprintf(
                        '%s, %s: %d read, %d new, %d paired, %d updated%s',
                        $label, $name, $r['read'], $r['created'], $r['paired'], $r['updated'],
                        $r['skipped'] ? ' (left out: ' . http_build_query($r['skipped'], '', ', ') . ')' : ''
                    ));
                }
            }

            $this->line(sprintf(
                'Corp-only rows worked out: %d, waiting on a tax rate: %d',
                $report['estimates']['worked_out'], $report['estimates']['no_rate']
            ));
            $this->line(sprintf(
                'New NPC types: %d, new special spawns: %d, sessions built: %d, daily rows: %d',
                $report['npc_types'], $report['special_spawns'], $report['sessions'], $report['daily']['summaries']
            ));
            $this->info('Done in ' . $report['seconds'] . 's');

            return Command::SUCCESS;
        });

        // After the lock, which moves the cached figures on when it is let go.
        if ($ran) {
            try {
                $warmer->warm();
            } catch (Throwable $e) {
                $this->warn('The figures could not be worked out ahead of time: ' . $e->getMessage());
            }
        }

        return $status;
    }
}

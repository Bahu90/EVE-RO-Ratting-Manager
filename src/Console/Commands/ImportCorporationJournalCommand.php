<?php

namespace RattingManager\Console\Commands;

use Illuminate\Console\Command;
use RattingManager\Console\Commands\Concerns\HoldsThePipelineLock;
use RattingManager\Services\Import\JournalImporter;

class ImportCorporationJournalCommand extends Command
{
    use HoldsThePipelineLock;

    protected $signature = 'ratting-manager:import-corp-journal
                            {--days=2 : How many days back to read}
                            {--from= : Read from this date instead (YYYY-MM-DD)}';

    protected $description = 'Import bounty ticks and ESS payouts from the wallet journals of the configured corporations';

    public function handle(JournalImporter $importer)
    {
        return $this->underLock(1800, function () use ($importer) {
            $report = $importer->importCorporation($this->since());
            $this->printReport($report);

            return Command::SUCCESS;
        });
    }

    private function printReport(array $report): void
    {
        if (isset($report['note'])) {
            $this->warn($report['note']);
            return;
        }

        foreach (['bounty' => 'Ticks', 'ess' => 'ESS payouts'] as $stream => $label) {
            $r = $report[$stream];
            $this->line(sprintf('%s: %d read, %d new, %d paired, %d updated', $label, $r['read'], $r['created'], $r['paired'], $r['updated']));
            foreach ($r['skipped'] as $why => $count) {
                $this->line("  left out ({$why}): {$count}");
            }
        }
    }
}

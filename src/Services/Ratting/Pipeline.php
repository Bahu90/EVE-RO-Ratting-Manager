<?php

namespace RattingManager\Services\Ratting;

use Carbon\Carbon;
use RattingManager\Services\Configuration\Settings;
use RattingManager\Services\Import\JournalImporter;
use RattingManager\Services\Npc\NpcClassifier;
use RattingManager\Services\Npc\SpawnDetector;

/**
 * Every step from journal rows to dashboard figures, in the order each one
 * needs the last: rows first, then the rates that need paired rows, then
 * everything built on the rows.
 */
class Pipeline
{
    private Settings $settings;
    private JournalImporter $importer;
    private TaxRates $taxRates;
    private NpcClassifier $npcs;
    private SpawnDetector $spawns;
    private SessionBuilder $sessions;
    private DailySummaryBuilder $summaries;

    public function __construct(
        Settings $settings,
        JournalImporter $importer,
        TaxRates $taxRates,
        NpcClassifier $npcs,
        SpawnDetector $spawns,
        SessionBuilder $sessions,
        DailySummaryBuilder $summaries
    ) {
        $this->settings = $settings;
        $this->importer = $importer;
        $this->taxRates = $taxRates;
        $this->npcs = $npcs;
        $this->spawns = $spawns;
        $this->sessions = $sessions;
        $this->summaries = $summaries;
    }

    /** How many steps run() reports, for a caller numbering them. */
    public const STEPS = 7;

    /**
     * @param Progress|callable|null $progress a Progress, or a callable given each step's name as it starts
     */
    public function run(Carbon $since, $progress = null): array
    {
        $progress = $progress instanceof Progress ? $progress : new Progress($progress);
        $count = $progress->counting();

        $corporations = $this->settings->configuredCorporations();
        $report = ['since' => $since->toDateTimeString(), 'corporations' => $corporations];

        if ($corporations === []) {
            $report['note'] = 'No corporation is configured. Pick one on the Settings page.';
            return $report;
        }

        $started = microtime(true);

        $progress->step('Corporation journal', $count ? $this->importer->corporationRowCount($since) : 0);
        $report['corporation_journal'] = $this->importer->importCorporation($since, $progress);

        $progress->step('Character journals', $count ? $this->importer->characterRowCount($since) : 0);
        $report['character_journal'] = $this->importer->importCharacter($since, $progress);

        $progress->step('Tax rates', $count ? $this->taxRates->rowCount($corporations, $since) : 0);
        $report['tax_rates'] = [
            'snapshot' => $this->taxRates->snapshot($corporations),
            'learned_days' => $this->taxRates->learn($corporations, $since, $progress),
        ];
        $report['estimates'] = $this->taxRates->estimate($corporations, $since, $progress);

        $progress->step('NPC types', 1);
        $report['npc_types'] = $this->npcs->sync();
        $reclassified = $this->npcs->reclassifyIfRulesChanged();
        if ($reclassified !== null) {
            $report['npc_types_reclassified'] = $reclassified;
        }
        $progress->advance();

        // New rules can change which rats count, back to the first tick, so
        // the whole list is gone through once instead of the usual window.
        $progress->step('Special spawns', 1);
        if ($reclassified !== null) {
            $rebuilt = $this->spawns->rebuild();
            $report['special_spawns'] = $rebuilt['found'];
            $report['special_spawns_removed'] = $rebuilt['removed'];
        } else {
            $report['special_spawns'] = $this->spawns->detect($since);
        }
        $progress->advance();

        $progress->step('Sessions', $count ? $this->sessions->characterCount($since) : 0);
        $report['sessions'] = $this->sessions->rebuild($since, $progress);

        $progress->step('Daily summaries', $count ? DailySummaryBuilder::dayCount($since) : 0);
        $report['daily'] = $this->summaries->rebuild($since, null, $progress);

        $report['seconds'] = round(microtime(true) - $started, 1);

        $this->settings->set('state.last_process_at', now()->toDateTimeString());
        $this->settings->set('state.last_process_report', $report);

        return $report;
    }
}

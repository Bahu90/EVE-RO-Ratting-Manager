<?php

namespace RattingManager\Services\Ratting;

use RattingManager\Services\Configuration\Settings;

/**
 * Works out the figures the Corporation tab and the Leaderboard open with
 * right after an import, so the first director to open them does not wait
 * for the month's ticks to be read.
 */
class FigureWarmer
{
    private Settings $settings;
    private RattingMetrics $metrics;
    private Insights $insights;
    private MonthlyBreakdown $monthly;

    public function __construct(Settings $settings, RattingMetrics $metrics, Insights $insights, MonthlyBreakdown $monthly)
    {
        $this->settings = $settings;
        $this->metrics = $metrics;
        $this->insights = $insights;
        $this->monthly = $monthly;
    }

    public function warm(): void
    {
        $corporations = $this->settings->configuredCorporations();
        if ($corporations === []) {
            return;
        }

        $scope = new Scope($corporations);
        $period = Period::from(null);

        $this->metrics->collect($scope, $period->start, $period->end);
        $this->insights->for($scope, $period, true);
        $this->monthly->corporationMonths($scope);
    }
}

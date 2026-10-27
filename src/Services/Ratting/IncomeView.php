<?php

namespace RattingManager\Services\Ratting;

use RattingManager\Services\Configuration\Settings;

/**
 * Which income the pages lead with, and the figures that go with it.
 *
 * - ratted: ISK ratted the way the corporation counts it, its cut of
 *   bounties and ESS payouts over its tax rate (the gross it taxed). A
 *   player the corporation took no cut from in the period is left out of
 *   the leaderboard and the totals.
 * - received: what pilots received, from their own wallet journals, with
 *   corporation-only ticks worked out from the corporation's cut.
 */
final class IncomeView
{
    public const RATTED = 'ratted';
    public const RECEIVED = 'received';

    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function mode(): string
    {
        return $this->settings->get('display.income') === self::RECEIVED ? self::RECEIVED : self::RATTED;
    }

    public function isRatted(): bool
    {
        return $this->mode() === self::RATTED;
    }

    /** The alliance's share of ISK ratted, 0.075 for 7.5%; 0 when none is set. */
    public function allianceRate(): float
    {
        $percent = (float) str_replace(',', '.', (string) $this->settings->get('display.alliance_tax_percent'));

        return $percent > 0 && $percent < 100 ? $percent / 100 : 0.0;
    }
}

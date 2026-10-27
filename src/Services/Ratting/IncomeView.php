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

    /** @var int[]|null tracked corporations whose tax rate SeAT holds as 0% now */
    private ?array $untaxed = null;

    /**
     * The mode picked in Settings, except that ISK ratted is worked out from
     * the corporation's cut: with every tracked corporation at 0% tax there
     * is no cut and every figure would be 0, so the pages show what pilots
     * received instead.
     */
    public function mode(): string
    {
        if ($this->settings->get('display.income') === self::RECEIVED) {
            return self::RECEIVED;
        }

        $tracked = $this->settings->configuredCorporations();

        return $tracked !== [] && count($this->untaxedCorporations()) === count($tracked) ? self::RECEIVED : self::RATTED;
    }

    /**
     * The tracked corporations at 0% tax now, by SeAT's corporation sheet.
     * A corporation SeAT holds no rate for is not counted as untaxed.
     *
     * @return int[]
     */
    public function untaxedCorporations(): array
    {
        if ($this->untaxed === null) {
            $tracked = $this->settings->configuredCorporations();
            $this->untaxed = $tracked === [] ? [] : \Illuminate\Support\Facades\DB::table('corporation_infos')
                ->whereIn('corporation_id', $tracked)
                ->where('tax_rate', '<=', 0)
                ->pluck('corporation_id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return $this->untaxed;
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

<?php

namespace RattingManager\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RattingManager\Support\Format;

/** ISK and hours as the corporation's reports write them. */
final class FormatTest extends TestCase
{
    public function test_isk_units(): void
    {
        $this->assertSame('57.38 B', Format::isk(57_380_000_000));
        $this->assertSame('788.07 M', Format::isk(788_070_000));
        $this->assertSame('660.60 K', Format::isk(660_600));
        $this->assertSame('1.20 T', Format::isk(1_200_000_000_000));
        $this->assertSame('950', Format::isk(950));
    }

    public function test_isk_keeps_the_sign(): void
    {
        $this->assertSame('-2.50 M', Format::isk(-2_500_000));
    }

    public function test_hours(): void
    {
        $this->assertSame('45m', Format::hours(45));
        $this->assertSame('1h 00m', Format::hours(60));
        $this->assertSame('12h 05m', Format::hours(725));
    }
}

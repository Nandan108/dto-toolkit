<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\Enum\Phase;
use PHPUnit\Framework\TestCase;

final class PhaseTest extends TestCase
{
    public function testPhaseCanReportItsComponents(): void
    {
        $phase = Phase::InboundCast;
        $this->assertFalse($phase->isOutbound());
        $this->assertFalse($phase->isIOBound());

        $phase = Phase::OutboundCast;
        $this->assertTrue($phase->isOutbound());
        $this->assertFalse($phase->isIOBound());

        $phase = Phase::InboundLoad;
        $this->assertFalse($phase->isOutbound());
        $this->assertTrue($phase->isIOBound());

        $phase = Phase::OutboundExport;
        $this->assertTrue($phase->isOutbound());
        $this->assertTrue($phase->isIOBound());
    }
}

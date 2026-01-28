<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Support;

use Nandan108\DtoToolkit\Support\CardSchemeDetector;
use PHPUnit\Framework\TestCase;

final class CardSchemeDetectorTest extends TestCase
{
    public function testSupportedSchemesIncludesKnownValues(): void
    {
        $schemes = CardSchemeDetector::supportedSchemes();

        $this->assertContains(CardSchemeDetector::VISA, $schemes);
        $this->assertContains(CardSchemeDetector::TROY, $schemes);
    }

    public function testMatchesSchemeReturnsTrueForValidNumber(): void
    {
        $this->assertTrue(CardSchemeDetector::matchesScheme('4111111111111111', CardSchemeDetector::VISA));
        $this->assertTrue(CardSchemeDetector::matchesScheme('9792000000000000', CardSchemeDetector::TROY));
    }

    public function testMatchesSchemeReturnsFalseForMismatch(): void
    {
        $this->assertFalse(CardSchemeDetector::matchesScheme('4111111111111111', CardSchemeDetector::MASTERCARD));
    }
}

<?php

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\CastTo\DateTimeFromLocalized;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Exception\CastingException;
use PHPUnit\Framework\TestCase;

final class DateTimeFromLocalizedTest extends TestCase
{
    #[\Override]
    public function setUp(): void
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('intl extension not available');
        }
    }

    public function testParsesStandardLocaleShortStyle(): void
    {
        $dtoClass = new class extends FullDto {
            #[DateTimeFromLocalized(locale: 'fr_CH')]
            public \DateTimeInterface|string|null $dt = null;
        };

        foreach (['04.05.2025', '04.05.25'] as $date) {
            $dt = $dtoClass::fromArray(['dt' => "$date 14:30"])->dt;
            if (is_string($dt)) {
                $this->fail('Expected a DateTimeInterface, got string');
            }
            $this->assertSame('2025-05-04 14:30', $dt?->format('Y-m-d H:i'));
        }
    }

    public function testParsesWithPatternOverride(): void
    {
        $dtoClass = new class extends FullDto {
            #[DateTimeFromLocalized(locale: 'fr_CH', pattern: 'dd.MM.yyyy HH:mm')]
            public \DateTimeInterface|string|null $dt = null;
        };

        $dt = $dtoClass::fromArray(['dt' => '04.05.2025 14:30'])->dt;
        if (is_string($dt)) {
            $this->fail('Expected a DateTimeInterface, got string');
        }
        $this->assertSame('2025-05-04 14:30', $dt?->format('Y-m-d H:i'));
    }

    public function testParsesWithTimezoneOverride(): void
    {
        $dtoClass = new class extends FullDto {
            #[DateTimeFromLocalized(locale: 'fr_CH', timezone: 'Europe/Paris')]
            public \DateTimeInterface|string|null $dt = null;
        };

        $dt = $dtoClass::fromArray(['dt' => '04.05.25 14:30'])->dt;

        if (is_string($dt)) {
            $this->fail('Expected a DateTimeInterface, got string');
        }
        $this->assertSame('Europe/Paris', $dt?->getTimezone()?->getName());
    }

    public function testThrowsOnEmptyString(): void
    {
        $dtoClass = new class extends FullDto {
            #[DateTimeFromLocalized(locale: 'fr_CH')]
            public \DateTimeInterface|string|null $dt = null;
        };

        $this->expectException(CastingException::class);
        $dtoClass::fromArray(['dt' => '']);
    }

    public function testThrowsOnMalformedString(): void
    {
        $dtoClass = new class extends FullDto {
            #[DateTimeFromLocalized(locale: 'fr_CH')]
            public \DateTimeInterface|string|null $dt = null;
        };

        $this->expectException(CastingException::class);
        $dtoClass::fromArray(['dt' => 'Not a date']);
    }
}

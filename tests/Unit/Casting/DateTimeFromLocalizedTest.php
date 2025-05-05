<?php

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\CastTo\DateTimeFromLocalized;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Exception\CastingException;
use PHPUnit\Framework\TestCase;

final class DateTimeFromLocalizedTest extends TestCase
{
    public function testParsesStandardLocaleShortStyle(): void
    {
        extension_loaded('intl') or $this->markTestSkipped('intl extension not available');

        $dtoClass = new class extends FullDto {
            #[DateTimeFromLocalized(locale: 'fr_CH')]
            public \DateTimeInterface|string|null $dt = null;
        };

        foreach (['04.05.2025', '04.05.25'] as $date) {
            $dto = $dtoClass::fromArray(['dt' => "$date 14:30"]);
            $this->assertInstanceOf(\DateTimeImmutable::class, $dto->dt);
            $this->assertSame('2025-05-04 14:30', $dto->dt->format('Y-m-d H:i'));
        }
    }

    public function testParsesWithPatternOverride(): void
    {
        $dtoClass = new class extends FullDto {
            #[DateTimeFromLocalized(locale: 'fr_CH', pattern: 'dd.MM.yyyy HH:mm')]
            public \DateTimeInterface|string|null $dt = null;
        };

        $dto = $dtoClass::fromArray(['dt' => '04.05.2025 14:30']);
        $this->assertSame('2025-05-04 14:30', $dto->dt->format('Y-m-d H:i'));
    }

    public function testParsesWithTimezoneOverride(): void
    {
        $dtoClass = new class extends FullDto {
            #[DateTimeFromLocalized(locale: 'fr_CH', timezone: 'Europe/Paris')]
            public \DateTimeInterface|string|null $dt = null;
        };

        $dto = $dtoClass::fromArray(['dt' => '04.05.25 14:30']);
        $this->assertSame('Europe/Paris', $dto->dt->getTimezone()->getName());
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

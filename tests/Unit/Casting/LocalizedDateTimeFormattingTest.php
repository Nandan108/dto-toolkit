<?php

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\CastTo\LocalizedDateTime;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Exception\CastingException;
use PHPUnit\Framework\TestCase;

final class LocalizedDateTimeFormattingTest extends TestCase
{
    public function testLocalizedDateTimeWithShortFormat(): void
    {
        extension_loaded('intl') or $this->markTestSkipped('intl extension not loaded');

        $dtoClass = new class extends FullDto {
            #[LocalizedDateTime(locale: 'fr_FR')]
            public \DateTimeInterface|string|null $date = null;
        };

        $dto = $dtoClass::fromArray([
            'date' => new \DateTimeImmutable('2025-01-01 12:34'),
        ]);

        $this->assertSame('01/01/2025 12:34', $dto->date);
    }

    public function testLocalizedDateTimeThrowsIfInputIsNotDateTimeInterface(): void
    {
        extension_loaded('intl') or $this->markTestSkipped('intl extension not loaded');

        $dtoClass = new class extends FullDto {
            #[LocalizedDateTime(locale: 'fr_FR')]
            public \DateTimeInterface|string|null $date = null;
        };

        $this->expectException(CastingException::class);
        $this->expectExceptionMessage('Value must be a DateTimeInterface');

        $dtoClass::fromArray(['date' => '2025-01-01 12:34']);
    }

    public function testLocalizedDateTimeWithCustomPattern(): void
    {
        $dtoClass = new class extends FullDto {
            #[LocalizedDateTime(locale: 'en_US', pattern: 'yyyy/MM/dd')]
            public \DateTimeInterface|string|null $date = null;
        };

        $dto = $dtoClass::fromArray([
            'date' => new \DateTimeImmutable('2025-04-30'),
        ]);

        $this->assertSame('2025/04/30', $dto->date);
    }

    public function testDateTimeFormatterFailure(): void
    {
        $this->expectException(CastingException::class);
        $this->expectExceptionMessage('Invalid date formater arguments');

        $dtoClass = new class extends FullDto {
            #[LocalizedDateTime(locale: 'en_US', dateStyle: 999999)]
            public \DateTimeInterface|string|null $date = null;
        };

        $dtoClass::fromArray(['date' => new \DateTimeImmutable()]);
    }
}

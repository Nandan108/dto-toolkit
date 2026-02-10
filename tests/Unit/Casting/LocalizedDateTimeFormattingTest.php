<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\CastTo\LocalizedDateTime;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Exception\Process\TransformException;
use PHPUnit\Framework\TestCase;

final class LocalizedDateTimeFormattingTest extends TestCase
{
    #[\Override]
    public function setUp(): void
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('intl extension not available');
        }
    }

    public function testLocalizedDateTimeWithShortFormat(): void
    {
        $dtoClass = new class extends FullDto {
            #[LocalizedDateTime(locale: 'fr_FR')]
            public \DateTimeInterface | string | null $date = null;
        };

        $dto = $dtoClass::newFromArray([
            'date' => new \DateTimeImmutable('2025-01-01 12:34'),
        ]);

        $this->assertSame('01/01/2025 12:34', $dto->date);
    }

    public function testLocalizedDateTimeThrowsIfInputIsNotDateTimeInterface(): void
    {
        $dtoClass = new class extends FullDto {
            #[LocalizedDateTime(locale: 'fr_FR')]
            public \DateTimeInterface | string | null $date = null;
        };

        $this->expectException(TransformException::class);
        $this->expectExceptionMessage('Expected a DateTimeInterface instance, got a string.');

        $dtoClass::newFromArray(['date' => '2025-01-01 12:34']);
    }

    public function testLocalizedDateTimeWithCustomPattern(): void
    {
        $dtoClass = new class extends FullDto {
            #[LocalizedDateTime(locale: 'en_US', pattern: 'yyyy/MM/dd')]
            public \DateTimeInterface | string | null $date = null;
        };

        $dto = $dtoClass::newFromArray([
            'date' => new \DateTimeImmutable('2025-04-30'),
        ]);

        $this->assertSame('2025/04/30', $dto->date);
    }

    public function testDateTimeFormatterFailure(): void
    {
        $this->expectException(InvalidConfigException::class);

        $dtoClass = new class extends FullDto {
            #[LocalizedDateTime(locale: 'en_US', dateStyle: 999999)]
            public \DateTimeInterface | string | null $date = null;
        };

        $dtoClass::newFromArray(['date' => new \DateTimeImmutable()]);
    }
}

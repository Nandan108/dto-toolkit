<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\CastTo\DateTime;
use Nandan108\DtoToolkit\CastTo\LocalizedDateTime;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Enum\DateTimeFormat;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use PHPUnit\Framework\TestCase;

final class UsesTimezoneResolverTest extends TestCase
{
    #[\Override]
    public function setUp(): void
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('intl extension not available');
        }
    }

    public function testSadPaths(): void
    {
        // normal path
        $dto = new class extends FullDto {
            #[DateTime(DateTimeFormat::SQL, timezone: 'Europe/Paris')]
            public \DateTimeInterface | string | null $date = null;
        };

        $date = $dto->loadArray(['date' => '2025-10-05 12:34:00'])->date;
        $date instanceof \DateTimeInterface || $this->fail('Value was not cast to DateTimeInterface');

        $this->assertSame('2025-10-05 12:34:00', $date->format('Y-m-d H:i:s'));
        $this->assertSame('Europe/Paris', $date->getTimezone()->getName());

        // normal path a second time - covers cached value path
        $dto = new class extends FullDto {
            #[DateTime(DateTimeFormat::SQL, timezone: 'Europe/Paris')]
            public \DateTimeInterface | string | null $date = null;
        };

        $date = $dto->loadArray(['date' => '2025-10-05 12:34:00'])->date;
        $this->assertInstanceOf(\DateTimeInterface::class, $date);
        $date instanceof \DateTimeInterface || $this->fail('Value was not cast to DateTimeInterface');
        $this->assertSame('2025-10-05 12:34:00', $date->format('Y-m-d H:i:s'));
        $this->assertSame('Europe/Paris', $date->getTimezone()->getName());

        // fallback (timzone not provided) -> null -> no timezone applied
        // -> keeps default timezone from parsed value, which is UTC for SQL format
        $dto = new class extends FullDto {
            #[DateTime(DateTimeFormat::SQL)]
            public \DateTimeInterface | string | null $date = null;
        };

        $date = $dto->loadArray(['date' => '2025-10-05 12:34:00'])->date;
        $date instanceof \DateTimeInterface || $this->fail('Value was not cast to DateTimeInterface');
        $this->assertSame('UTC', $date->getTimezone()->getName());

        // fallback (timzone not provided) -> null -> no timezone applied
        // -> keeps timezone from $value, which is UTC for SQL format
        $dto = new class extends FullDto {
            #[LocalizedDateTime(
                dateStyle: \IntlDateFormatter::MEDIUM,
                timeStyle: \IntlDateFormatter::SHORT,
                locale: 'fr_CH',
            )]
            public \DateTimeInterface | string | null $date = null;
        };

        $dto->loadArray(['date' => new \DateTimeImmutable('2025-10-05T12:34:00+02:00')]);
        $this->assertSame('5 oct. 2025, 12:34', $dto->date);

        // checkValid(): invalid string value
        try {
            $dto = new class extends FullDto {
                #[DateTime(DateTimeFormat::SQL, timezone: 'not-a-timezone')]
                public \DateTimeInterface | string | null $date = null;
            };

            $dto->loadArray(['date' => '2025-10-05 12:34:00']);
        } catch (InvalidConfigException $e) {
            $this->assertStringContainsString('Cannot resolve timezone from "not-a-timezone"', $e->getMessage());
        }
    }
}

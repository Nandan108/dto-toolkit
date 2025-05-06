<?php

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\CastTo\DateTimeString;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Enum\DateTimeFormat;
use PHPUnit\Framework\TestCase;

final class DateTimeStringTest extends TestCase
{
    public function testFormatsToIsoStringByDefault(): void
    {
        $dtoClass = new class extends FullDto {
            #[DateTimeString]
            public \DateTimeInterface|string|null $dt = null;
        };

        $dto = $dtoClass::fromArray(['dt' => new \DateTimeImmutable('2025-05-04T12:34:56+02:00')]);

        $this->assertSame('2025-05-04T12:34:56+02:00', $dto->dt);
    }

    public function testUsesEnumFormat(): void
    {
        $dtoClass = new class extends FullDto {
            #[DateTimeString(format: DateTimeFormat::SQL)]
            public \DateTimeInterface|string|null $dt = null;
        };

        $dto = $dtoClass::fromArray(['dt' => new \DateTimeImmutable('2025-05-04T12:34:56+02:00')]);

        $this->assertSame('2025-05-04 12:34:56', $dto->dt);
    }

    public function testUsesCustomPattern(): void
    {
        $dtoClass = new class extends FullDto {
            #[DateTimeString(pattern: 'd.m.Y H:i')]
            public \DateTimeInterface|string|null $dt = null;
        };

        $dto = $dtoClass::fromArray(['dt' => new \DateTimeImmutable('2025-05-04 14:30')]);

        $this->assertSame('04.05.2025 14:30', $dto->dt);
    }

    public function testUsesTimezoneOverride(): void
    {
        $dtoClass = new class extends FullDto {
            #[DateTimeString(format: DateTimeFormat::SQL, timezone: 'UTC')]
            public \DateTimeInterface|string|null $dt = null;
        };

        $dto = $dtoClass::fromArray(['dt' => new \DateTimeImmutable('2025-05-04 14:30', new \DateTimeZone('Europe/Paris'))]);

        $this->assertSame('2025-05-04 12:30:00', $dto->dt);
    }

    public function testThrowsIfValueIsNotDateTime(): void
    {
        $dtoClass = new class extends FullDto {
            #[DateTimeString]
            public \DateTimeInterface|string|null $dt = null;
        };

        $this->expectException(\Nandan108\DtoToolkit\Exception\CastingException::class);
        $dtoClass::fromArray(['dt' => 12345]);
    }
}

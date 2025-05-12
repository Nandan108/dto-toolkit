<?php

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Enum\DateTimeFormat;
use Nandan108\DtoToolkit\Exception\CastingException;
use PHPUnit\Framework\TestCase;

final class DateTimeCastTest extends TestCase
{
    public function testStringIsCastToDateTimeImmutableComponents(): void
    {
        $format = 'Y-m-d H:i:s';
        $badDate = 'invalid date';
        $dateTime = date($format);
        $dateTimeObj = \DateTimeImmutable::createFromFormat($format, $dateTime);
        $dt = new CastTo\DateTime($format);

        $dto = new class extends FullDto {
            #[CastTo\DateTime(DateTimeFormat::SQL)]
            public string|\DateTimeImmutable|null $dt = null;
        };
        /** @psalm-suppress UndefinedMagicMethod */
        $dto->fromArray(['dt' => $dateTime]);

        $this->assertEquals($dateTimeObj, $dto->dt);

        // Throws on input $value that's not a date (string 'bad date')
        try {
            $dto = new class extends FullDto {
                #[CastTo\DateTime(DateTimeFormat::SQL)]
                public string|\DateTimeImmutable|null $dt = null;
            };
            /** @psalm-suppress UndefinedMagicMethod */
            $dto->fromArray(['dt' => 'invalid date']);

            $this->fail('DateTime cast should not be able to cast "invalid date" string into a DateTimeImmutable');
        } catch (CastingException $e) {
            $this->assertStringContainsString("Unable to parse date with pattern '$format' from '$badDate'", $e->getMessage());
        }

        // Throws on input value that's not a stringable
        try {
            $dt->cast(new \stdClass(), [$format, null, null]);
            $this->fail('DateTime cast should not be able to cast "invalid date" string into a DateTimeImmutable');
        } catch (CastingException $e) {
            $this->assertStringContainsString('Expected a non-empty date string', $e->getMessage());
        }
    }

    public function testStringIsCastToDateTimeImmutableComponentsWithTimezone(): void
    {
        $format = DateTimeFormat::ISO_8601;
        $dateTimeString = '2025-05-04T12:34:56+02:00';
        $dateTimeObj = \DateTimeImmutable::createFromFormat($format->value, $dateTimeString);

        $dto = new class extends FullDto {
            #[CastTo\DateTime]
            public string|\DateTimeImmutable|null $dt = null;
        };
        /** @psalm-suppress UndefinedMagicMethod */
        $dto->fromArray(['dt' => $dateTimeString]);

        $this->assertEquals($dateTimeObj, $dto->dt);
    }
}

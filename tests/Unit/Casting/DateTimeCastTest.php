<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Core\ProcessingFrame;
use Nandan108\DtoToolkit\Enum\DateTimeFormat;
use Nandan108\DtoToolkit\Exception\Process\TransformException;
use PHPUnit\Framework\TestCase;

final class DateTimeCastTest extends TestCase
{
    public function testStringIsCastToDateTimeImmutableComponents(): void
    {
        $format = 'Y-m-d H:i:s';
        $dateTime = date($format);
        $dateTimeObj = \DateTimeImmutable::createFromFormat($format, $dateTime);
        $dt = new CastTo\DateTime($format);

        $dto = new class extends FullDto {
            #[CastTo\DateTime(DateTimeFormat::SQL)]
            public string | \DateTimeImmutable | null $dt = null;
        };

        $dto->loadArray(['dt' => $dateTime]);

        $this->assertEquals($dateTimeObj, $dto->dt);

        // Throws on input $value that's not a date (string 'bad date')
        try {
            $dto = new class extends FullDto {
                #[CastTo\DateTime(DateTimeFormat::SQL)]
                public string | \DateTimeImmutable | null $dt = null;
            };

            $dto->loadArray(['dt' => 'invalid date']);

            $this->fail('DateTime cast should not be able to cast "invalid date" string into a DateTimeImmutable');
        } catch (TransformException $e) {
            $this->assertSame('processing.transform.date.parsing_failed', $e->getMessageTemplate());
            // $this->assertSame('processing.transform.date.parsing_failed', $e->getMessageParameters()['reason'] ?? null);
        }

        // Throws on input value that's not a stringable
        try {
            $frame = new ProcessingFrame($dto, $dto->getErrorList(), $dto->getErrorMode());
            ProcessingContext::pushFrame($frame);
            try {
                $dt->cast(new \stdClass(), [$format, null, null]);
            } finally {
                ProcessingContext::popFrame();
            }
            $this->fail('DateTime cast should not be able to cast "invalid date" string into a DateTimeImmutable');
        } catch (TransformException $e) {
            $this->assertSame('processing.transform.stringable.non_empty_expected', $e->getMessageTemplate());
        }
    }

    public function testStringIsCastToDateTimeImmutableComponentsWithTimezone(): void
    {
        $format = DateTimeFormat::ISO_8601;
        $dateTimeString = '2025-05-04T12:34:56+02:00';
        $dateTimeObj = \DateTimeImmutable::createFromFormat($format->value, $dateTimeString);

        $dto = new class extends FullDto {
            #[CastTo\DateTime]
            public string | \DateTimeImmutable | null $dt = null;
        };

        $dto->loadArray(['dt' => $dateTimeString]);

        $this->assertEquals($dateTimeObj, $dto->dt);
    }
}

<?php

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\CastTo;
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

        $castDateTime = $dt->cast($dateTime, [$format, null, null]);
        $this->assertEquals($dateTimeObj, $castDateTime);

        // Throws on input $value that's not a date (string 'bad date')
        try {
            $dt->cast($badDate, [$format, null, null]);
            $this->fail('DateTime cast should not be able to cast "invalid date" string into a DateTimeImmutable');
        } catch (CastingException $e) {
            $this->assertStringContainsString("Unable to parse date with pattern '$format' from '$badDate'", $e->getMessage());
        }

        // Throws on input value that's not a stringable
        try {
            $dt->cast(new \stdClass(), [$format, null, null]);
            $this->fail('DateTime cast should not be able to cast "invalid date" string into a DateTimeImmutable');
        } catch (CastingException $e) {
            $this->assertStringContainsString('Expected non-empty date string', $e->getMessage());
        }
    }
}

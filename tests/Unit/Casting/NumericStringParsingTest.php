<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\CastTo\Floating;
use Nandan108\DtoToolkit\Core\FullDto;
use PHPUnit\Framework\TestCase;

final class NumericStringParsingTest extends TestCase
{
    #[\Override]
    public function setUp(): void
    {
        if (!extension_loaded('intl')) {
            $this->markTestSkipped('intl extension not available');
        }
    }

    public function testParsesNumericString(): void
    {
        $dtoClass = new class extends FullDto {
            #[Floating(decimalPoint: ',')]
            public string | float | null $num = null;

            #[Floating(decimalPoint: '.')]
            public float | string | null $amount = null;
        };

        $dto = $dtoClass::fromArray(['num' => "1\u{202F}234,56"]);
        $this->assertSame(1234.56, $dto->num);

        $dto = $dtoClass::fromArray(['num' => "--1\u{202F}2,34,56"]);
        $this->assertSame(-1234.56, $dto->num);

        // thousand separator can be a narrow-non-breaking space
        $dto = $dtoClass::fromArray(['amount' => "1\u{202F}234.56\u{A0}CHF"]);
        $this->assertSame(1234.56, $dto->amount);
        // thousand separator can be a normal space
        $dto = $dtoClass::fromArray(['amount' => "1 234.56\u{A0}CHF"]);
        $this->assertSame(1234.56, $dto->amount);
        // thousand separator can be a normal non-braking space
        $dto = $dtoClass::fromArray(['amount' => "1\u{A0}234.56\u{A0}CHF"]);
        $this->assertSame(1234.56, $dto->amount);
        // thousand separator may be missing
        $dto = $dtoClass::fromArray(['amount' => "1\u{A0}234.56\u{A0}CHF"]);
        $this->assertSame(1234.56, $dto->amount);

        $dto = $dtoClass::fromArray(['amount' => '1\'234.56 CHF']);
        $this->assertSame(1234.56, $dto->amount);
    }

    public function testRejectsEmptyDecimalPoint(): void
    {
        $caster = new Floating(decimalPoint: '');

        $this->expectException(\Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException::class);
        $caster->cast('1.23', ['']);
    }
}

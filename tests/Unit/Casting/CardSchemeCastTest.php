<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\ProcessesInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Process\TransformException;
use Nandan108\DtoToolkit\Traits\ProcessesFromAttributes;
use PHPUnit\Framework\TestCase;

final class CardSchemeCastTest extends TestCase
{
    public function testDetectsScheme(): void
    {
        $dto = new class extends BaseDto implements ProcessesInterface {
            use ProcessesFromAttributes;

            #[CastTo\CardScheme]
            public string $scheme = '';
        };

        $dto->fill(['scheme' => '4111111111111111'])->processInbound();

        $this->assertSame('visa', $dto->scheme);
    }

    public function testRestrictsSchemes(): void
    {
        $dto = new class extends BaseDto implements ProcessesInterface {
            use ProcessesFromAttributes;

            #[CastTo\CardScheme(['mastercard'])]
            public string $scheme = '';
        };

        $this->expectException(TransformException::class);
        $dto->fill(['scheme' => '4111111111111111'])->processInbound();
    }

    public function testConstructorRejectsUnknownScheme(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CastTo\CardScheme(['nope']);
    }

    public function testConstructorRejectsEmptySchemeList(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CastTo\CardScheme([]);
    }

    public function testConstructorRejectsNonStringableScheme(): void
    {
        $this->expectException(InvalidArgumentException::class);
        /** @psalm-suppress InvalidArgument */
        new CastTo\CardScheme([[]]);
    }

    public function testConstructorAcceptsStringableScheme(): void
    {
        $scheme = new class {
            public function __toString(): string
            {
                return 'visa';
            }
        };
        /** @psalm-suppress ImplicitToStringCast */
        $caster = new CastTo\CardScheme([$scheme]);

        $this->assertInstanceOf(CastTo\CardScheme::class, $caster);
    }
}

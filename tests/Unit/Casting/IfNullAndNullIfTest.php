<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\ProcessesInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Traits\ProcessesFromAttributes;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IfNullAndNullIfTest extends TestCase
{
    /** @param truthy-string $prop  */
    // use valueProvider()
    #[DataProvider('valueProvider')]
    public function testAppliesIfnullAndNullifCasters(string $prop, mixed $value, mixed $expected): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto implements ProcessesInterface {
            use ProcessesFromAttributes;
            #[CastTo\IfNull(-1)]
            #[CastTo\Integer]
            public string | int | null $foo = null;

            #[CastTo\NullIf([-1, '', 'null', 'no', 0])]
            #[CastTo\Json]
            public string | int | null $bar = null;

            #[CastTo\ReplaceWhen([1, 2], 'A')]
            #[CastTo\ReplaceWhen(3, 'a')]
            #[CastTo\ReplaceWhen(['a', 'b', [1, 2]], 'c')]
            public mixed $baz = null;
        };

        /** @psalm-suppress UnusedMethodCall */
        $dto->fill([$prop => $value])->processInbound();

        $this->assertSame($expected, $dto->$prop);
    }

    public static function valueProvider(): array
    {
        return [
            ['foo', null, -1],
            ['foo', 4, 4],
            ['bar', -2, '-2'],
            ['bar', -1, 'null'],
            ['bar', 0, 'null'],
            ['bar', 'null', 'null'],
            ['bar', 'no', 'null'],
            ['bar', '0', '"0"'],
            ['bar', 'zero', '"zero"'],
            ['baz', 1, 'A'], // c
            ['baz', 2, 'A'],
            ['baz', 3, 'c'],
            ['baz', 4, 4],
            ['baz', 'a', 'c'],
            ['baz', 'b', 'c'],
            ['baz', 'd', 'd'],
            ['baz', [1], [1]],
            ['baz', [1, 2], 'c'],
        ];
    }
}

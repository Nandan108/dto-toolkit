<?php

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\NormalizesInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Traits\NormalizesFromAttributes;
use PHPUnit\Framework\TestCase;

final class IfNullAndNullIfTest extends TestCase
{
    public function testAppliesIfnullAndNullifCasters(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto implements NormalizesInterface {
            use NormalizesFromAttributes;

            #[CastTo\IfNull(-1)]
            #[CastTo\Integer]
            public string|int|null $foo = null;

            #[CastTo\NullIf([-1, '', 'null', 'no', 0])]
            #[CastTo\Json]
            public string|int|null $bar = null;

            #[CastTo\ReplaceWhen([1, 2], 'A')]
            #[CastTo\ReplaceWhen(3, 'a')]
            #[CastTo\ReplaceWhen(['a', 'b', [1, 2]], 'c')]
            public mixed $baz = null;
        };

        $check = function (string $prop, mixed $value, mixed $expected) use ($dto): void {
            $dto->unfill(['foo', 'bar', 'baz']);
            /** @psalm-suppress UnusedMethodCall */
            $dto->fill([$prop => $value])->normalizeInbound();

            $this->assertSame($expected, $dto->$prop);
        };

        // IfNull test
        $check('foo', null, -1);
        $check('foo', 4, 4);
        // nullIf tests
        $check('bar', -2, '-2');
        $check('bar', -1, 'null');
        $check('bar', 0, 'null');
        $check('bar', 'null', 'null');
        $check('bar', 'no', 'null');
        $check('bar', '0', '"0"');
        $check('bar', 'zero', '"zero"');
        // replaceIf tests
        $check('baz', 1, 'A'); // c
        $check('baz', 2, 'A');
        $check('baz', 3, 'c');
        $check('baz', 4, 4);
        $check('baz', 'a', 'c');
        $check('baz', 'b', 'c');
        $check('baz', 'd', 'd');
        $check('baz', [1], [1]);
        $check('baz', [1, 2], 'c');
    }
}

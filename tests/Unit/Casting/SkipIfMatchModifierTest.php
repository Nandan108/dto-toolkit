<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\Attribute\ChainModifier as Mod;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Core\FullDto;
use PHPUnit\Framework\TestCase;

/** @psalm-suppress DocblockTypeContradiction */
final class SkipIfMatchModifierTest extends TestCase
{
    public function testSkipsAllAndReturnsInputWhenMatched(): void
    {
        $dto = new class extends FullDto {
            #[CastTo\Trimmed]
            #[Mod\SkipIfMatch(['skip'])]
            #[CastTo\Uppercase]
            public mixed $value = null;
        };

        $dto->loadArray(['value' => ' skip ']);
        $this->assertSame('skip', $dto->value);

        $dto->loadArray(['value' => ' go ']);
        $this->assertSame('GO', $dto->value);
    }

    public function testNegateSkipsOnNonMatchAndReturnsCustomValue(): void
    {
        $dto = new class extends FullDto {
            #[Mod\SkipIfMatch(['skip'], return: 'NO', negate: true)]
            #[CastTo\Uppercase]
            public mixed $value = null;
        };

        $dto->loadArray(['value' => 'skip']);
        $this->assertSame('SKIP', $dto->value);

        $dto->loadArray(['value' => 'go']);
        $this->assertSame('NO', $dto->value);
    }

    public function testCountOnlySkipsNextNode(): void
    {
        $dto = new class extends FullDto {
            #[Mod\SkipIfMatch(['skip'], count: 1)]
            #[CastTo\RegexReplace('/^/', 'x')]
            #[CastTo\RegexReplace('/$/', 'y')]
            public mixed $value = null;
        };

        $dto->loadArray(['value' => 'skip']);
        $this->assertSame('skipy', $dto->value);

        $dto->loadArray(['value' => 'go']);
        $this->assertSame('xgoy', $dto->value);
    }

    public function testStrictFalseMatchesLoosely(): void
    {
        $dto = new class extends FullDto {
            #[Mod\SkipIfMatch([1], strict: false)]
            #[CastTo\RegexReplace('/^/', 'x')]
            public mixed $value = null;
        };

        $dto->loadArray(['value' => '1']);
        $this->assertSame('1', $dto->value);
    }

    public function testEmptyMatchValuesNeverMatch(): void
    {
        $dto = new class extends FullDto {
            #[Mod\SkipIfMatch([])]
            #[CastTo\Uppercase]
            public mixed $value = null;
        };

        $dto->loadArray(['value' => 'skip']);
        $this->assertSame('SKIP', $dto->value);
    }
}

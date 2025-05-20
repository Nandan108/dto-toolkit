<?php

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\Attribute\ChainModifier as Mod;
use Nandan108\DtoToolkit\Attribute\ChainModifier\FailIf;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Exception\CastingException;
use Nandan108\DtoToolkit\Tests\Traits\CanTestCasterClassesAndMethods;
use PHPUnit\Framework\TestCase;

final class FirstSuccessTest extends TestCase
{
    use CanTestCasterClassesAndMethods;

    public function testFirstSuccessModifier(): void
    {
        // Test the Collect modifier
        $dto = new class extends FullDto {
            // Using an int argument
            #[CastTo\FromJson]
            #[Mod\FirstSuccess(4)]
            // fails to return array unless context.allowArray is true
            /* - */ #[Mod\Wrap(2), CastTo\Extract('foo.bar'), FailIf('<context:allowArray', negate: true)]
            // fails to cast "not-a-number" to float
            /* - */ #[Mod\Wrap(2), CastTo\Extract('foo.bar.baz'), CastTo\Floating('.')]
            // succeeds to cast "123.4" to float
            /* - */ #[Mod\Wrap(2)]
            /* --- */ #[Mod\Groups('qux', 2), CastTo\Extract('foo.bar.qux'), CastTo\Floating('.')]
            /* --- */ #[CastTo\Str]
            // skipped because of the previous success
            /* - */ #[Mod\Wrap(2), CastTo\Extract('foo.bar.fux'), CastTo\Floating('.')]
            public mixed $value = null;
        };

        $fooBarInput = '{"baz": "not-a-number", "qux": "123.4", "fux": [123]}';
        $input = "{\"foo\": {\"bar\": $fooBarInput}}";

        // Test that FailIf modifier doesn't fail fails when when condition is true
        /** @psalm-suppress UndefinedMagicMethod */
        $dto->withContext(['allowArray' => true])->fromArray(['value' => $input]);
        $this->assertSame(json_decode($fooBarInput, true), $dto->value);
        // Test that FailIf modifier fails when condition is false, Floating fails to cast "not-a-number" to float
        /** @psalm-suppress UndefinedMagicMethod */
        $dto->withContext(['allowArray' => false])->withGroups('qux')->fromArray(['value' => $input]);
        $this->assertSame('123.4', $dto->value);

        $this->expectException(CastingException::class);
        $this->expectExceptionMessage('All 4 nodes wrapped by FirstSuccess have failed');
        /** @psalm-suppress UndefinedMagicMethod */
        $dto->withGroups('fux')->fromArray(['value' => $input]);
    }
}

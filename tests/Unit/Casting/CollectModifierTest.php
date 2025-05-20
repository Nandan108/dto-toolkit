<?php

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\Attribute\ChainModifier as Mod;
use Nandan108\DtoToolkit\Attribute\ChainModifier\PerItem;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Tests\Traits\CanTestCasterClassesAndMethods;
use PHPUnit\Framework\TestCase;

final class CollectModifierTest extends TestCase
{
    use CanTestCasterClassesAndMethods;

    public function testCollectModifier(): void
    {
        // Test the Collect modifier
        $dto = new class extends FullDto {
            // Using an int argument
            #[Mod\Collect(3)]
            #[CastTo\Floating('.')]
            #[Mod\Wrap(2), CastTo\Floating(','), CastTo\NumericString(2, '.')]
            #[Mod\Wrap(2), CastTo\Split(','), PerItem, CastTo\NumericString(2, ',')]
            public mixed $value = null;

            // Using an array argument
            #[Mod\Collect(['original', 'pascal', 'camel', 'snake', 'kebab'])]
            #[Mod\NoOp]
            #[CastTo\PascalCase]
            #[CastTo\CamelCase]
            #[CastTo\SnakeCase]
            #[CastTo\KebabCase]
            public string|array|null $identifier = null;
        };

        $dto->fill(['value' => '1234,5678', 'identifier' => 'foo bar baz']);
        $dto->normalizeInbound();
        $this->assertSame([12345678.0, '1234.57', ['1234,00', '5678,00']], $dto->value);
        $this->assertSame([
            'original' => 'foo bar baz',
            'pascal'   => 'FooBarBaz',
            'camel'    => 'fooBarBaz',
            'snake'    => 'foo_bar_baz',
            'kebab'    => 'foo-bar-baz',
        ], $dto->identifier);
    }
}

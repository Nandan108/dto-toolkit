<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\Attribute\ChainModifier as Mod;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Tests\Traits\CanTestCasterClassesAndMethods;
use PHPUnit\Framework\TestCase;

final class ConditionalModifiersTest extends TestCase
{
    use CanTestCasterClassesAndMethods;

    public function testFirstSuccessModifier(): void
    {
        // Test the Collect modifier
        $dto = new class extends FullDto {
            // Apply Case caster depending on desiredCase
            #[Mod\ApplyNextIf('<dto:desiredCaseIs:pascal'), CastTo\PascalCase]
            #[Mod\ApplyNextIf('<dto:desiredCaseIs:camel'), CastTo\CamelCase]
            #[Mod\ApplyNextIf('<dto:desiredCaseIs:snake'), Mod\Wrap(1), CastTo\SnakeCase]
            #[Mod\ApplyNextIf('<dto:desiredCaseIs:kebab'), CastTo\KebabCase]
            // prefix the value with "separated:" unless the desired case is pascal or camel
            // This also tests passing a json value to the condition method
            #[Mod\SkipNextIf('<dto:desiredCaseIs:["pascal","camel"]'), CastTo\RegexReplace('/^/', 'separated:')]
            public mixed $value = null;

            public function desiredCaseIs(mixed $value, string $prop, string | array $case): bool
            {
                return in_array($this->contextGet('desiredCase'), (array) $case);
            }
        };

        $tests = [
            'pascal' => 'PascalCase',
            'camel'  => 'camelCase',
            'snake'  => 'separated:snake_case',
            'kebab'  => 'separated:kebab-case',
        ];

        foreach ($tests as $case => $expected) {
            $dto->contextSet('desiredCase', $case);

            $dto->loadArray(['value' => "$case case"]);
            $this->assertSame($expected, $dto->value);
        }
    }
}

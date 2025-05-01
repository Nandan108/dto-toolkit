<?php

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Core\FullDto;
use PHPUnit\Framework\TestCase;

final class CaseConvertTest extends TestCase
{
    public function testCaseIsCorrectlyConvertedComponents(): void
    {
        $dto = new FullDto();

        $inputs = ['postal_code', 'PostalCode', 'postalCode', 'postal-code', 'postal code', 'postal+code'];

        $outputs = [
            CastTo\PascalCase::class     => 'PostalCode',
            CastTo\SnakeCase::class      => 'postal_code',
            CastTo\KebabCase::class      => 'postal-code',
            CastTo\CamelCase::class      => 'postalCode',
            CastTo\UpperSnakeCase::class => 'POSTAL_CODE',
        ];

        foreach ($outputs as $casterClass => $expected) {
            $caster = new $casterClass();
            foreach ($inputs as $input) {
                $this->assertSame(
                    $expected,
                    $caster->cast($input, [], $dto),
                );
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Exception\Process\TransformException;
use Nandan108\DtoToolkit\Tests\Traits\CanTestCasterClassesAndMethods;
use Nandan108\PropAccess\PropAccess;
use PHPUnit\Framework\TestCase;

final class JsonCastTest extends TestCase
{
    use CanTestCasterClassesAndMethods;

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();
        // Ensure that the test environment is clean
        PropAccess::bootDefaultResolvers();
    }

    public function testFromJson(): void
    {
        // FromJson with valid input
        $this->casterTest(new CastTo\FromJson(), '{"0":"baz","foo": {"bar": ["a","b","c"]}}', ['baz', 'foo' => ['bar' => ['a', 'b', 'c']]]);

        // FromJson with invalid input
        $this->casterTest(new CastTo\FromJson(), '{"0":"baz","foo": {"bar": "a","b","c"]}}', TransformException::class);
    }
}

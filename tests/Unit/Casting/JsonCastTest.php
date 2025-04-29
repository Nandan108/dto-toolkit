<?php

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Exception\CastingException;
use Nandan108\DtoToolkit\Tests\Traits\CanTestCasterClassesAndMethods;
use PHPUnit\Framework\TestCase;

final class JsonCastTest extends TestCase
{
    use CanTestCasterClassesAndMethods;

    public function testRegexReplace(): void
    {
        // FromJson with valid input
        $this->casterTest(new CastTo\FromJson(), '{"0":"baz","foo": {"bar": ["a","b","c"]}}', ['baz', 'foo' => ['bar' => ['a', 'b', 'c']]]);

        // FromJson with invalid input
        $this->casterTest(new CastTo\FromJson(), '{"0":"baz","foo": {"bar": "a","b","c"]}}', CastingException::class);

        // JsonExtract with valid array input
        $this->casterTest(new CastTo\JsonExtract('foo.bar.1'), ['baz', 'foo' => ['bar' => ['a', 'b', 'c']]], 'b');

        // JsonExtract with invalid string input
        $this->casterTest(new CastTo\JsonExtract('foo.bar.1'), '{"0":"baz","foo": {"bar": "a","b","c"]}}', CastingException::class);

        // JsonExtract with invalid non-array input
        $this->casterTest(new CastTo\JsonExtract('foo.bar.1'), (object) [], CastingException::class);

        // JsonExtract with invalid path input
        $this->casterTest(new CastTo\JsonExtract('foo.bar.3'), ['baz', 'foo' => ['bar' => ['a', 'b', 'c']]], CastingException::class);
    }
}

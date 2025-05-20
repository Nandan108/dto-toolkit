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

        $arrayAccess = new \ArrayIterator(['A', 'B', 'C']);
        $obj = new JsonCastTestObjectWithGetter(propVal: ['A', 'B', $arrayAccess], getterVal: ['D', 'E', 'F']);

        // Extract through objects, via props or getters
        $this->casterTest(new CastTo\Extract('foo.propVal.1'), ['baz', 'foo' => $obj], 'B');
        $this->casterTest(new CastTo\Extract('foo.getterVal.1'), ['baz', 'foo' => $obj], 'E');
        $this->casterTest(new CastTo\Extract('propVal.2.2'), $obj, 'C');
        $this->casterTest(new CastTo\Extract('propVal.2.2'), $obj, 'C');

        // Extract from ArrayAccess with invalid path
        $this->casterTest(new CastTo\Extract('propVal.2.3'), $obj, CastingException::class);

        // Extract with invalid path input
        $this->casterTest(new CastTo\Extract('foo.bar.3'), ['baz', 'foo' => ['bar' => ['a', 'b', 'c']]], CastingException::class);

        // Extract with invalid string input
        $this->casterTest(new CastTo\Extract('foo.bar.1'), '{"0":"baz","foo": {"bar": "a","b","c"]}}', CastingException::class);

        // Extract with invalid non-array input
        $this->casterTest(new CastTo\Extract('foo.bar.1'), (object) [], CastingException::class);

        // Extract with invalid path input
        $this->casterTest(new CastTo\Extract('foo.bar.baz'), ['foo' => ['bar' => 'not-an-array']], CastingException::class, [], 'Unexpected type `string` at foo.bar.`baz`');
    }
}

/**
 * @psalm-suppress PossiblyUnusedMethod, PossiblyUnusedProperty
 */
final class JsonCastTestObjectWithGetter
{
    /** @psalm-suppress PossiblyUnusedProperty */
    public function __construct(public mixed $propVal, private mixed $getterVal)
    {
        [$propVal, $getterVal];
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function getGetterVal(): mixed
    {
        return $this->getterVal;
    }
}

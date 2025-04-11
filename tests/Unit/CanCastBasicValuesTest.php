<?php

namespace Nandan108\DtoToolkit\Tests\Unit;

use DateTimeImmutable;
use Mockery;
use Nandan108\DtoToolkit\Core\CastTo;
use Nandan108\DtoToolkit\Contracts\NormalizesOutboundInterface;
use Nandan108\DtoToolkit\Traits\CanCastBasicValues;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Nandan108\DtoToolkit\BaseDto;
use Nandan108\DtoToolkit\Exception\CastingException;
use Nandan108\DtoToolkit\Traits\NormalizesFromAttributes;


final class CanCastBasicValuesTest extends TestCase
{


    #[DataProvider('builtinCastProvider')]
    public function test_builtin_cast_methods(string $method, mixed $input, mixed $expected, ?array $args = null): void
    {
        // /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto {
            use NormalizesFromAttributes;
        };

        // create caster Attribute using static helper method, and from it get the caster Closure
        /** @var \Closure $caster */
        $caster = CastTo::$method(...($args ?? []))->getCaster($dto);
        // Call the caster closure with the input value and get the result
        $result = $caster($input);

        if (is_object($expected)) {
            $this->assertInstanceOf(get_class($expected), $result);
            $this->assertEquals($expected, $result); // compares datetime value
        } else {
            $this->assertSame($expected, $result);
        }
    }



    public static function builtinCastProvider(): array
    {
        $someArray            = ['key' => 'value'];
        $objWithToArrayMethod = new class ($someArray) {
            public function __construct(private array $data = []) {}
            public function toArray(): array
            {
                return $this->data;
            }
        };

        $dateTime    = date('Y-m-d H:i:s');
        $dateTimeObj = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateTime);

        // Make a iterator on numbers 1 to 5
        $iterator_OneToFive = new \ArrayIterator([1, 2, 3, 4, 5]);

        return [
            /* 0*/ ['intOrNull', '42', 42],
            /* 1*/ ['floatOrNull', '3.14', 3.14],
            /* 2*/ ['boolOrNull', false, false],
            /* 3*/ ['boolOrNull', '1', true],
            /* 4*/ ['boolOrNull', 'yes', true],
            /* 5*/ ['boolOrNull', 'yessss', null],
            /* 6*/ ['boolOrNull', [], null],
            /* 7*/ ['stringOrNull', 42, '42'],
            /* 8*/ ['trimmedString', '  hello ', 'hello'],
            /* 9*/ ['arrayFromCSV', 'a,b,c', ['a', 'b', 'c']],
            /*10*/ ['arrayFromCSV', 'a-b-c', ['a', 'b', 'c'], ['-']],
            /*11*/ ['arrayFromCSV', '', ['']],
            /*12*/ ['arrayOrEmpty', null, []],
            /*13*/ ['arrayOrEmpty', $iterator_OneToFive, [1, 2, 3, 4, 5]],
            /*14*/ ['arrayOrEmpty', $objWithToArrayMethod, $someArray],
            /*15*/ ['arrayOrEmpty', (object)[1, 2, 3], []],
            /*16*/ ['arrayOrEmpty', ['abc', 'x,y,z'], ['abc', 'x,y,z']],
            /*17*/ ['dateTimeOrNull', $dateTime, $dateTimeObj],
            /*18*/ ['dateTimeOrNull', null, null],
            /*29*/ ['dateTimeOrNull', 'invalid date', null],
            /*20*/ ['dateTimeOrNull', $dateTimeObj, $dateTimeObj],
            /*21*/ ['dateTimeOrNull', new \DateTime(), null],
            /*22*/ ['dateTimeOrNull', new \stdClass(), null],
            /*23*/ ['rounded', 0.991, 0.99, [2]],
            /*24*/ ['rounded', 0.991, 1.0, [1]],
            /*24*/ ['rounded', 0.6, 1.0],
            /*25*/ ['rounded', 'yes', null],
        ];
    }
}
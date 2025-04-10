<?php

namespace Tests\Unit;

use DateTimeImmutable;
use Mockery;
use Nandan108\DtoToolkit\Attribute\CastTo;
use Nandan108\DtoToolkit\Contracts\NormalizesOutboundInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Nandan108\DtoToolkit\BaseDto;
use Nandan108\DtoToolkit\Traits\NormalizesFromAttributes;


class NormalizesFromAttributesTest extends TestCase
{
    public function test_returns_normalized_properties(): void
    {
        $dto = new class extends BaseDto
            // implements NormalizesInbound
        {
            use NormalizesFromAttributes;

            #[CastTo('intOrNull')]
            public string|int|null $age;
        };

        // Case 1: Assert that properties that are not "filled" are not normalized
        $dto->age = "30";
        $dto->normalizeInbound();
        $this->assertSame("30", $dto->age);

        // Case 2: Null input
        $dto->fill(['age' => null])->normalizeInbound();
        $this->assertNull($dto->age);

        // Case 3: Assert that properties that are "filled" are normalized
        $dto->fill(['age' => "30"])->normalizeInbound();
        $this->assertSame(30, $dto->age);

        // Case 4: Assert that invalid values are set to null
        $dto->fill(['age' => "not-a-number"])->normalizeInbound();
        $this->assertNull($dto->age);
    }

    #[DataProvider('builtinCastProvider')]
    public function test_builtin_cast_methods(string $method, array $input, mixed $expected): void
    {
        $dto = new class extends BaseDto {
            use NormalizesFromAttributes;

            // expose the protected methods for test
            public function call(string $method, mixed ...$args): mixed
            {
                return $this->$method(...$args);
            }
        };

        $result = $dto->call($method, ...$input);

        if (is_object($expected)) {
            $this->assertInstanceOf(get_class($expected), $result);
            $this->assertEquals($expected, $result); // compares datetime value
        } else {
            $this->assertSame($expected, $result);
        }
    }


    public function test_normalize_outbound_applies_casts_to_tagged_properties(): void
    {
        $dto = new class extends BaseDto implements NormalizesOutboundInterface {
            use NormalizesFromAttributes;

            #[CastTo('trimmedString', outbound: true)]
            public string $title;

            #[CastTo('stringOrNull', outbound: true)]
            public int|string|null $categoryId;

            #[CastTo('stringOrNull', outbound: true)]
            private int|string|null $privatePropWithSetter;
            public function setPrivatePropWithSetter($value): void {
                $this->privatePropWithSetter = $value;
            }

            public string $untouched;
        };

        $normalized = $dto->normalizeOutbound([
            'title' => '  Hello  ',
            'categoryId' => 42,
            'untouched' => 'value',
            'privatePropWithSetter' => 'val',
        ]);

        $this->assertSame('Hello', $normalized['title']);
        $this->assertSame('42', $normalized['categoryId']);
        $this->assertSame('value', $normalized['untouched']); // unchanged
        $this->assertSame('val', $normalized['privatePropWithSetter']);
    }

    public function test_get_caster_throws_when_method_missing(): void
    {
        $dto = new class extends BaseDto {
            // Note: no castToSomething method defined
        };

        $cast = new CastTo('something');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("Missing method 'castToSomething' for #[CastTo('something')]");

        $cast->getCaster($dto);
    }

    public static function builtinCastProvider(): array
    {
        $someArray            = ['key' => 'value'];
        $objWithToArrayMethod = new class($someArray) {
            public function __construct(private array $data = []) {}
            public function toArray(): array
            {
                return $this->data;
            }
        };

        $dateTime = date('Y-m-d H:i:s');
        $dateTimeObj = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateTime);

        // Make a iterator on numbers 1 to 5
        $iterator_OneToFive = new \ArrayIterator([1, 2, 3, 4, 5]);

        return [
            /* 0*/['castToIntOrNull', ['42'], 42],
            /* 1*/['castToFloatOrNull', ['3.14'], 3.14],
            /* 2*/['castToBoolOrNull', [false], false],
            /* 3*/['castToBoolOrNull', ['1'], true],
            /* 4*/['castToBoolOrNull', ['yes'], true],
            /* 5*/['castToBoolOrNull', ['yessss'], null],
            /* 6*/['castToBoolOrNull', [[]], null],
            /* 7*/['castToStringOrNull', [42], '42'],
            /* 8*/['castToTrimmedString', ['  hello '], 'hello'],
            /* 9*/['castToArrayFromCSV', ['a,b,c'], ['a', 'b', 'c']],
            /*10*/['castToArrayFromCSV', ['a-b-c', '-'], ['a', 'b', 'c']],
            /*11*/['castToArrayFromCSV', [''], ['']],
            /*12*/['castToArrayOrEmpty', [null], []],
            /*13*/['castToArrayOrEmpty', [$iterator_OneToFive], [1,2,3,4,5]],
            /*14*/['castToArrayOrEmpty', [$objWithToArrayMethod], $someArray],
            /*15*/['castToArrayOrEmpty', [(object)[1,2,3]], []],
            /*16*/['castToArrayOrEmpty', [['abc','x,y,z']], ['abc','x,y,z']],
            /*17*/['castToDateTimeOrNull', [$dateTime], $dateTimeObj],
            /*18*/['castToDateTimeOrNull', [null], null],
            /*29*/['castToDateTimeOrNull', ['invalid date'], null],
            /*20*/['castToDateTimeOrNull', [$dateTimeObj], $dateTimeObj],
            /*21*/['castToDateTimeOrNull', [new \DateTime()], null],
            /*22*/['castToDateTimeOrNull', [new \stdClass()], null],
        ];
    }
}

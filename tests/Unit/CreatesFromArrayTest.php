<?php

namespace Tests\Unit;

use Nandan108\DtoToolkit\Attribute\CastTo;
use PHPUnit\Framework\TestCase;
use Nandan108\DtoToolkit\BaseDto;
use Nandan108\DtoToolkit\Contracts\NormalizesInboundInterface;
use Nandan108\DtoToolkit\Contracts\ValidatesInputInterface;
use Nandan108\DtoToolkit\Traits\CreatesFromArray;
use Nandan108\DtoToolkit\Traits\NormalizesFromAttributes;

/** @psalm-suppress UnusedClass */
class CreatesFromArrayTest extends TestCase
{
    public function test_instantiates_dto_from_array(): void
    {
        $dtoClass = new class extends BaseDto {
            use CreatesFromArray;
            use NormalizesFromAttributes;

            public string|int $item_id;
            public string $email;
            public string|int|null $age;
        };

        /** @psalm-suppress NoValue, UnusedVariable */
        $dto = $dtoClass::fromArray([ // GET
            'item_id' => $rawItemId = '5',
            'age'     => $rawAge = '30',
            'email'   => $rawEmail = 'john@example.com',
        ]);

        // still raw
        $this->assertSame($rawEmail, $dto->email);
        // still raw, taken from GET
        $this->assertSame($rawAge, $dto->age);
        $this->assertSame($rawItemId, $dto->item_id);

        // filled
        $this->assertArrayHasKey('email', $dto->_filled);
        $this->assertArrayHasKey('age', $dto->_filled);
        $this->assertArrayHasKey('item_id', $dto->_filled);
    }

    // Test the the DTO is validateda after being filled if it implements ValidatesInputInterface
    public function test_dto_is_validated_in_fromArray_if_it_implements_ValidatesInputInterface(): void
    {
        $dtoClass = new class extends BaseDto implements ValidatesInputInterface {
            use CreatesFromArray;
            #[\Override]
            public function validate(array $args = []): static
            {
                // Simulate validation logic
                if ($this->email === 'invalid-email') {
                    throw new \Exception('Validation failed');
                }
            }

            public string|int $item_id;
            public string $email;
            public string|int|null $age;
        };

        try {
            $dtoClass::fromArray([
                'item_id' => '5',
                'age'     => '30',
                'email'   => 'invalid-email',
            ]);
            $this->fail('Expected exception not thrown');
        } catch (\Exception $e) {
            // Assert that the exception message is as expected
            $this->assertSame('Validation failed', $e->getMessage());
        }
    }

    // Test the the DTO is validateda after being filled if it implements ValidatesInputInterface
    public function test_validation_fails_if_args_are_given_but_dto_doesnt_implement_ValidatesInputInterface(): void
    {
        $dtoClass = new class extends BaseDto {
            use CreatesFromArray;
            public string $email;
        };

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('To support $args, the DTO must implement ValidatesInput.');

        $dtoClass::fromArray(['email' => 'invalid-email'], ['some-args-for-validation']);
    }

    // Test that the fromArray method throws an exception if unknown properties are passed
    // and ignoreUnknownProperties is false
    public function test_throw_exception_for_unknown_properties_when_ignoreUnknownProperties_is_false(): void
    {
        $dtoClass = new class extends BaseDto {
            use CreatesFromArray;
            public string $email;
        };

        /** @psalm-suppress NoValue, UnusedVariable */
        $dto = $dtoClass::fromArray(
            // ignoreUnknownProperties: true, // default
            input: [ // GET
                'anUnknownProp' => 'foo',
                'andAnother'    => 'bar',
                'email'         => $rawEmail = 'john@example.com',
            ],
        );

        $this->assertSame($rawEmail, $dto->email);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unknown properties: anUnknownProp, andAnother');

        /** @psalm-suppress NoValue, UnusedVariable */
        $dtoClass::fromArray(
            ignoreUnknownProperties: false,
            input: [ // GET
                'anUnknownProp' => 'foo',
                'andAnother'    => 'bar',
                'email'         => $rawEmail = 'john@example.com',
            ],
        );
    }

    // that that a dto must extend BaseDto to use CreatesFromArray
    public function test_throws_exception_if_dto_class_does_not_extend_BaseDto(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('must extend BaseDto to use CreatesFromArray');

        $dtoClass = new class {
            use CreatesFromArray;
        };

        $dtoClass::fromArray([]);
    }

    // test that if dto is an instance of NormalizesInboundInterface, normalizeInbound() is called
    public function test_FromArray_calls_normalizeInbound_on_DTOs_implementing_NormalizesInboundInterface()
    {
        $dtoClass = new class extends BaseDto implements NormalizesInboundInterface
            // implements NormalizesInbound
        {
            use CreatesFromArray;
            use NormalizesFromAttributes;

            #[CastTo('intOrNull')]
            public string|int|null $age;

            #[CastTo('trimmedString')]
            public string $name;
        };

        /** @psalm-suppress NoValue, UnusedVariable */
        $dto = $dtoClass->fromArray([
            'age'  => "30",
            'name' => "  sam   ",
        ]);

        // Assert that the age property is normalized to an integer
        $this->assertSame(30, $dto->age);
        // Assert that the name property is normalized to a trimmed string
        $this->assertSame('sam', $dto->name);

    }
}

<?php

namespace Nandan108\DtoToolkit\Tests\Unit;

use Nandan108\DtoToolkit\CastTo;
use PHPUnit\Framework\TestCase;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Contracts\NormalizesInboundInterface;
use Nandan108\DtoToolkit\Contracts\ValidatesInputInterface;
use Nandan108\DtoToolkit\Traits\CreatesFromArray;
use Nandan108\DtoToolkit\Traits\NormalizesFromAttributes;

/** @psalm-suppress UnusedClass */
final class CreatesFromArrayTest extends TestCase
{
    public function test_instantiates_dto_from_array(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dtoClass = new class extends BaseDto {
            use CreatesFromArray;
            use NormalizesFromAttributes;

            public null|string|int $item_id = null;
            public null|string $email = null;
            public null|string|int $age = null;
        };

        /** @psalm-suppress NoValue, UnusedVariable */
        $dto = $dtoClass::fromArray([ // GET
            'item_id' => $rawItemId = '5',
            'age'     => $rawAge = '30',
            'email'   => $rawEmail = 'john@example.com',
        ]);

        // still raw
        /** @psalm-suppress UndefinedPropertyFetch */
        $this->assertSame($rawEmail, $dto->email);
        // still raw, taken from GET
        /** @psalm-suppress UndefinedPropertyFetch */
        $this->assertSame($rawAge, $dto->age);
        /** @psalm-suppress UndefinedPropertyFetch */
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
                return $this;
            }

            public null|string|int $item_id = null;
            public null|string $email = null;
            public null|string|int $age = null;
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
            public ?string $email = null;
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
            public ?string $email = null;
        };

        /** @psalm-suppress NoValue, UnusedVariable */
        $dto = $dtoClass::fromArrayLoose(
            input: [ // GET
                'anUnknownProp' => 'foo',
                'andAnother'    => 'bar',
                'email'         => $rawEmail = 'john@example.com',
            ],
        );
        /** @psalm-suppress UndefinedPropertyFetch */
        $this->assertSame($rawEmail, $dto->email);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unknown properties: anUnknownProp, andAnother');

        /** @psalm-suppress NoValue, UnusedVariable */
        $dtoClass::fromArray(
            ignoreUnknownProps: false,
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

        /** @psalm-suppress ExtensionRequirementViolation */
        $dtoClass = new class {
            use CreatesFromArray;
        };

        $dtoClass::fromArray([]);
    }

    // test that if dto is an instance of NormalizesInboundInterface, normalizeInbound() is called
    public function test_FromArray_calls_normalizeInbound_on_DTOs_implementing_NormalizesInboundInterface(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dtoClass = new class extends BaseDto implements NormalizesInboundInterface
            // implements NormalizesInbound
        {
            use CreatesFromArray;
            use NormalizesFromAttributes;

            #[CastTo\Integer]
            public string|int|null $age = null;

            #[CastTo\Trimmed]
            public ?string $name = null;
        };

        /** @psalm-suppress NoValue, UnusedVariable */
        $dto = $dtoClass->fromArray([
            'age'  => "30",
            'name' => "  sam   ",
        ]);

        // Assert that the age property is normalized to an integer
        /** @psalm-suppress UndefinedPropertyFetch */
        $this->assertSame(30, $dto->age);
        // Assert that the name property is normalized to a trimmed string
        /** @psalm-suppress UndefinedPropertyFetch */
        $this->assertSame('sam', $dto->name);

    }
}

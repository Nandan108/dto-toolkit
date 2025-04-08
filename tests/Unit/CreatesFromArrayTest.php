<?php

namespace Tests\Unit;

use Nandan108\SymfonyDtoToolkit\Attribute\CastTo;
use Nandan108\SymfonyDtoToolkit\Exception\ValidationException;
use PHPUnit\Framework\TestCase;
use Nandan108\SymfonyDtoToolkit\BaseDto;
use Nandan108\SymfonyDtoToolkit\Contracts\NormalizesInboundInterface;
use Nandan108\SymfonyDtoToolkit\Contracts\ValidatesInputInterface;
use Nandan108\SymfonyDtoToolkit\Traits\CreatesFromArray;
use Nandan108\SymfonyDtoToolkit\Traits\CreatesFromRequest;
use Nandan108\SymfonyDtoToolkit\Traits\NormalizesFromAttributes;
use Nandan108\SymfonyDtoToolkit\Traits\ValidatesInput;
use Symfony\Component\Validator\Constraints as Assert;

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
            use ValidatesInput;

            public string|int $item_id;
            public string $email;
            #[Assert\Email]
            public string|int|null $age;
        };

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Validation failed');

        $dtoClass::fromArray([
            'item_id' => '5',
            'age'     => '30',
            'email'   => 'invalid-email',
        ]);
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

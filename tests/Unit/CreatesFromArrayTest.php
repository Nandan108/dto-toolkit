<?php

namespace Nandan108\DtoToolkit\Tests\Unit;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\NormalizesInterface;
use Nandan108\DtoToolkit\Contracts\ValidatesInputInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Traits\CreatesFromArrayOrEntity;
use Nandan108\DtoToolkit\Traits\NormalizesFromAttributes;
use Nandan108\PropAccess\Exception\AccessorException;
use Nandan108\PropAccess\PropAccess;
use PHPUnit\Framework\TestCase;

/** @psalm-suppress UnusedClass */
final class CreatesFromArrayTest extends TestCase
{
    #[\Override]
    public function setUp(): void
    {
        parent::setUp();
        // Ensure that the test environment is clean
        PropAccess::bootDefaultResolvers();
    }

    public function testInstantiatesDtoFromArray(): void
    {
        /** @psalm-suppress NoValue, UnusedVariable, UndefinedMagicMethod */
        $dto = FromArrayTestDto::fromArray([ // GET
            'itemId'   => $rawItemId = '5',
            'name'     => $name = 'John',
            'email'    => $rawEmail = 'john@example.com',
        ]);

        // still raw
        /** @psalm-suppress UndefinedPropertyFetch */
        $this->assertSame($rawEmail, $dto->email);
        // still raw, taken from GET
        /** @psalm-suppress UndefinedPropertyFetch */
        $this->assertSame(strtoupper($name), $dto->name);
        /** @psalm-suppress UndefinedPropertyFetch */
        $this->assertSame((int) $rawItemId, $dto->itemId);

        // filled
        $this->assertArrayHasKey('email', $dto->_filled);
        $this->assertArrayHasKey('name', $dto->_filled);
        $this->assertArrayHasKey('itemId', $dto->_filled);
    }

    // Test the the DTO is validateda after being filled if it implements ValidatesInputInterface
    public function testDtoIsValidatedInFromArrayIfItImplementsValidatesInputInterface(): void
    {
        $dtoClass = new class extends BaseDto implements ValidatesInputInterface {
            use CreatesFromArrayOrEntity;

            #[\Override]
            public function validate(array $args = []): static
            {
                // Simulate validation logic
                if ('invalid-email' === $this->email) {
                    throw new \Exception('Validation failed');
                }

                return $this;
            }

            public string|int|null $item_id = null;
            public ?string $email = null;
            public string|int|null $age = null;
        };

        try {
            /** @psalm-suppress UndefinedMagicMethod */
            (new $dtoClass())->fromArray([
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

    // Test that the fromArray method throws an exception if unknown properties are passed
    // and ignoreUnknownProperties is false
    public function testThrowExceptionForUnknownPropertiesWhenIgnoreUnknownPropertiesIsFalse(): void
    {
        $dtoClass = new class extends BaseDto {
            use CreatesFromArrayOrEntity;
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
            input: [ // GET
                'anUnknownProp' => 'foo',
                'andAnother'    => 'bar',
                'email'         => $rawEmail = 'john@example.com',
            ],
            ignoreUnknownProps: false,
        );
    }

    // test that if dto is an instance of NormalizesInboundInterface, normalizeInbound() is called
    public function testFromArrayCallsNormalizeInboundOnDtosImplementingNormalizesInboundInterface(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dtoClass = new class extends BaseDto implements NormalizesInterface {
            // implements NormalizesInbound
            use CreatesFromArrayOrEntity;
            use NormalizesFromAttributes;

            #[CastTo\Integer]
            public string|int|null $age = null;

            #[CastTo\Trimmed]
            public ?string $name = null;
        };

        /** @psalm-suppress NoValue, UnusedVariable, UndefinedMagicMethod */
        $dto = $dtoClass->fromArray([
            'age'  => '30',
            'name' => '  sam   ',
        ]);

        // Assert that the age property is normalized to an integer
        /** @psalm-suppress UndefinedPropertyFetch */
        $this->assertSame(30, $dto->age);
        // Assert that the name property is normalized to a trimmed string
        /** @psalm-suppress UndefinedPropertyFetch */
        $this->assertSame('sam', $dto->name);
    }

    public function testNormalizesFromEntity(): void
    {
        $dtoClass = new class extends BaseDto implements NormalizesInterface {
            use CreatesFromArrayOrEntity;
            use NormalizesFromAttributes;

            #[CastTo\Integer]
            public string|int|null $itemId = null;
            public ?string $email = null;
            #[CastTo\Uppercase]
            public string|int|null $name = null;
        };

        $entity = new class {
            public string|int|null $itemId = '5';

            public ?string $email = 'name@domain.test';

            private string|int|null $name = 'sam';

            public function getName(): string|int|null
            {
                return $this->name;
            }

            public function setName(string $name): void
            {
                $this->name = $name;
            }

            public string|int|null $ignoredPropNotOnDTO = 'value';
        };

        /** @psalm-suppress UndefinedMagicMethod */
        $dto = $dtoClass->fromEntity($entity);
        $this->assertSame('SAM', $dto->name);
        $this->assertSame(5, $dto->itemId);
        $this->assertSame('name@domain.test', $dto->email);

        // Again, this time for coverage of cache-hit path on property getter
        $entity->setName('joe');
        /** @psalm-suppress UndefinedMagicMethod */
        $anotherDto = $dtoClass->fromEntity($entity);
        $this->assertSame('JOE', $anotherDto->name);
    }

    public function testFailsIfPropertyDoesntExistOnEntity(): void
    {
        $entity = new class {
            public string|int|null $itemNumber = '5';
            public ?string $email = 'name@domain.test';
            // name is private, not accessible
            private string|int|null $name = 'sam';
        };

        $this->expectException(AccessorException::class);
        $this->expectExceptionMessage('No public getter or property found for: itemId, name in '.get_class($entity));

        // attempts to create a new FromArrayTestDto from data (itemId, name, email) taken from the entity
        // but itemId and name do not exist on the entity, so it should throw an exception
        FromArrayTestDto::fromEntity($entity, false);
    }
}

final class FromArrayTestDto extends BaseDto implements NormalizesInterface
{
    use CreatesFromArrayOrEntity;
    use NormalizesFromAttributes;

    #[CastTo\Integer]
    public string|int|null $itemId = null;
    public ?string $email = null;
    #[CastTo\Uppercase]
    public string|int|null $name = null;
}

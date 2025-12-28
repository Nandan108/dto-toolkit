<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit;

use Nandan108\DtoToolkit\Attribute\MapFrom;
use Nandan108\DtoToolkit\Attribute\Presence;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\ProcessesInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Enum\PresencePolicy;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Traits\CreatesFromArrayOrEntity;
use Nandan108\DtoToolkit\Traits\ProcessesFromAttributes;
use Nandan108\PropAccess\Exception\AccessorException;
use Nandan108\PropAccess\PropAccess;
use Nandan108\PropPath\Support\ThrowMode;
use PHPUnit\Framework\Attributes\DataProvider;
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

        $this->expectException(InvalidConfigException::class);
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
        $dtoClass = new class extends BaseDto implements ProcessesInterface {
            // implements NormalizesInbound
            use CreatesFromArrayOrEntity;
            use ProcessesFromAttributes;

            #[CastTo\Integer]
            public string | int | null $age = null;

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

    public function testProcessesFromEntity(): void
    {
        $dtoClass = new class extends BaseDto implements ProcessesInterface {
            use CreatesFromArrayOrEntity;
            use ProcessesFromAttributes;

            #[CastTo\Integer]
            public string | int | null $itemId = null;
            public ?string $email = null;
            #[CastTo\Uppercase]
            public string | int | null $name = null;
        };

        $entity = new class {
            public string | int | null $itemId = '5';

            public ?string $email = 'name@domain.test';

            private string | int | null $name = 'sam';

            public function getName(): string | int | null
            {
                return $this->name;
            }

            public function setName(string $name): void
            {
                $this->name = $name;
            }

            public string | int | null $ignoredPropNotOnDTO = 'value';
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
            public string | int | null $itemNumber = '5';
            public ?string $email = 'name@domain.test';
            // name is private, not accessible
            private string | int | null $name = 'sam';
        };

        $this->expectException(AccessorException::class);
        $this->expectExceptionMessage('No public getter or property found for: itemId, name in '.get_class($entity));

        // attempts to create a new FromArrayTestDto from data (itemId, name, email) taken from the entity
        // but itemId and name do not exist on the entity, so it should throw an exception
        FromArrayTestDto::fromEntity($entity, false);
    }

    public static function presencePolicyOverridingTestProvider(): array
    {
        return [
            'null value + clear' => ['clear' => true, 'fillWith' => 'null', 'expected' => [
                'defaultPresencePolicyProp'       => ['filled' => true, 'val' => null],
                'nullMeansMissingProp'            => ['filled' => false, 'val' => 'default-bar'], // missing -> reset
                'missingMeansDefaultProp'         => ['filled' => true, 'val' => null],
            ]],
            'missing value + clear' => ['clear' => true, 'fillWith' => 'missing', 'expected' => [
                'defaultPresencePolicyProp'       => ['filled' => false, 'val' => 'default-foo'],
                'nullMeansMissingProp'            => ['filled' => false, 'val' => 'default-bar'],
                'missingMeansDefaultProp'         => ['filled' => true, 'val' => 'default-baz'],
            ]],
            'null value' => ['clear' => false, 'fillWith' => 'null', 'expected' => [
                'defaultPresencePolicyProp'       => ['filled' => true, 'val' => null],
                'nullMeansMissingProp'            => ['filled' => true, 'val' => 'bar'], // missing + not cleared = untouched
                'missingMeansDefaultProp'         => ['filled' => true, 'val' => null],
            ]],
            'missing value' => ['clear' => false, 'fillWith' => 'missing', 'expected' => [
                'defaultPresencePolicyProp'       => ['filled' => true, 'val' => 'foo'], // untouched
                'nullMeansMissingProp'            => ['filled' => true, 'val' => 'bar'], // untouched
                'missingMeansDefaultProp'         => ['filled' => true, 'val' => 'default-baz'], // missing means "apply default!"
            ]],
        ];
    }

    #[DataProvider('presencePolicyOverridingTestProvider')]
    public function testPresencePolicyResolutionWithMissingNullAndOptionalClear(bool $clear, string $fillWith, array $expected): void
    {
        // ->clear() resets props to their default value.
        // When $clear param = true, ->clear(unfilled props) is called after load.
        // In the case of presence policies, unfilled props are:
        // - for Default policy: missing only (nulls are marked as filled)
        // - for NullMeansMissing: both missing and nulls
        // - for MissingMeansDefault: none - always filled, either with input value or default

        // --- Arrange ---

        // Create a DTO pre-filled with non-defaults values, so we can observe resets
        $dto = PresencePolicyOverridingBaseDto::fromArray([
            'defaultPresencePolicyProp'       => 'foo',
            'nullMeansMissingProp'            => 'bar',
            'missingMeansDefaultProp'         => 'baz',
        ]);

        // prepare test input
        if ('missing' === $fillWith) {
            // test missing input
            $input = [];
        } else {
            // test null input
            $input = [
                'defaultPresencePolicyProp'       => null,
                'nullMeansMissingProp'            => null,
                'missingMeansDefaultProp'         => null,
            ];
        }

        // --- Act ---
        $dto->_fromArray($input, clear: $clear);

        // --- Assert ---
        $actual = fn (string $prop): array => [
            'filled' => $dto->_filled[$prop] ?? false,
            'val'    => $dto->$prop,
        ];
        $this->assertSame($expected['defaultPresencePolicyProp'], $actual('defaultPresencePolicyProp'));
        $this->assertSame($expected['nullMeansMissingProp'], $actual('nullMeansMissingProp'));
        $this->assertSame($expected['missingMeansDefaultProp'], $actual('missingMeansDefaultProp'));
    }

    public function testMapperFailToNullSilentlyUnderNullMeansMissingPolicy(): void
    {
        $dtoClass = new class extends BaseDto {
            use CreatesFromArrayOrEntity;
            /**
             * @psalm-suppress PossiblyUnusedProperty
             **/
            #[MapFrom('item_id', ThrowMode::MISSING_KEY)] // mapper throws if 'item_id' is missing
            #[Presence(PresencePolicy::NullMeansMissing)]
            public string | int | null $itemId = null;
        };

        // first test happy path: non-null input - mapper doesn't fail and property is filled
        /** @psalm-suppress NoValue, UnusedVariable */
        // Note, using fromArrayLoose() instead of fromArray() is necessary here to
        // avoid exception on unknown prop 'item_id'
        $dto = $dtoClass::fromArrayLoose(['item_id' => 'bar']);
        $this->assertArrayHasKey('itemId', $dto->_filled);
        $this->assertSame('bar', $dto->itemId);

        // missing input - mapper fails, but property is left unfilled silently
        /** @psalm-suppress NoValue, UnusedVariable */
        // 'item_id' is missing, so mapper fails
        $dto->_fromArray([]);

        $this->assertArrayNotHasKey('itemId', $dto->_filled);
        /** @psalm-suppress DocblockTypeContradiction */
        $this->assertNull($dto->itemId);
    }
}

final class FromArrayTestDto extends BaseDto implements ProcessesInterface
{
    use CreatesFromArrayOrEntity;
    use ProcessesFromAttributes;

    #[CastTo\Integer]
    public string | int | null $itemId = null;
    public ?string $email = null;
    #[CastTo\Uppercase]
    public string | int | null $name = null;
}

#[Presence(PresencePolicy::NullMeansMissing)]
final class PresencePolicyOverridingBaseDto extends BaseDto
{
    use CreatesFromArrayOrEntity;
    /**
     * DTO's `NullMeansMissing` presence policy overriden back to `Default`.
     *
     * @psalm-suppress PossiblyUnusedProperty
     **/
    #[Presence(PresencePolicy::Default)]
    public ?string $defaultPresencePolicyProp = 'default-foo';

    /**
     * Inherits NullMeansMissing from class-level PresencePolicy attribute.
     *
     * @psalm-suppress PossiblyUnusedProperty
     **/
    public ?string $nullMeansMissingProp = 'default-bar';

    /**
     * Presence policy overriden to MissingMeansDefault.
     *
     * @psalm-suppress PossiblyUnusedProperty
     **/
    #[Presence(PresencePolicy::MissingMeansDefault)]
    public ?string $missingMeansDefaultProp = 'default-baz';
}

<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit;

use Nandan108\DtoToolkit\Attribute\DefaultOutboundEntity;
use Nandan108\DtoToolkit\Attribute\MapFrom;
use Nandan108\DtoToolkit\Attribute\MapTo;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\PreparesEntityInterface;
use Nandan108\DtoToolkit\Contracts\ProcessesInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Enum\ConstructMode;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Internal\Exporter;
use Nandan108\DtoToolkit\Support\ContainerBridge;
use Nandan108\DtoToolkit\Traits\ExportsOutbound;
use Nandan108\DtoToolkit\Traits\ExportsOutboundTyped;
use Nandan108\DtoToolkit\Traits\ProcessesFromAttributes;
use Nandan108\PropAccess\Exception\AccessorException;
use Nandan108\PropAccess\PropAccess;
use PHPUnit\Framework\TestCase;

/** @psalm-suppress UnusedClass */
final class ExportsOutboundTest extends TestCase
{
    #[\Override]
    public function setUp(): void
    {
        parent::setUp();
        // Ensure that the test environment is clean
        PropAccess::bootDefaultResolvers();
    }

    public static function getStandardInput(): array
    {
        return [
            'id'             => 123,
            'username'       => 'alice',
            'public_profile' => [
                'name'      => 'Alice Example',
                'birthdate' => '2025-01-02T00:00:00+00:00',
                'bio'       => 'Hello',
                'interests' => [
                    'categories' => '1,2,3',
                    'book'       => '10,11',
                ],
            ],
            'address' => [
                'street'    => '123 Main St',
                'locality'  => 'Springfield',
                'zip'       => '99999',
                'state'     => 'NA',
                'ctry_code' => 'US',
            ],
        ];
    }

    public function testDtoInstanciatesNewEntityFromEntityClassProperty(): void
    {
        $dto = new
        #[DefaultOutboundEntity(EntityClassToInstanciateFromName::class)]
        class extends FullDto {
            public ?string $fooProp = null;
        };

        $entity1 = $dto->fill(['fooProp' => 'someVal'])->exportToEntity();
        /** @psalm-suppress TypeDoesNotContainType */
        $this->assertInstanceOf(EntityClassToInstanciateFromName::class, $entity1);

        $entity2 = $dto->fill(['fooProp' => 'someOtherVal'])->exportToEntity();
        /** @psalm-suppress TypeDoesNotContainType */
        $this->assertInstanceOf(EntityClassToInstanciateFromName::class, $entity2);

        $this->assertNotSame($entity1, $entity2, 'Each call to toEntity() should instanciate and return a new entity');
    }

    /** @psalm-suppress MixedMethodCall */
    public function testMapsDtoFieldsToEntitySetters(): void
    {
        // Create dummy entity
        $entity = \Mockery::mock(new class {
            public ?int $id = null;
            public ?string $name = null;
            public int | string | null $age = null;
            public ?string $email = null;

            // note, these setters are not being used because the entity is mocked
            public function setName(string $name): void
            {
                $this->name = $name;
            }

            public function setAge(int $age): void
            {
                $this->age = $age;
            }
            // no setter for email
        });

        $inputName = '  Alice  ';
        $inputAge = '45';

        /** @psalm-suppress UndefinedMagicMethod, UndefinedInterfaceMethod */
        $entity->shouldReceive('setName')
            ->once()->with($trimmedName = trim($inputName))
            ->andReturnUsing(function (string $val) use ($entity) {
                $entity->name = $val;
            });

        /** @psalm-suppress UndefinedMagicMethod, UndefinedInterfaceMethod */
        $entity->shouldReceive('setAge')
            ->once()->with($intAge = (int) $inputAge)
            ->andReturnUsing(function (int $val) use ($entity) {
                $entity->age = $val;
            });

        // Create DTO
        $dto = new class extends BaseDto implements ProcessesInterface {
            use ProcessesFromAttributes;
            use ExportsOutbound;

            public ?int $id = null;
            #[CastTo\Trimmed]
            public ?string $name = null;
            #[CastTo\Integer]
            public int | string | null $age = null;
            #[CastTo('trimmedString')]
            public ?string $email = null;

            public function castToTrimmedString(?string $str): string
            {
                return trim($str ?? '');
            }
        };

        $dto->fill([
            'name'  => $trimmedName,
            'age'   => $intAge,
            'email' => 'some@email',
        ]);

        // un-mark email as 'filled'
        $dto->unfill(['email']);

        $entity = $dto->exportToEntity(entity: $entity, supplementalProps: ['id' => $id = 4]);

        $this->assertSame($trimmedName, $entity->name);
        $this->assertSame($intAge, $entity->age);
        $this->assertSame($id, $entity->id);
        // email should not be set
        $this->assertNull($entity->email);
        $dto->unfill();
        $this->assertSame([], $dto->_filled);
    }

    public function testThrowsExceptionIfEntityClassDoesNotExist(): void
    {
        $dto = new
        /** @psalm-suppress ArgumentTypeCoercion, UndefinedClass */
        #[DefaultOutboundEntity('NonExistentFooClass')]
        class extends BaseDto {
            /** @use ExportsOutboundTyped<NonExistentFooClass> */
            use ExportsOutboundTyped;
        };

        // test when no param is given, uses DefaultOutboundEntity
        try {
            $dto->exportToEntity();
            $this->fail('Expected InvalidConfigException was not thrown');
        } catch (InvalidConfigException $e) {
            $this->assertStringContainsString('Class "NonExistentFooClass" not found', $e->getMessage());
        }

        // test when param is given, overrides DefaultOutboundEntity
        try {
            $dto->exportToEntity(entity: 'NonExistentBarClass');
            $this->fail('Expected InvalidConfigException was not thrown');
        } catch (InvalidConfigException $e) {
            $this->assertStringContainsString('Class "NonExistentBarClass" not found', $e->getMessage());
        }
    }

    public function testThrowsExceptionIfEntityClassIsNotSet(): void
    {
        /** @psalm-suppress MissingTemplateParam */
        $dto = new class extends BaseDto {
            use ExportsOutbound;
        };

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('No target entity object or class name provided');

        $dto->exportToEntity();
    }

    public function testThrowsExceptionIfGroupsDeclaredWithoutGroupsInterface(): void
    {
        $dto = new
        #[DefaultOutboundEntity(EntityClassToInstanciateFromName::class, groups: 'scoped')]
        class extends BaseDto {
            /** @use ExportsOutboundTyped<EntityClassToInstanciateFromName> */
            use ExportsOutboundTyped;
        };

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('declares scoping groups');

        DefaultOutboundEntity::resolveForDto($dto);
    }

    public function testExportToEntityFromArrayWithoutTargetThrows(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('No target entity object or class name provided');

        Exporter::export(source: ['id' => 1], as: 'entity');
    }

    public function testExportToEntityUsesContainerBinding(): void
    {
        // register a factory for the entity class, on the container
        ContainerBridge::register(
            EntityClassToInstanciateFromName::class,
            fn () => new EntityClassToInstanciateFromName(),
        );

        try {
            $dto = new
            #[DefaultOutboundEntity(EntityClassToInstanciateFromName::class)]
            class extends BaseDto {
                use ExportsOutbound;
                public ?string $fooProp = null;
            };

            $result = $dto->exportToEntity();

            $this->assertInstanceOf(EntityClassToInstanciateFromName::class, $result);
        } finally {
            ContainerBridge::clearBindings();
        }
    }

    public function testThrowsExceptionIfEntityClassHasNoWayToSetProperty(): void
    {
        $dto = new
        #[DefaultOutboundEntity(EntityClassToInstanciateFromName::class)]
        class extends BaseDto {
            use ExportsOutbound;
            /** @psalm-suppress NonInvariantDocblockPropertyType */
            public ?string $fooProp = null;
            public ?string $email = null;
        };

        $this->expectException(AccessorException::class);
        $this->expectExceptionMessage('prop_access.setter_access_not_found');

        /** @psalm-suppress UnusedMethodCall */
        $dto->fill(['fooProp' => 'someVal', 'email' => 'foo@bar.baz']);
        $dto->exportToEntity();
    }

    public function testToEntityAllowsOverridingPrepareEntity(): void
    {
        $dto = new class extends FullDto implements PreparesEntityInterface {
            /** @use ExportsOutboundTyped<EntityClassToInstanciateFromName> */
            use ExportsOutboundTyped;
            public ?string $fooProp = null;

            #[\Override]
            public function prepareEntity(array $outboundProps): array
            {
                $entity = new EntityClassToInstanciateFromName();
                $entity->fooProp = $outboundProps['fooProp'] ?? null;

                return [
                    'entity'   => $entity,
                    'hydrated' => true, // properties are already loaded
                ];
            }
        };

        $entity = $dto->fill(['fooProp' => 'someVal'])->exportToEntity();

        $this->assertInstanceOf(EntityClassToInstanciateFromName::class, $entity);
        $this->assertSame('someVal', $entity->fooProp);
    }

    public function testToEntityUsesMapToAttributes(): void
    {
        $dto = new #[DefaultOutboundEntity(EntityClassToInstanciateFromName::class)]
        class extends FullDto {
            public ?string $doop = null;

            #[MapTo('fooProp')]
            public ?string $foo = null;

            // Note: this will use the setter method `setBarProp()` (converts to uppercase)
            #[MapTo('barProp')]
            public ?string $bar = null;

            // Note: this will use the setter method `assignBazProp()`
            #[MapTo(null, 'assignBazProp')]
            public ?string $baz = null;
        };

        $dto->fill(['doop' => 'doop', 'foo' => 'someVal', 'bar' => 'anotherVal', 'baz' => 'someBaz']);
        /** @psalm-suppress UnusedMethodCall */
        $entity = $dto->exportToEntity();

        $this->assertInstanceOf(EntityClassToInstanciateFromName::class, $entity);
        $this->assertSame('someVal', $entity->fooProp);
        $this->assertSame('ANOTHERVAL', $entity->barProp);
        $this->assertSame('somebaz', $entity->bazProp);
        $this->assertSame('doop', $entity->doop);
    }

    public function testToEntityRecursiveConvertsNestedDtos(): void
    {
        PropAccess::bootDefaultResolvers();

        $input = self::getStandardInput();
        $dto = RecursiveUserDto::newFromArray($input);

        $entity = $dto->exportToEntity(recursive: true);
        // - RecursiveUserDto -> RecursiveUserEntity
        //   - RecursivePublicProfileDto -> RecursivePublicProfileEntity
        //     - RecursiveInterestsDto -> RecursiveInterestsEntity
        //   - RecursiveAddressDto -> RecursiveAddressEntity

        $this->assertInstanceOf(RecursiveUserEntity::class, $entity);
        $this->assertInstanceOf(RecursivePublicProfileEntity::class, $entity->public_profile);
        $this->assertInstanceOf(RecursiveInterestsEntity::class, $entity->public_profile->interests);
        $this->assertInstanceOf(RecursiveAddressEntity::class, $entity->address);
        $this->assertSame('alice', $entity->username);
        $this->assertSame([1, 2, 3], $entity->public_profile->interests->categories);
        $this->assertSame('US', $entity->address->ctry_code);
    }

    public function testDefaultOutboundClass(): void
    {
        $dtoClass = new
        #[DefaultOutboundEntity(ImmutableAddressVoPropPromoConstructor::class, ConstructMode::NamedArgs, ['NamedArgs'])]
        #[DefaultOutboundEntity(ImmutableAddressVoArrayConstructor::class, ConstructMode::Array)]
        class extends FullDto {
            use ExportsOutbound;
            public ?string $street = null;

            #[MapFrom('locality_zip[0]')]
            public ?string $locality = null;

            #[MapFrom('locality_zip[1]')]
            public ?string $zip = null;

            public ?string $state = null;
            #[CastTo\Uppercase(), CastTo\Trimmed]
            #[MapTo('ctryCode')]
            public ?string $ctry_code = null;

            #[MapTo(null)]
            public ?string $ignored_prop = null;
        };

        $dto = $dtoClass::newFromArrayLoose([
            'street'        => '123 Main St',
            'locality_zip'  => ['Springfield', '99999'],
            'state'         => 'NA',
            'ctry_code'     => ' us ',
            'ignored_prop'  => 'should be ignored',
        ]);

        $immutableAddressVo1 = $dto->exportToEntity();
        $this->assertInstanceOf(ImmutableAddressVoArrayConstructor::class, $immutableAddressVo1);
        $this->assertSame('123 Main St', $immutableAddressVo1->street);
        $this->assertSame('Springfield', $immutableAddressVo1->locality);
        $this->assertSame('99999', $immutableAddressVo1->zip);
        $this->assertSame('NA', $immutableAddressVo1->state);
        $this->assertSame('US', $immutableAddressVo1->ctryCode);

        $immutableAddressVo2 = $dto->withGroups(['NamedArgs'])->exportToEntity();
        $this->assertInstanceOf(ImmutableAddressVoPropPromoConstructor::class, $immutableAddressVo2);
        $this->assertSame('123 Main St', $immutableAddressVo2->street);
        $this->assertSame('Springfield', $immutableAddressVo2->locality);
        $this->assertSame('99999', $immutableAddressVo2->zip);
        $this->assertSame('NA', $immutableAddressVo2->state);
        $this->assertSame('US', $immutableAddressVo2->ctryCode);

    }
}

/**
 * Dummy class for reflection testing.
 *
 * @psalm-suppress UnusedClass
 * */
final class EntityClassToInstanciateFromName
{
    public ?string $doop = null;

    /** @psalm-suppress PossiblyUnusedProperty */
    public ?string $fooProp = null;
    public ?string $barProp = null;

    /** @psalm-suppress PossiblyUnusedMethod */
    public function setBarProp(string $bar): void
    {
        $this->barProp = strtoupper($bar);
    }

    public ?string $bazProp = null;

    /** @psalm-suppress PossiblyUnusedMethod */
    public function assignBazProp(string $baz): void
    {
        $this->bazProp = strtolower($baz);
    }
}

/** @psalm-suppress PossiblyUnusedProperty */
final class RecursiveUserEntity
{
    public int $id = 0;
    public string $username = '';
    public RecursivePublicProfileEntity | array | null $public_profile = null;
    public RecursiveAddressEntity | array | null $address = null;
}

/** @psalm-suppress PossiblyUnusedProperty */
final class RecursivePublicProfileEntity
{
    public string $name = '';
    public \DateTimeInterface | string | null $birthdate = null;
    public string $bio = '';
    public RecursiveInterestsEntity | array | null $interests = null;
}

/** @psalm-suppress PossiblyUnusedProperty */
final class RecursiveInterestsEntity
{
    public array $categories = [];
    public array $book = [];
}

/** @psalm-suppress PossiblyUnusedProperty */
final class RecursiveAddressEntity
{
    public string $street = '';
    public string $locality = '';
    public string $zip = '';
    public string $state = '';
    public string $ctry_code = '';
}

/** @psalm-suppress PossiblyUnusedProperty */
#[DefaultOutboundEntity(RecursiveUserEntity::class)]
final class RecursiveUserDto extends FullDto
{
    public int $id = 0;
    public string $username = '';

    #[CastTo\Dto(RecursivePublicProfileDto::class)]
    public RecursivePublicProfileDto | array | null $public_profile = null;

    #[CastTo\Dto(RecursiveAddressDto::class)]
    public RecursiveAddressDto | array | null $address = null;

}

/** @psalm-suppress PossiblyUnusedProperty */
#[DefaultOutboundEntity(RecursivePublicProfileEntity::class)]
final class RecursivePublicProfileDto extends FullDto
{
    public string $name = '';

    #[CastTo\DateTime]
    public \DateTimeInterface | string | null $birthdate = null;

    public string $bio = '';

    #[CastTo\Dto(RecursiveInterestsDto::class)]
    public RecursiveInterestsDto | array | null $interests = null;
}

/** @psalm-suppress PossiblyUnusedProperty */
#[DefaultOutboundEntity(RecursiveInterestsEntity::class)]
final class RecursiveInterestsDto extends FullDto
{
    #[CastTo\Split]
    #[\Nandan108\DtoToolkit\Attribute\ChainModifier\PerItem, CastTo\Integer]
    public string | array $categories = [];

    #[CastTo\Split]
    #[\Nandan108\DtoToolkit\Attribute\ChainModifier\PerItem, CastTo\Integer]
    public string | array $book = [];
}

/** @psalm-suppress PossiblyUnusedProperty */
#[DefaultOutboundEntity(RecursiveAddressEntity::class)]
final class RecursiveAddressDto extends FullDto
{
    public string $street = '';
    public string $locality = '';
    public string $zip = '';
    public string $state = '';
    public string $ctry_code = '';
}

/** @psalm-suppress PossiblyUnusedProperty */
final class ImmutableAddressVoArrayConstructor
{
    public readonly ?string $street;
    public readonly ?string $locality;
    public readonly ?string $zip;
    public readonly ?string $state;
    public readonly ?string $ctryCode;

    /** @psalm-suppress PossiblyUnusedMethod, MixedAssignment */
    public function __construct(array $props)
    {
        $this->street = $props['street'] ?? null;
        $this->locality = $props['locality'] ?? null;
        $this->zip = $props['zip'] ?? null;
        $this->state = $props['state'] ?? null;
        $this->ctryCode = $props['ctryCode'] ?? null;
    }
}

/** @psalm-suppress PossiblyUnusedProperty */
final class ImmutableAddressVoPropPromoConstructor
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public function __construct(
        public ?string $ctryCode, // note: ctryCode is first to test param order independence
        public ?string $street,
        public ?string $locality,
        public ?string $zip,
        public ?string $state,
    ) {
    }
}

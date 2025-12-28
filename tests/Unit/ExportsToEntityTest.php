<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit;

use Nandan108\DtoToolkit\Attribute\MapTo;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\ProcessesInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Traits\ExportsToEntity;
use Nandan108\DtoToolkit\Traits\ProcessesFromAttributes;
use Nandan108\PropAccess\Exception\AccessorException;
use Nandan108\PropAccess\PropAccess;
use PHPUnit\Framework\TestCase;

/** @psalm-suppress UnusedClass */
final class ExportsToEntityTest extends TestCase
{
    #[\Override]
    public function setUp(): void
    {
        parent::setUp();
        // Ensure that the test environment is clean
        PropAccess::bootDefaultResolvers();
    }

    public function testDtoInstanciatesNewEntityFromEntityClassProperty(): void
    {
        $dto = new class extends BaseDto {
            use ExportsToEntity;
            public ?string $fooProp = null;

            public function __construct()
            {
                static::$entityClass = EntityClassToInstanciateFromName::class;
            }
        };

        $entity1 = $dto->fill(['fooProp' => 'someVal'])->toEntity();
        /** @psalm-suppress TypeDoesNotContainType */
        $this->assertInstanceOf(EntityClassToInstanciateFromName::class, $entity1);

        $entity2 = $dto->fill(['fooProp' => 'someOtherVal'])->toEntity();
        /** @psalm-suppress TypeDoesNotContainType */
        $this->assertInstanceOf(EntityClassToInstanciateFromName::class, $entity2);

        $this->assertNotSame($entity1, $entity2, 'Each call to toEntity() should instanciate and return a new entity');
    }

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
        /** @psalm-suppress ExtensionRequirementViolation (psalm = dumb+blind+crazy! Aaargh!) */
        $dto = new class extends BaseDto implements ProcessesInterface {
            use ProcessesFromAttributes;
            use ExportsToEntity;
            // use CanCastBasicValues;

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

        $entity = $dto->toEntity(entity: $entity, context: ['id' => $id = 4]);

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
        $dto = new class extends BaseDto {
            use ExportsToEntity;

            public function __construct()
            {
                /** @psalm-suppress PropertyTypeCoercion */
                static::$entityClass = 'NonExistentClass';
            }
        };

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('Entity class NonExistentClass does not exist');

        $dto->toEntity();
    }

    public function testThrowsExceptionIfEntityClassIsNotSet(): void
    {
        $dto = new class extends BaseDto {
            use ExportsToEntity;
        };

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('No entity class specified on DTO');

        $dto->toEntity();
    }

    public function testThrowsExceptionIfDtoClassDoesNotExtendBaseDto(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class {
            use ExportsToEntity;
        };

        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('DTO must extend BaseDto to use ExportsToEntity trait.');

        $dto->toEntity();
    }

    public function testThrowsExceptionIfEntityClassHasNoWayToSetProperty(): void
    {
        $dto = new class extends BaseDto {
            use ExportsToEntity;
            /** @psalm-suppress NonInvariantDocblockPropertyType */
            public ?string $fooProp = null;
            public ?string $email = null;

            public function __construct()
            {
                static::$entityClass = EntityClassToInstanciateFromName::class;
            }
        };

        $this->expectException(AccessorException::class);
        $this->expectExceptionMessage('No public setter or property found');

        /** @psalm-suppress UnusedMethodCall */
        $dto->fill(['fooProp' => 'someVal', 'email' => 'foo@bar.baz']);
        $dto->toEntity();
    }

    public function testToEntityAllowsOverridingNewEntityInstance(): void
    {
        $dto = new class extends BaseDto {
            use ExportsToEntity;
            public ?string $fooProp = null;

            public function __construct()
            {
                static::$entityClass = EntityClassToInstanciateFromName::class;
            }

            protected function newEntityInstance(array $inputData = []): array
            {
                if (null === static::$entityClass) {
                    throw new InvalidConfigException('Entity class must not be null');
                }
                $entity = new static::$entityClass();
                $entity->fooProp = $inputData['fooProp'] ?? null;

                // entity properties already set!
                return [
                    $entity,
                    true, // indicate that the entity properties are already loaded
                ];
            }
        };

        $entity = $dto->fill(['fooProp' => 'someVal'])->toEntity();

        $this->assertInstanceOf(EntityClassToInstanciateFromName::class, $entity);
        $this->assertSame('someVal', $entity->fooProp);
    }

    public function testToEntityUsesMapToAttributes(): void
    {
        $dto = new class extends FullDto {
            /** @var ?class-string */
            protected static ?string $entityClass = EntityClassToInstanciateFromName::class;

            public ?string $doop = null;

            #[MapTo('fooProp')]
            public ?string $foo = null;

            // Note: this will use the setter method `setBarProp()` (converts to uppercase)
            #[MapTo('barProp')]
            public ?string $bar = null;

            // Note: this will use the setter method `assignBazProp()`
            #[MapTo(null, 'assignBazProp')]
            public ?string $baz = null;

            public function __construct()
            {
            }
        };

        $dto->fill(['doop' => 'doop', 'foo' => 'someVal', 'bar' => 'anotherVal', 'baz' => 'someBaz']);
        /** @psalm-suppress UnusedMethodCall */
        $entity = $dto->toEntity();

        $this->assertInstanceOf(EntityClassToInstanciateFromName::class, $entity);
        $this->assertSame('someVal', $entity->fooProp);
        $this->assertSame('ANOTHERVAL', $entity->barProp);
        $this->assertSame('somebaz', $entity->bazProp);
        $this->assertSame('doop', $entity->doop);
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

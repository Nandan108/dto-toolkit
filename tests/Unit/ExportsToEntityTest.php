<?php

namespace Nandan108\DtoToolkit\Tests\Unit;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\NormalizesInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Traits\ExportsToEntity;
use Nandan108\DtoToolkit\Traits\NormalizesFromAttributes;
use PHPUnit\Framework\TestCase;

/** @psalm-suppress UnusedClass */
final class ExportsToEntityTest extends TestCase
{
    public function testDtoInstanciatesNewEntityFromEntityClassProperty(): void
    {
        $dto = new class extends BaseDto {
            use ExportsToEntity;
            public ?string $someProp = null;

            public function __construct()
            {
                static::$entityClass = EntityClassToInstanciateFromName::class;
            }
        };

        $entity1 = $dto->fill(['someProp' => 'someVal'])->toEntity();
        /** @psalm-suppress TypeDoesNotContainType */
        $this->assertInstanceOf(EntityClassToInstanciateFromName::class, $entity1);

        $entity2 = $dto->fill(['someProp' => 'someOtherVal'])->toEntity();
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
            public int|string|null $age = null;
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
        $dto = new class extends BaseDto implements NormalizesInterface {
            use NormalizesFromAttributes;
            use ExportsToEntity;
            // use CanCastBasicValues;

            public ?int $id = null;
            #[CastTo\Trimmed]
            public ?string $name = null;
            #[CastTo\Integer]
            public int|string|null $age = null;
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

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Entity class NonExistentClass does not exist');

        $dto->toEntity();
    }

    public function testThrowsExceptionIfEntityClassIsNotSet(): void
    {
        $dto = new class extends BaseDto {
            use ExportsToEntity;
        };

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No entity class specified on DTO');

        $dto->toEntity();
    }

    public function testThrowsExceptionIfDtoClassDoesNotExtendBaseDto(): void
    {
        /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class {
            use ExportsToEntity;
        };

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('DTO must extend BaseDto to use ExportsToEntity trait.');

        $dto->toEntity();
    }

    public function testThrowsExceptionIfEntityClassHasNoWayToSetProperty(): void
    {
        $dto = new class extends BaseDto {
            use ExportsToEntity;
            /** @psalm-suppress NonInvariantDocblockPropertyType */
            public ?string $someProp = null;
            public ?string $email = null;

            public function __construct()
            {
                static::$entityClass = EntityClassToInstanciateFromName::class;
            }
        };

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No public setter or property found');

        /** @psalm-suppress UnusedMethodCall */
        $dto->fill(['someProp' => 'someVal', 'email' => 'foo@bar.baz'])->toEntity();
    }

    public function testToEntityAllowsOverridingNewEntityInstance(): void
    {
        $dto = new class extends BaseDto {
            use ExportsToEntity;
            public ?string $someProp = null;

            public function __construct()
            {
                static::$entityClass = EntityClassToInstanciateFromName::class;
            }

            protected function newEntityInstance(array $inputData = []): array
            {
                if (null === static::$entityClass) {
                    throw new \LogicException('Entity class must not be null');
                }
                $entity = new static::$entityClass();
                $entity->someProp = $inputData['someProp'] ?? null;

                // entity properties already set!
                return [
                    $entity,
                    true, // indicate that the entity properties are already loaded
                ];
            }
        };

        /** @var object $entity */
        $entity = $dto->fill(['someProp' => 'someVal'])->toEntity();

        $this->assertInstanceOf(EntityClassToInstanciateFromName::class, $entity);
        $this->assertSame('someVal', $entity->someProp);
    }
}

/**
 * Dummy class for reflection testing.
 *
 * @psalm-suppress UnusedClass
 * */
final class EntityClassToInstanciateFromName
{
    /** @psalm-suppress PossiblyUnusedProperty */
    public ?string $someProp = null;
    // no setter for email
}

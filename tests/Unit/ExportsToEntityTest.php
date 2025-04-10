<?php

namespace Tests\Unit;

use Mockery;
use Nandan108\DtoToolkit\Contracts\NormalizesOutboundInterface;
use Nandan108\DtoToolkit\Traits\ExportsToEntity;
use PHPUnit\Framework\TestCase;
use Nandan108\DtoToolkit\Attribute\CastTo;
use Nandan108\DtoToolkit\BaseDto;
use Nandan108\DtoToolkit\Traits\NormalizesFromAttributes;

/** @psalm-suppress UnusedClass */
class ExportsToEntityTest extends TestCase
{
    public function test_dto_instanciates_new_entity_from_entityClass_property(): void
    {
        $dto = new class extends BaseDto {
            use ExportsToEntity;
            static protected ?string $entityClass = EntityClassToInstanciateFromName::class;
            /** @psalm-suppress PossiblyUnusedProperty */
            public string $someProp;
        };

        $entity1 = $dto->fill(['someProp' => 'someVal'])->toEntity();
        $this->assertInstanceOf(EntityClassToInstanciateFromName::class, $entity1);

        $entity2 = $dto->fill(['someProp' => 'someOtherVal'])->toEntity();
        $this->assertInstanceOf(EntityClassToInstanciateFromName::class, $entity2);

        $this->assertNotSame($entity1, $entity2, 'Each call to toEntity() should instanciate and return a new entity');
    }

    public function test_maps_dto_fields_to_entity_setters(): void
    {
        // Create dummy entity
        $entity = Mockery::mock(new class {
            public ?int $id = null;
            public ?string $name = null;
            public int|string|null $age = null;
            public ?string $email = null;

            public function setName(string $name): void { $this->name = $name; }
            public function setAge(int $age): void { $this->age = $age; }
            // no setter for email
        });

        $inputName = '  Alice  ';
        $inputAge = '45';

        $entity->shouldReceive('setName')
            ->once()->with($trimmedName = trim($inputName))
            ->andReturnUsing(function ($val) use ($entity) {
                $entity->name = $val;
            });

        $entity->shouldReceive('setAge')
            ->once()->with($intAge = (int)$inputAge)
            ->andReturnUsing(function ($val) use ($entity) {
                $entity->age = $val;
            });

        // Create DTO
        $dto = new class extends BaseDto implements NormalizesOutboundInterface {
            use NormalizesFromAttributes;
            use ExportsToEntity;

            public ?int $id = null;
            #[CastTo('trimmedString')]
            public string $name;
            #[CastTo('intOrNull')]
            public int|string $age;
            #[CastTo('trimmedString')]
            public ?string $email;
        };

        $dto->fill([
            'name' => $trimmedName,
            'age'  => $intAge,
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
    }

    public function test_throws_exception_if_entity_class_does_not_exist(): void
    {
        $dto = new class extends BaseDto {
            use ExportsToEntity;
            static protected ?string $entityClass = 'NonExistentClass';
        };

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Entity class NonExistentClass does not exist');

        $dto->toEntity();
    }
    public function test_throws_exception_if_entity_class_is_not_set(): void
    {
        $dto = new class extends BaseDto {
            use ExportsToEntity;
            static protected ?string $entityClass = null;
        };

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No entity class defined for DTO');

        $dto->toEntity();
    }

    public function test_throws_exception_if_dto_class_does_not_extend_BaseDto(): void
    {
        $dto = new class {
            use ExportsToEntity;
        };

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('DTO must extend BaseDto to use ExportsToEntity.');

        $dto->toEntity();
    }

    public function test_throws_exception_if_entity_class_has_no_way_to_set_property(): void
    {
        $dto = new class extends BaseDto {
            use ExportsToEntity;
            static protected ?string $entityClass = EntityClassToInstanciateFromName::class;
            public string $someProp;
            public string $email;
        };

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('No public setter or property found');

        $dto->fill(['someProp' => 'someVal', 'email' => 'foo@bar.baz'])->toEntity();
    }
}

/**
 * Dummy class for reflection testing
 *
 * @psalm-suppress UnusedClass
 * */
class EntityClassToInstanciateFromName {
    /** @psalm-suppress PossiblyUnusedProperty */
    public ?string $someProp = null;
    // no setter for email
};

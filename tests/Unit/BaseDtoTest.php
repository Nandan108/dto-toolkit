<?php

namespace Tests\Unit\Dto;

use Mockery;
use Nandan108\SymfonyDtoToolkit\Contracts\NormalizesOutbound;
use PHPUnit\Framework\TestCase;
use Nandan108\SymfonyDtoToolkit\Attribute\CastTo;
use Nandan108\SymfonyDtoToolkit\BaseDto;
use Nandan108\SymfonyDtoToolkit\Traits\NormalizesFromAttributes;
use Symfony\Component\HttpFoundation\Request;

/** @psalm-suppress UnusedClass */
class BaseDtoTest extends TestCase
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

    public function test_instantiates_dto_from_request_object(): void
    {
        // create a request with a POST payload
        $request = new Request(
            query: [ // GET
                'item_id' => $rawItemId = '5',
                'age'     => $rawAge = '30',
            ],
            request: [ // POST
                'email' => $rawEmail = ' john@example.com  ',
                'age'   => '25',
            ],
        );

        $dto = new class extends BaseDto {
            use NormalizesFromAttributes;

            // imput sources are merged in order, so later sources override earlier ones
            protected array $inputSources = ['POST', 'GET'];

            public string|int $item_id;
            public string $email;
            public string|int|null $age;
        };

        $dto = $dto::fromRequest($request);

        // still raw
        $this->assertSame($rawEmail, $dto->email);
        // still raw, taken from GET
        $this->assertSame($rawAge, $dto->age);
        $this->assertSame($rawItemId, $dto->item_id);

        // filled
        $this->assertArrayHasKey('email', $dto->filled);
        $this->assertArrayHasKey('age', $dto->filled);
        $this->assertArrayHasKey('item_id', $dto->filled);
    }


    // public function test_handles_missing_optional_properties(): void {}
    // public function test_supports_get_entity_setter_map(): void {}

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
        $dto = new class extends BaseDto implements NormalizesOutbound {
            use NormalizesFromAttributes;
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

    public function test_dto_instanciates_new_entity_from_entityClass_property(): void
    {
        $dto = new class extends BaseDto {
            static protected ?string $entityClass = EntityClassToInstanciateFromName::class;
            /** @psalm-suppress PossiblyUnusedProperty */
            public string $someProp;
        };

        $entity = $dto->fill(['someProp' => 'someVal'])->toEntity();

        $this->assertInstanceOf(EntityClassToInstanciateFromName::class, $entity);
    }

}

/** @psalm-suppress UnusedClass */
class EntityClassToInstanciateFromName {
    /** @psalm-suppress PossiblyUnusedProperty */
    public ?string $someProp = null;
    // no setter for email
};

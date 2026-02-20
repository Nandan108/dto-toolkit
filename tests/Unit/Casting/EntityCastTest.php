<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\Attribute\ChainModifier as Mod;
use Nandan108\DtoToolkit\Attribute\DefaultOutboundEntity;
use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\PropAccess\PropAccess;
use PHPUnit\Framework\TestCase;

final class EntityCastTest extends TestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        PropAccess::bootDefaultResolvers();
    }

    public function testCastFromArraySetsEntityProperties(): void
    {
        $input = DtoCastTest::getStandardInput();

        $casterArgs = [UserEntityFromArray::class, [], true];
        $caster = new CastTo\Entity(...$casterArgs);
        $result = $caster->cast($input, $casterArgs);

        $this->assertInstanceOf(UserEntityFromArray::class, $result);
        $this->assertSame($input['id'], $result->id);
        $this->assertSame($input['username'], $result->username);
        $this->assertSame($input['public_profile'], $result->public_profile);
        $this->assertSame($input['address'], $result->address);
    }

    public function testCastFromDtoUsesEntityClassFromDto(): void
    {
        $dto = new // When multiple #[DefaultOutboundEntity] attributes are present, order is important.
        // 1st matching group will be used, so 'special' group must come first to take precedence.
        // No-group (default) must come last, as it matches all cases.
        #[DefaultOutboundEntity(SpecialNamedEntity::class, groups: 'special')]
        #[DefaultOutboundEntity(NamedEntity::class)]
        class extends FullDto {
            public string $name = '';
        };

        $dto->fill(['name' => 'Alice']);

        $caster = new CastTo\Entity(null);
        $result = $caster->cast(value: $dto, args: [null, [], false]);
        $this->assertInstanceOf(NamedEntity::class, $result);
        $this->assertSame('Alice', $result->name);

        $result = $caster->cast(value: $dto->withGroups('special'), args: [null, [], false]);
        $this->assertInstanceOf(SpecialNamedEntity::class, $result);
        $this->assertSame('Alice', $result->name);
    }

    // test sad path: invalid value (non-array, non-dto)
    public function testCastFromInvalidValueThrowsException(): void
    {
        $dto = new class extends FullDto {
            #[CastTo\Entity(UserEntityFromArray::class)]
            public ?string $name = null;
        };

        $this->expectException(\Nandan108\DtoToolkit\Exception\Process\TransformException::class);
        $this->expectExceptionMessage('Expected a DTO or an array, got a string');

        $dto->fill(['name' => 'A string is neither an array nor a DTO!'])
            ->processInbound();
    }
}

final class UserEntityFromArray
{
    public int $id = 0;
    public string $username = '';
    public EntityCastTest_PublicProfileDto | array $public_profile = [];
    public array $address = [];
}

class NamedEntity
{
    public string $name = '';
}

final class SpecialNamedEntity extends NamedEntity
{
}

final class EntityCastTest_PublicProfileDto extends FullDto
{
    public string $name = '';

    #[CastTo\DateTime]
    public \DateTimeInterface | string | null $birthdate = null;

    public string $bio = '';

    #[CastTo\Dto(EntityCastTest_InterestsDto::class)]
    public EntityCastTest_InterestsDto | array | null $interests = null;
}

final class EntityCastTest_InterestsDto extends FullDto
{
    #[CastTo\Split]
    #[Mod\PerItem, CastTo\Integer]
    public string | array $categories = [];

    #[CastTo\Split]
    #[Mod\PerItem, CastTo\Integer]
    public string | array $book = [];
}

final class EntityCastTest_PublicProfileEntity
{
    public string $name = '';
    public \DateTimeInterface | string | null $birthdate = null;
    public string $bio = '';
    public EntityCastTest_InterestsEntity | array | null $interests = null;
}

final class EntityCastTest_InterestsEntity
{
    public array $categories = [];
    public array $book = [];
}

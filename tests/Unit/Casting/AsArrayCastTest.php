<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Core\FullDto;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Exception\Process\TransformException;
use Nandan108\PropAccess\PropAccess;
use PHPUnit\Framework\TestCase;

final class AsArrayCastTest extends TestCase
{
    public function testCastFromDtoReturnsArray(): void
    {
        $dto = UserDto::newFromArray(DtoCastTest::getStandardInput());

        $caster = new CastTo\AsArray();
        $result = $caster->cast($dto, [[], false]);

        $this->assertIsArray($result);
        $this->assertSame(123, $result['id']);
        $this->assertSame('alice', $result['username']);
        $this->assertInstanceOf(PublicProfileDto::class, $result['public_profile']);
        $this->assertInstanceOf(AddressDto::class, $result['address']);
    }

    public function testCastFromDtoRecursiveConvertsNestedDtos(): void
    {
        $dto = UserDto::newFromArray(DtoCastTest::getStandardInput());

        $caster = new CastTo\AsArray([], true);
        $result = $caster->cast($dto, [[], true]);

        $this->assertIsArray($result['public_profile']);
        $this->assertIsArray($result['public_profile']['interests']);
        $this->assertIsArray($result['address']);
        $this->assertSame([1, 2, 3], $result['public_profile']['interests']['categories']);
        $this->assertSame('US', $result['address']['ctry_code']);
    }

    public function testCastIncludessupplementalProps(): void
    {
        $dto = UserDto::newFromArray(DtoCastTest::getStandardInput());

        $caster = new CastTo\AsArray(['role' => 'admin']);
        $result = $caster->cast($dto, [['role' => 'admin'], false]);

        $this->assertSame('admin', $result['role']);
    }

    public function testCastRejectsNonObjectInput(): void
    {
        $caster = new CastTo\AsArray();

        $this->expectException(TransformException::class);
        $this->expectExceptionMessage('Expected an object, got a string');
        $dto = new FullDto();
        $callback = fn (): mixed => $caster->cast('nope', [[], false]);
        ProcessingContext::wrapProcessing($dto, $callback);
    }

    // test casting from an entity object
    public function testCastFromEntityObjectReturnsArray(): void
    {
        PropAccess::bootDefaultResolvers();

        $entity = new AsArrayCastTest_Entity();
        $caster = new CastTo\AsArray();
        $result = $caster->cast($entity, [[], false]);

        $this->assertIsArray($result);
        $this->assertSame(123, $result['id']);
        $this->assertSame('Bob', $result['username']);
    }
}

/** @psalm-suppress PossiblyUnusedProperty */
final class AsArrayCastTest_Entity
{
    public int $id = 123;
    public ?string $username = 'Bob';
}

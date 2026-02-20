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

    public function testCastFromTraversableNonRecursiveKeepsNestedDtoAndSourceWinsOnConflicts(): void
    {
        $dto = UserDto::newFromArray(DtoCastTest::getStandardInput());
        $traversable = new AsArrayCastTest_Traversable([
            'id'      => 123,
            'profile' => $dto->public_profile,
            'role'    => 'user',
        ]);

        $caster = new CastTo\AsArray(['id' => 999, 'role' => 'admin']);
        $result = $caster->cast($traversable, [['id' => 999, 'role' => 'admin'], false]);

        $this->assertSame(123, $result['id']);
        $this->assertSame('user', $result['role']);
        $this->assertInstanceOf(PublicProfileDto::class, $result['profile']);
    }

    public function testCastFromTraversableRecursiveConvertsNestedDtos(): void
    {
        $dto = UserDto::newFromArray(DtoCastTest::getStandardInput());
        $traversable = new AsArrayCastTest_Traversable([
            'profile' => $dto->public_profile,
        ]);

        $caster = new CastTo\AsArray();
        $result = $caster->cast($traversable, [[], true]);

        $this->assertIsArray($result['profile']);
        $this->assertIsArray($result['profile']['interests']);
        $this->assertSame([1, 2, 3], $result['profile']['interests']['categories']);
    }
}

final class AsArrayCastTest_Entity
{
    public int $id = 123;
    public ?string $username = 'Bob';
}

/**
 * @implements \IteratorAggregate<mixed>
 */
final class AsArrayCastTest_Traversable implements \IteratorAggregate
{
    /** @param array<array-key, mixed> $items */
    public function __construct(
        private array $items,
    ) {
    }

    #[\Override]
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }
}

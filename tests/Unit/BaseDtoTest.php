<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\Core\BaseDto;
use PHPUnit\Framework\TestCase;

final class BaseDtoTest extends TestCase
{
    public function testCanAccessPublicStaticFunc(): void
    {
        try {
            /** @psalm-suppress InaccessibleMethod */
            BaseDtoTestDtoWithProtectedStaticFunc::protectedMakeSpecialObject(new \stdClass());
            $this->fail('Expected exception not thrown');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Exception::class, $e);
            $this->assertSame('Protected method '.BaseDtoTestDtoWithProtectedStaticFunc::class.'::protectedMakeSpecialObject() is not reachable from calling context.', $e->getMessage());
        }
    }

    public function testCannotAccessProtectedStaticFunc(): void
    {
        $dto = BaseDtoTestDtoWithProtectedStaticFunc::makeSpecialObject((object) ['foo' => 'bar']);
        $this->assertInstanceOf(BaseDtoTestDtoWithProtectedStaticFunc::class, $dto);
        $this->assertSame(['foo' => 'bar'], $dto->toArray());
    }

    public function testCanUseMagicFunc(): void
    {
        $dto = BaseDtoTestDtoWithProtectedStaticFunc::fromSpecialObject((object) ['foo' => 'bar']);
        $this->assertInstanceOf(BaseDtoTestDtoWithProtectedStaticFunc::class, $dto);
        $this->assertSame(['foo' => 'bar'], $dto->toArray());
    }

    public function testMagicFuncThrowsIfTargetMethodNotFound(): void
    {
        // test static call on non-existent from* method
        try {
            /** @psalm-suppress UndefinedMagicMethod */
            $dto = BaseDtoTestDtoWithProtectedStaticFunc::fromNonExistentMethod((object) ['foo' => 'bar']);
            $this->fail('Expected exception not thrown');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Exception::class, $e);
            $this->assertSame('Method '.BaseDtoTestDtoWithProtectedStaticFunc::class.'::_fromNonExistentMethod() does not exist.', $e->getMessage());
        }

        // test instance call on non-existent from* method
        try {
            $dto = new BaseDtoTestDtoWithProtectedStaticFunc();
            /** @psalm-suppress UndefinedMagicMethod */
            $dto->fromNonExistentMethod((object) ['foo' => 'bar']);
            $this->fail('Expected exception not thrown');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Exception::class, $e);
            $this->assertSame('Method '.BaseDtoTestDtoWithProtectedStaticFunc::class.'::_fromNonExistentMethod() does not exist.', $e->getMessage());
        }
    }

    public function testClearResetsPublicPropsToDefaults(): void
    {
        $dto = new BaseDtoClearDto();
        $dto->fill([
            'required' => 'value',
            'optional' => 42,
            'items'    => ['a'],
        ]);

        $result = $dto->clear();

        $this->assertSame($dto, $result);
        $this->assertSame([], $dto->_filled);

        $dtoDefaultValues = $dto->getDefaultValues()['defaults'];
        $this->assertSame('foo', $dtoDefaultValues['hasDefault']);

        $requiredProp = new \ReflectionProperty($dto, 'required');
        $this->assertFalse($requiredProp->isInitialized($dto));
        $this->assertNull($dto->optional);
        $this->assertSame([], $dto->items);
    }
}

/**
 * @method static static fromSpecialObject(object $specialObj)
 */
final class BaseDtoTestDtoWithProtectedStaticFunc extends BaseDto
{
    // not accessible directly -- caught by __callStatic(), which throws
    protected static function protectedMakeSpecialObject(object $specialObj): static
    {
        /** @psalm-suppress UnsafeInstantiation */
        $dto = new static();

        $vars = get_object_vars($specialObj);
        $dto->fill($vars);

        return $dto;
    }

    // accessible directly
    public static function makeSpecialObject(object $specialObj): static
    {
        return static::protectedMakeSpecialObject($specialObj);
    }

    // accessible via __callStatic()
    /** @psalm-suppress PossiblyUnusedMethod */
    public function _fromSpecialObject(object $specialObj): static
    {
        return static::protectedMakeSpecialObject($specialObj);
    }
}

final class BaseDtoClearDto extends BaseDto
{
    /** @psalm-suppress PossiblyUnusedProperty */
    public static string $someStaticProp = 'staticValue';

    /** @psalm-suppress PossiblyUnusedProperty */
    public ?string $required;

    /** @psalm-suppress PossiblyUnusedProperty */
    public string $hasDefault = 'foo';

    public ?int $optional = null;

    /** @var string[] */
    public array $items = [];

    public function __construct()
    {
        $this->required = null;
    }
}

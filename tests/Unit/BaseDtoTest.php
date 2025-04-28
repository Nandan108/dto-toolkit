<?php

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
            $this->assertSame('Cannot access non-public static method '.BaseDtoTestDtoWithProtectedStaticFunc::class.'::protectedMakeSpecialObject()', $e->getMessage());
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
            $this->assertSame('Method fromNonExistentMethod() does not exist on '.BaseDtoTestDtoWithProtectedStaticFunc::class.'.', $e->getMessage());
        }

        // test instance call on non-existent from* method
        try {
            $dto = new BaseDtoTestDtoWithProtectedStaticFunc();
            /** @psalm-suppress UndefinedMagicMethod */
            $dto->fromNonExistentMethod((object) ['foo' => 'bar']);
            $this->fail('Expected exception not thrown');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Exception::class, $e);
            $this->assertSame('Method fromNonExistentMethod() does not exist on '.BaseDtoTestDtoWithProtectedStaticFunc::class.'.', $e->getMessage());
        }
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

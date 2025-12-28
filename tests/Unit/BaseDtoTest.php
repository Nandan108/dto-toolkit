<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use PHPUnit\Framework\TestCase;

final class BaseDtoTest extends TestCase
{
    public function testCanAccessPublicStaticFunc(): void
    {
        try {
            $dto = BaseDtoTestDtoWithProtectedStaticFunc::new();
            /** @psalm-suppress InaccessibleMethod */
            BaseDtoTestDtoWithProtectedStaticFunc::protectedMakeSpecialObject($dto, new \stdClass());
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
        $dto = BaseDtoTestDtoWithProtectedStaticFunc::newFromSpecialObject((object) ['foo' => 'bar']);
        $this->assertInstanceOf(BaseDtoTestDtoWithProtectedStaticFunc::class, $dto);
        $this->assertSame(['foo' => 'bar'], $dto->toArray());
    }

    public function testMagicFuncThrowsIfTargetMethodNotFound(): void
    {
        // test static call on non-existent from* method
        try {
            /** @psalm-suppress UndefinedMagicMethod */
            BaseDtoTestDtoWithProtectedStaticFunc::newFromNonExistentMethod((object) ['foo' => 'bar']);
            $this->fail('Expected exception not thrown');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Exception::class, $e);
            $this->assertSame('Method '.BaseDtoTestDtoWithProtectedStaticFunc::class.'::loadNonExistentMethod() does not exist.', $e->getMessage());
        }
    }

    public function testClearResetsPublicPropsToDefaults(): void
    {
        // --- Arrange
        $dto = new BaseDtoClearDto();
        $dtoDefaultValues = $dto->getDefaultValues();
        $input = [
            'required' => 'value',
            'optional' => 42,
            'items'    => ['a'],
        ];
        $inputKeys = array_keys($input);

        // --- Act - fill the DTO
        $result = $dto->fill($input);

        // --- Assert
        // fill() returns $this
        $this->assertSame($dto, $result);
        // verify prop's filled state
        $this->assertSame($input, $dto->toArray($inputKeys));
        // verify _filled tracking
        $this->assertSame(array_fill_keys($inputKeys, true), $dto->_filled);

        // --- Act - clear one prop
        $dto->clear(['required']);

        // --- Assert
        // This prop was cleared
        $this->assertSame($dtoDefaultValues['required'], $dto->required);
        // But other props remain unchanged
        $this->assertSame($input['items'], $dto->items);
        $this->assertSame($input['optional'], $dto->optional);

        // --- Act - clear all props
        $result = $dto->clear();

        // --- Assert
        // clear() returns $this
        $this->assertSame($dto, $result);
        // after clearing, _filled should be empty,
        $this->assertSame([], $dto->_filled);
        // and all public props reset to default values
        foreach (get_object_vars($dto) as $propName => $value) {
            // keys starting with "_" are considered internal and excluded from DTO state and operations
            if ('_' === $propName[0]) {
                continue;
            }
            $this->assertSame($dtoDefaultValues[$propName], $value);
        }
    }

    public function testInvalidBaseDtoWithPropMissingDefaultValueThrows(): void
    {
        $this->expectException(InvalidConfigException::class);
        $class = InvalidBaseDtoWithPropMissingDefaultValue::class;
        $propName = 'propWithoutDefaultValue';
        $this->expectExceptionMessage("Default value missing on DTO property: {$class}::\${$propName}.");
        InvalidBaseDtoWithPropMissingDefaultValue::new()->getDefaultValues();
    }
}

/**
 * @method static static newFromSpecialObject(object $specialObj)
 */
final class BaseDtoTestDtoWithProtectedStaticFunc extends BaseDto
{
    // not accessible directly -- caught by __callStatic(), which throws
    protected static function protectedMakeSpecialObject(?self $dto, object $specialObj): static
    {
        /** @psalm-suppress UnsafeInstantiation */
        $dto ??= new static();

        $vars = get_object_vars($specialObj);
        $dto->fill($vars);

        return $dto;
    }

    // accessible directly
    public static function makeSpecialObject(object $specialObj): static
    {
        return static::protectedMakeSpecialObject(null, $specialObj);
    }

    // accessible via __callStatic()
    /** @psalm-suppress PossiblyUnusedMethod */
    public function loadSpecialObject(object $specialObj): static
    {
        return static::protectedMakeSpecialObject($this, $specialObj);
    }
}

final class BaseDtoClearDto extends BaseDto
{
    /** @psalm-suppress PossiblyUnusedProperty */
    public static string $someStaticProp = 'staticValue';

    /** @psalm-suppress PossiblyUnusedProperty */
    public ?string $required = null;

    /** @psalm-suppress PossiblyUnusedProperty */
    public string $hasDefault = 'foo';

    public ?int $optional = null;

    /** @var string[] */
    public array $items = [];
}

final class InvalidBaseDtoWithPropMissingDefaultValue extends BaseDto
{
    /** @psalm-suppress PossiblyUnusedProperty */
    public string $propWithoutDefaultValue;

    /** @psalm-suppress PossiblyUnusedMethod */
    public function __construct()
    {
        $this->propWithoutDefaultValue = 'value';
    }
}

<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Tests\Unit\Casting;

use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use PHPUnit\Framework\TestCase;

final class BaseDtoTest extends TestCase
{
    public function testCanAccessPublicStaticFunc(): void
    {
        try {
            $dto = BaseDtoTestDtoWithUnaccessibleStaticFuncs::new();
            /** @psalm-suppress InaccessibleMethod */
            BaseDtoTestDtoWithUnaccessibleStaticFuncs::protectedMakeSpecialObject($dto, new \stdClass());
            $this->fail('Expected exception not thrown');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Exception::class, $e);
            $this->assertSame('Protected method '.BaseDtoTestDtoWithUnaccessibleStaticFuncs::class.'::protectedMakeSpecialObject() is not reachable from calling context.', $e->getMessage());
        }

        try {
            /** @psalm-suppress InaccessibleMethod */
            BaseDtoTestDtoWithUnaccessibleStaticFuncs::somePrivateFunc();
            $this->fail('Expected exception still not thrown');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Exception::class, $e);
            $this->assertSame('Private method '.BaseDtoTestDtoWithUnaccessibleStaticFuncs::class.'::somePrivateFunc() is not reachable from calling context.', $e->getMessage());
        }
    }

    public function testCannotAccessProtectedStaticFunc(): void
    {
        $dto = BaseDtoTestDtoWithUnaccessibleStaticFuncs::makeSpecialObject((object) ['foo' => 'bar']);
        $this->assertInstanceOf(BaseDtoTestDtoWithUnaccessibleStaticFuncs::class, $dto);
        $this->assertSame(['foo' => 'bar'], $dto->toArray());
    }

    public function testCanUseMagicFunc(): void
    {
        $dto = BaseDtoTestDtoWithUnaccessibleStaticFuncs::newFromSpecialObject((object) ['foo' => 'bar']);
        $this->assertInstanceOf(BaseDtoTestDtoWithUnaccessibleStaticFuncs::class, $dto);
        $this->assertSame(['foo' => 'bar'], $dto->toArray());
    }

    public function testMagicFuncThrowsIfTargetMethodNotFound(): void
    {
        // test static call on non-existent from* method
        try {
            /** @psalm-suppress UndefinedMagicMethod */
            BaseDtoTestDtoWithUnaccessibleStaticFuncs::newFromNonExistentMethod((object) ['foo' => 'bar']);
            $this->fail('Expected exception not thrown');
        } catch (\Exception $e) {
            $this->assertInstanceOf(\Exception::class, $e);
            $this->assertSame('Method '.BaseDtoTestDtoWithUnaccessibleStaticFuncs::class.'::loadNonExistentMethod() does not exist.', $e->getMessage());
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

    public function testIsInstanceOfValidatorErrorMessageClassParameter(): void
    {
        $file = basename(__FILE__);
        $line = __LINE__ + 1;
        $dto = new class extends BaseDto {
            public mixed $testCase = null;
        };

        // In dev mode, the node name should include the file and line number for anonymous DTOs
        $this->assertSame("AnonymousDTO($file:$line)", $dto->getProcessingNodeName());

        // In production mode, the node name should be the default 'DTO'
        ProcessingContext::setDevMode(false);
        $this->assertSame('DTO', $dto->getProcessingNodeName());

        // Reset dev mode for other tests
        ProcessingContext::setDevMode(true);
    }

    public function testDtoWithRequiredConstructorArgsThrowsOnClassRefLoad(): void
    {
        $this->expectException(InvalidConfigException::class);
        $this->expectExceptionMessage('constructor with required parameters');
        BaseDtoWithRequiredConstructorArg::new();
    }
}

/**
 * @method static static newFromSpecialObject(object $specialObj)
 */
final class BaseDtoTestDtoWithUnaccessibleStaticFuncs extends BaseDto
{
    // not accessible directly -- caught by __callStatic(), which throws
    protected static function protectedMakeSpecialObject(?self $dto, object $specialObj): static
    {
        /** @psalm-suppress UnsafeInstantiation */
        $dto ??= new static();

        /** @var array<truthy-string, mixed> $vars */
        $vars = get_object_vars($specialObj);
        $dto->fill($vars);

        return $dto;
    }

    private static function somePrivateFunc(): void
    {
        // This function is intentionally left blank.
        // It's only here to test that private static methods are not accessible.
    }

    // accessible directly
    public static function makeSpecialObject(object $specialObj): static
    {
        return static::protectedMakeSpecialObject(null, $specialObj);
    }

    // accessible via __callStatic()
    public function loadSpecialObject(object $specialObj): static
    {
        return static::protectedMakeSpecialObject($this, $specialObj);
    }
}

final class BaseDtoClearDto extends BaseDto
{
    public static string $someStaticProp = 'staticValue';

    public ?string $required = null;

    public string $hasDefault = 'foo';

    public ?int $optional = null;

    /** @var string[] */
    public array $items = [];
}

final class InvalidBaseDtoWithPropMissingDefaultValue extends BaseDto
{
    public string $propWithoutDefaultValue;

    public function __construct()
    {
        $this->propWithoutDefaultValue = 'value';
    }
}

final class BaseDtoWithRequiredConstructorArg extends BaseDto
{
    public string $name = '';

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}

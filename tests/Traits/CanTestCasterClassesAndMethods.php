<?php

namespace Nandan108\DtoToolkit\Tests\Traits;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\BootsOnDtoInterface;
use Nandan108\DtoToolkit\Contracts\NormalizesInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Traits\NormalizesFromAttributes;

trait CanTestCasterClassesAndMethods
{
    /**
     * @param mixed       $method           the method to test, can be a string or CastTo instance
     * @param mixed       $input            the input value to be casted
     * @param mixed       $expected         the expected result after casting
     * @param array       $args             additional arguments for the caster
     * @param string|null $exceptionMessage expected exception message if an exception is thrown
     */
    public function casterTest(mixed $method, mixed $input, mixed $expected, array $args = [], ?string $exceptionMessage = null): void
    {
        // /** @psalm-suppress ExtensionRequirementViolation */
        $dto = new class extends BaseDto implements NormalizesInterface {
            use NormalizesFromAttributes;
        };

        // create caster Attribute using static helper method, and from it get the caster Closure
        if (is_string($method)) {
            $casterAttribute = new CastTo($method, args: $args);
            $caster = $casterAttribute->getCaster($dto);
        } elseif ($method instanceof CastTo) {
            $caster = $method->getCaster($dto);
        } else {
            $this->fail('Invalid method type: '.gettype($method));
        }

        CastTo::setCurrentPropName('test');
        CastTo::setCurrentDto($dto);

        if ($caster->instance instanceof BootsOnDtoInterface) {
            $caster->instance->bootOnDto();
        }

        // Call the caster closure with the input value and get the result
        try {
            $result = $caster($input);

            if (is_object($expected)) {
                $this->assertInstanceOf(get_class($expected), $result);
                $this->assertEquals($expected, $result); // compares datetime value
            } else {
                $this->assertSame($expected, $result);
            }
        } catch (\Exception $e) {
            if (is_string($expected) && class_exists($expected) && is_a($e, $expected)) {
                $this->assertInstanceOf($expected, $e);
                if (null !== $exceptionMessage && $exceptionMessage > '') {
                    $this->assertStringContainsString($exceptionMessage, $e->getMessage());
                }
            } else {
                throw $e;
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

/**
 * Validates that a value is an instance of the given class or interface.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class IsInstanceOf extends ValidatorBase
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     *
     * @param class-string $className
     **/
    public function __construct(string $className)
    {
        /** @psalm-suppress DocblockTypeContradiction */
        if ('' === $className) {
            throw new InvalidArgumentException('IsInstanceOf validator requires a class or interface name.');
        }
        if (!class_exists($className) && !interface_exists($className)) {
            throw new InvalidArgumentException("Class or interface '$className' does not exist.");
        }

        $shortClassName = ProcessingContext::isDevMode()
            // in development, use full class name for error message
            ? $className
            // in production, use short class name to avoid leaking namespaces
            : (new \ReflectionClass($className))->getShortName();

        parent::__construct([$className, $shortClassName]);
    }

    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        [$className, $shortClassName] = $args;

        if (!is_object($value) || !$value instanceof $className) {
            throw GuardException::expected(
                operand: $value,
                expected: 'type.instance_of_class',
                templateSuffix: 'instance_of',
                parameters: ['class' => $shortClassName],
            );
        }
    }
}

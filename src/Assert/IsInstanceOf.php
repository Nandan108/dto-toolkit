<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
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
            throw new InvalidConfigException('IsInstanceOf validator requires a class or interface name.');
        }
        parent::__construct([$className]);
    }

    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        $className = $args[0];

        if (!is_object($value) || !$value instanceof $className) {
            throw GuardException::expected(
                methodOrClass: static::class,
                operand: $value,
                expected: 'instance_of_class',
                templateSuffix: 'not_instance_of',
                parameters: ['class' => $className],
            );
        }
    }
}

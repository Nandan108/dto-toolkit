<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit;

use Nandan108\DtoToolkit\Contracts\ValidatorInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Internal\ProcessingNodeBase;

/**
 * Base class for all validator attributes.
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class Validate extends ProcessingNodeBase
{
    protected static string $methodPrefix = 'validate';

    public ?string $methodOrClass = null;

    /** @psalm-suppress PossiblyUnusedProperty */
    public array $args = [];

    public ?array $constructorArgs = null;

    public function __construct(?string $methodOrClass = null, array $args = [], ?array $constructorArgs = null)
    {
        parent::__construct($methodOrClass, $args, $constructorArgs);
    }

    #[\Override]
    protected function getInterface(): string
    {
        return ValidatorInterface::class;
    }

    #[\Override]
    protected function makeClosureFromInstance(object $instance, array $args): \Closure
    {
        /** @var ValidatorInterface $instance */
        return function (mixed $value) use ($instance, $args): mixed {
            $instance->validate($value, $args);

            return $value;
        };
    }

    #[\Override]
    protected function makeClosureFromDtoMethod(BaseDto $dto, string $method, array $args): \Closure
    {
        return function (mixed $value) use ($dto, $method, $args): mixed {
            $dto->{$method}($value, ...$args);

            return $value;
        };
    }

    #[\Override]
    protected function interfaceError(string $class): \Throwable
    {
        return new InvalidConfigException("Class '{$class}' does not implement the ValidatorInterface.");
    }

    #[\Override]
    public function resolveWithContainer(string $className): object
    {
        throw new InvalidConfigException("Validator {$className} requires constructor args, but none were provided and no container is available.");
    }
}

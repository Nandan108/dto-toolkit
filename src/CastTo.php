<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit;

use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Exception\Process\TransformException;
use Nandan108\DtoToolkit\Internal\ProcessingNodeBase;

/**
 * Base class for all caster attributes.
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class CastTo extends ProcessingNodeBase
{
    protected static string $methodPrefix = 'castTo';

    /**
     * @param string|class-string|null $methodOrClass
     *
     * @psalm-suppress PossiblyUnusedProperty
     */
    public function __construct(
        public ?string $methodOrClass = null,
        public array $args = [],
        public ?array $constructorArgs = null,
    ) {
        parent::__construct($methodOrClass, $args, $constructorArgs);
    }

    #[\Override]
    protected function getInterface(): string
    {
        return CasterInterface::class;
    }

    #[\Override]
    protected function makeClosureFromInstance(object $instance, array $args): \Closure
    {
        /** @var CasterInterface $instance */
        return fn (mixed $value): mixed => $instance->cast($value, $args);
    }

    #[\Override]
    protected function makeClosureFromDtoMethod(BaseDto $dto, string $method, array $args): \Closure
    {
        return fn (mixed $value): mixed => $dto->{$method}($value, ...$args);
    }

    #[\Override]
    protected function interfaceError(string $class): \Throwable
    {
        return TransformException::invalidInterface($class);
    }

    #[\Override]
    public function resolveWithContainer(string $className): object
    {
        throw new InvalidConfigException("Caster {$className} requires constructor args, but none were provided and no container is available.");
    }
}

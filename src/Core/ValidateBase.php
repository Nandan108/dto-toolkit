<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Core;

use Nandan108\DtoToolkit\Contracts\Injectable;
use Nandan108\DtoToolkit\Contracts\ValidatorInterface;
use Nandan108\DtoToolkit\Exception\Process\GuardException;
use Nandan108\DtoToolkit\Exception\Process\ProcessingException;
use Nandan108\DtoToolkit\Traits\IsInjectable;
use Nandan108\DtoToolkit\Validate;

/**
 * Base class for validator attributes.
 */
abstract class ValidateBase extends Validate implements ValidatorInterface, Injectable
{
    use IsInjectable;

    /** @var class-string<ProcessingException> */
    protected string $exceptionType = GuardException::class;

    /**
     * @param array      $args            attribute args forwarded to validate()
     * @param array|null $constructorArgs constructor arguments if resolved as class name
     */
    public function __construct(array $args = [], ?array $constructorArgs = null)
    {
        parent::__construct(
            methodOrClass: static::class,
            args: $args,
            constructorArgs: $constructorArgs,
        );
    }

    /**
     * Convenience helper to throw a validation exception.
     *
     * @param non-empty-string $reason
     */
    protected function fail(string $reason): never
    {
        throw GuardException::failed($reason);
    }

    #[\Override]
    abstract public function validate(mixed $value, array $args = []): void;
}

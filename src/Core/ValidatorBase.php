<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Core;

use Nandan108\DtoToolkit\Assert;
use Nandan108\DtoToolkit\Contracts\Injectable;
use Nandan108\DtoToolkit\Contracts\ProvidesProcessingNodeNameInterface;
use Nandan108\DtoToolkit\Contracts\ValidatorInterface;
use Nandan108\DtoToolkit\Exception\Process\GuardException;
use Nandan108\DtoToolkit\Exception\Process\ProcessingException;
use Nandan108\DtoToolkit\Internal\ProcessingNodeBase;
use Nandan108\DtoToolkit\Traits\IsInjectable;

/**
 * Base class for validator attributes.
 *
 * @api
 */
abstract class ValidatorBase extends Assert implements ValidatorInterface, Injectable, ProvidesProcessingNodeNameInterface
{
    use IsInjectable;

    /** @var class-string<ProcessingException> */
    protected string $exceptionType = GuardException::class;

    /** @var ?truthy-string */
    protected static ?string $nodeName = null;

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

    #[\Override]
    protected function ensureStringable(
        mixed $value,
        bool $expectNonEmpty = false,
    ): string {
        return parent::ensureStringable($value, $expectNonEmpty);
    }

    #[\Override]
    public function getProcessingNodeName(): string
    {
        return static::$nodeName ?: ProcessingNodeBase::getNodeNameFromClass(static::class);
    }

    #[\Override]
    abstract public function validate(mixed $value, array $args = []): void;
}

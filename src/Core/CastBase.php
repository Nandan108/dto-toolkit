<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Core;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Contracts\Injectable;
use Nandan108\DtoToolkit\Exception\Process\ProcessingException;
use Nandan108\DtoToolkit\Exception\Process\TransformException;
use Nandan108\DtoToolkit\Traits\IsInjectable;

/**
 * Base class for all Caster classes.
 */
abstract class CastBase extends CastTo implements CasterInterface, Injectable
{
    use IsInjectable;

    /** @var class-string<ProcessingException> */
    protected string $exceptionType = TransformException::class;

    /**
     * Constructs an instance of a Caster class.
     *
     * @param array $args should contain any argument values used to parameterize caster behavior
     *
     * @psalm-suppress PossiblyUnusedParam
     */
    public function __construct(array $args = [], array $constructorArgs = [])
    {
        parent::__construct(
            methodOrClass: static::class,
            args: $args,
            constructorArgs: $constructorArgs,
        );
    }

    /**
     * Transform $value according to caster logic and arguments, and return it.
     *
     * @param mixed[] $args passed from Attribute constructor
     */
    #[\Override]
    abstract public function cast(mixed $value, array $args): mixed;
}

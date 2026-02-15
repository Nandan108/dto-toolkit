<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Exception\Config;

/**
 * Exception thrown when a required dependency is missing for a caster/validator class.
 *
 * @api
 */
final class MissingDependencyException extends ConfigException
{
    public function __construct(
        string $extensionName,
        /** @var class-string */
        protected string $className,
    ) {
        $classShortName = (new \ReflectionClass($this->className))->getShortName();

        parent::__construct(
            "The PHP extension '$extensionName' is required to use the caster or validator class '$classShortName'.",
        );
    }
}

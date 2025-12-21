<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Exception\Config;

final class MissingDependencyException extends ConfigException
{
    public function __construct(
        string $extensionName,
        /** @var class-string */
        protected string $className,
    ) {
        $classShortName = (new \ReflectionClass($this->className))->getShortName();

        parent::__construct(
            "The PHP extension '$extensionName' is required to use the caster class '$classShortName'.",
        );
    }
}

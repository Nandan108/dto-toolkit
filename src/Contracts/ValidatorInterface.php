<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Contracts;

/** @api */
interface ValidatorInterface
{
    /**
     * Validate a value. Must throw on failure; returns void on success.
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function validate(mixed $value, array $args = []): void;
}

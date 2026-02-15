<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Contracts;

/**
 * This interface is used to check if a DTO is valid.
 *
 * @api
 */
interface BootsOnDtoInterface
{
    /**
     * Prepare caster as needed, and verify that DTO is valid.
     * This method will be called once per Caster-instance/DTO,
     * immediately after chain building, only for casters implementing this interface.
     *
     * @throws \InvalidArgumentException if the DTO is not valid
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function bootOnDto(): void;
}

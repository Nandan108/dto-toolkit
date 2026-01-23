<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Contracts;

interface PreparesEntityInterface
{
    /**
     * Prepare and return an outbound entity instance for this DTO.
     *
     * @param array<string, mixed> $outboundProps the properties to be hydrated on the new entity
     *                                            If the entity requires certain properties to be set via constructor,
     *                                            they can be extracted from here
     *
     * @return array{entity: object, hydrated: bool} if the second element is false, Exporter will attempt hydrating the
     *                                               properties on the entity automatically after receiving the entity instance
     *
     * @throws \Nandan108\DtoToolkit\Exception\Config\InvalidConfigException
     */
    public function prepareEntity(array $outboundProps): array;
}

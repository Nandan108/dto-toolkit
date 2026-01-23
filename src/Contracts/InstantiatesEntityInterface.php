<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Contracts;

interface InstantiatesEntityInterface
{
    /**
     * Get the default outbound entity class for this DTO, based on the DefaultOutboundEntity attributes.
     *
     * @param array<string, mixed> $propsToSet the properties to be hydrated on the new entity
     *                                         If the entity requires certain properties to be set via constructor, they can be extracted from here
     *
     * @return array{0: object, 1: bool} 0 => new entity instance, 1 => whether the properties are already loaded
     *                                   If the second element is true, the Exporter will skip setting properties on the entity
     *
     * @throws \Nandan108\DtoToolkit\Exception\Config\InvalidConfigException
     */
    public function newEntityInstance(array $propsToSet): array;
}

<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Attribute;

/**
 * This attribute is used to specify the default entity class to map to when exporting.
 *
 * Can be repeated to provide different entity classes for different scoping groups.
 * In case of multiple matches, the first matching one will be used.
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class DefaultOutboundEntity
{
    /**
     * @param class-string        $entityClass
     * @param string|list<string> $groups
     */
    public function __construct(
        public string $entityClass,
        public string | array $groups = [],
    ) {
        if (is_string($this->groups)) {
            $this->groups = [$this->groups];
        }
    }
}

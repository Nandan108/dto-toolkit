<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Attribute;

use Nandan108\DtoToolkit\Contracts\HasGroupsInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;

/**
 * This attribute is used to specify the scoping groups for a property.
 * If it is positioned after a #[Outbound] attribute, the groups will be set for the outbound phase.
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class WithDefaultGroups
{
    public function __construct(
        public array | string $all = [], // applies to all phases
        public array | string $inbound = [],
        public array | string $inboundCast = [],
        public array | string $outbound = [],
        public array | string $outboundCast = [],
    ) {
    }

    public function applyToDto(BaseDto $dto): void
    {
        if (!($dto instanceof HasGroupsInterface)) {
            throw new InvalidConfigException('The WithDefaultGroups attribute can only be used on DTOs that implement the HasGroupsInterface.');
        }
        $dto->withGroups(
            all: $this->all,
            inbound: $this->inbound,
            inboundCast: $this->inboundCast,
            outbound: $this->outbound,
            outboundCast: $this->outboundCast,
        );
    }
}

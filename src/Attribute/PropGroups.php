<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Attribute;

use Nandan108\DtoToolkit\Contracts\PhaseAwareInterface;
use Nandan108\DtoToolkit\Traits\HasPhase;

/**
 * This attribute is used to specify the scoping groups for a property.
 * If it is positioned after a #[Outbound] attribute, the groups will be set for the outbound phase.
 *
 * @api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class PropGroups implements PhaseAwareInterface
{
    use HasPhase;

    public function __construct(
        public array | string $groups,
    ) {
        $this->isIoBound = true;
    }
}

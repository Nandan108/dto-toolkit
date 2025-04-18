<?php

namespace Nandan108\DtoToolkit\Core;

use Nandan108\DtoToolkit\Traits;
use Nandan108\DtoToolkit\Contracts\NormalizesInboundInterface;
use Nandan108\DtoToolkit\Contracts\NormalizesOutboundInterface;

/** @psalm-api */
class FullDto extends BaseDto implements NormalizesInboundInterface, NormalizesOutboundInterface
{
    use Traits\CreatesFromArray;
    use Traits\NormalizesFromAttributes;
    use Traits\ExportsToEntity;
}

<?php

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\CastTo;

/** @psalm-require-extends \Nandan108\DtoToolkit\Core\BaseDto */
trait NormalizesFromAttributes // implements NormalizesInterface
{
    // will be used if using class implements NormalizesInboundInterface
    #[\Override]
    public function normalizeInbound(): void
    {
        $casters = CastTo::getCastingClosureMap(dto: $this, outbound: false);

        foreach ($casters as $prop => $method) {
            if (!empty($this->_filled[$prop])) {
                CastTo::setCurrentCastingContext($prop, $this);
                $this->$prop = $method($this->$prop);
            }
        }
        CastTo::setCurrentCastingContext(null, null);
    }

    // will be used if using class implements NormalizesOutboundInterface
    #[\Override]
    public function normalizeOutbound(array $props): array
    {
        $casters = CastTo::getCastingClosureMap(dto: $this, outbound: true);
        $normalized = [];

        foreach ($props as $prop => $value) {
            CastTo::setCurrentCastingContext($prop, $this);
            if (isset($casters[$prop])) {
                $normalized[$prop] = $casters[$prop]($value);
            } else {
                $normalized[$prop] = $value;
            }
            CastTo::setCurrentCastingContext(null, null);
        }

        return $normalized;
    }
}

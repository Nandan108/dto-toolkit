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
                CastTo::setCurrentPropName($prop);
                $this->$prop = $method($this->$prop);
            }
        }

        CastTo::setCurrentPropName(null);
    }

    // will be used if using class implements NormalizesOutboundInterface
    #[\Override]
    public function normalizeOutbound(array $props): array
    {
        $casters = CastTo::getCastingClosureMap(dto: $this, outbound: true);

        $normalized = [];
        foreach ($props as $prop => $value) {
            if (isset($casters[$prop])) {
                CastTo::setCurrentPropName($prop);
                $normalized[$prop] = $casters[$prop]($value);
            } else {
                $normalized[$prop] = $value;
            }

            CastTo::setCurrentPropName(null);
        }

        return $normalized;
    }
}

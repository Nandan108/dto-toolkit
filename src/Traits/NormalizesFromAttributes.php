<?php

namespace Nandan108\DtoToolkit\Traits;

use LogicException;
use Nandan108\DtoToolkit\CastTo as CastTo;

/** @psalm-require-extends \Nandan108\DtoToolkit\Core\BaseDto */
trait NormalizesFromAttributes
{
    public function normalizeInbound(): void
    {
        $casters = CastTo::getCastingClosureMap($this, outbound: false);

        foreach ($casters as $prop => $method) {
            if (!empty($this->_filled[$prop])) {
                $this->$prop = $method($this->$prop);
            }
        }
    }

    public function normalizeOutbound(array $props): array
    {
        $casters    = CastTo::getCastingClosureMap($this, outbound: true);
        $normalized = [];

        foreach ($props as $prop => $value) {
            if (isset($casters[$prop])) {
                $normalized[$prop] = $casters[$prop]($value);
            } else {
                $normalized[$prop] = $value;
            }
        }

        return $normalized;
    }
}

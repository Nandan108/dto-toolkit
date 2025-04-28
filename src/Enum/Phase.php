<?php

namespace Nandan108\DtoToolkit\Enum;

enum Phase: string
{
    case InboundLoad = 'inbound.io';      // loading raw data
    case InboundCast = 'inbound.cast';       // casting to types
    case OutboundCast = 'outbound.cast';     // casting during export
    case OutboundExport = 'outbound.io'; // final field export

    public static function fromComponents(bool $isOutbound, bool $isIOBound): self
    {
        $phase = $isOutbound ? 'outbound' : 'inbound';
        $subPhase = $isIOBound ? 'io' : 'cast';

        return static::from("$phase.$subPhase");
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function isOutbound(): bool
    {
        return $this->value === self::OutboundCast->value || $this->value === self::OutboundExport->value;
    }

    /** @psalm-suppress PossiblyUnusedMethod */
    public function isIOBound(): bool
    {
        return $this->value === self::InboundLoad->value || $this->value === self::OutboundExport->value;
    }
}

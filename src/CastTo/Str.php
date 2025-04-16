<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Contracts\CasterInterface;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Str extends CastBase implements CasterInterface
{
    public function __construct(bool $nullable = false, bool $outbound = false)
    {
        parent::__construct($outbound, [$nullable]);
    }


    #[\Override]
    public function cast(mixed $value, array $args = []): ?string
    {
        [$nullable] = $args;

        $stringable = is_string($value) && $value !== '' ||
            is_numeric($value) ||
            is_object($value) && method_exists($value, '__toString');

        return $stringable
            ? (string)$value
            : ($nullable ? null : '');
    }
}

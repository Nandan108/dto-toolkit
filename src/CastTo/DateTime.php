<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Exception\CastingException;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class DateTime extends CastBase
{
    public function __construct(string $format = 'Y-m-d H:i:s', bool $outbound = false)
    {
        parent::__construct($outbound, [$format]);
    }

    #[\Override]
    public function cast(mixed $value, array $args = []): \DateTimeImmutable
    {
        [$format] = $args;

        $value = $this->throwIfNotStringable($value, 'string or Stringable');

        $result = \DateTimeImmutable::createFromFormat($format, $value);

        if (!$result) {
            throw CastingException::castingFailure(
                className: $this::class,
                operand: $value,
                messageOverride: "Unable to parse date with format '{$format}' from '$value'",
            );
        }

        return $result;
    }
}

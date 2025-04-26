<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\CastingException;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Join extends CastBase
{
    public function __construct(string $separator = ',')
    {
        parent::__construct([$separator]);
    }

    #[\Override]
    public function cast(mixed $value, array $args, BaseDto $dto): ?string
    {
        [$separator] = $args;

        if (!is_array($value)) {
            throw CastingException::castingFailure(className: self::class, operand: $value, messageOverride: 'Expected array, but got '.gettype($value));
        }

        return implode($separator, $value);
    }
}

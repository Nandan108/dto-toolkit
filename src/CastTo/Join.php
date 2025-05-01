<?php

namespace Nandan108\DtoToolkit\CastTo;

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
    public function cast(mixed $value, array $args): ?string
    {
        [$separator] = $args;

        if (!is_array($value)) {
            throw CastingException::castingFailure(className: self::class, operand: $value, messageOverride: 'Expected array, but got '.gettype($value));
        }

        $value = array_map(
            fn ($item) => $this->throwIfNotStringable($item),
            $value
        );

        return implode($separator, $value);
    }
}

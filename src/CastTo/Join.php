<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\Process\TransformException;

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
        /** @var string $separator */
        if (!is_array($value)) {
            throw TransformException::expected(static::class, $value, 'array');
        }

        $value = array_map(
            fn ($value) => $this->ensureStringable($value),
            $value,
        );

        return implode($separator, $value);
    }
}

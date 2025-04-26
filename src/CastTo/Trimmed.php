<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\CastBase;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Trimmed extends CastBase
{
    public function __construct(
        string $characters = " \n\r\t\v\x00",
        string $where = 'both',
    ) {
        parent::__construct([$characters, $where]);
    }

    #[\Override]
    public function cast(mixed $value, array $args, BaseDto $dto): string
    {
        [$characters, $where] = $args;

        $v = $this->throwIfNotStringable($value);

        return match ($where) {
            'left'  => ltrim($v, $characters),
            'right' => rtrim($v, $characters),
            default => trim($v, $characters),
        };
    }
}

<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\CastingException;

/** @psalm-api */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class NumericString extends CastBase implements CasterInterface
{
    public function __construct(
        public readonly int $decimals = 0,
        public readonly string $decimalPoint = '.',
        public readonly string $thousandsSeparator = '',
    ) {
        parent::__construct([$decimals, $decimalPoint, $thousandsSeparator]);
    }

    #[\Override]
    public function cast(mixed $value, array $args, BaseDto $dto): string
    {
        [$decimals, $decimalPoint, $thousandsSeparator] = $args;

        if (!is_numeric($value)) {
            throw CastingException::castingFailure(className: static::class, operand: $value, messageOverride: 'Value is not numeric.');
        }

        return number_format((float) $value, $decimals, $decimalPoint, $thousandsSeparator);
    }
}

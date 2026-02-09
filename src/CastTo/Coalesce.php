<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\Process\TransformException;

/**
 * Returns the first value not in the ignore list.
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Coalesce extends CastBase
{
    private const NO_FALLBACK = '__dtot_no_fallback__';

    public function __construct(
        array | \Traversable $ignore = [null],
        mixed $fallback = self::NO_FALLBACK,
    ) {
        parent::__construct([$ignore, $fallback]);
    }

    #[\Override]
    public function cast(mixed $value, array $args): mixed
    {
        /** @var array|\Traversable $ignore */
        $ignore = $args[0];
        $fallback = $args[1];

        if ($ignore instanceof \Traversable) {
            $ignore = iterator_to_array($ignore, false);
        }

        if (\is_array($value)) {
            $iterable = $value;
        } elseif ($value instanceof \Traversable) {
            $iterable = $value;
        } else {
            throw TransformException::expected(
                operand: $value,
                expected: ['type.array', 'type.traversable'],
            );
        }

        foreach ($iterable as $item) {
            if (!\in_array($item, $ignore, true)) {
                return $item;
            }
        }

        if (self::NO_FALLBACK !== $fallback) {
            return $fallback;
        }

        throw TransformException::reason(
            value: $value,
            template_suffix: 'coalesce.no_value',
            errorCode: 'transform.coalesce',
        );
    }
}

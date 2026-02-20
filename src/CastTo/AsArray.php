<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\Process\TransformException;
use Nandan108\DtoToolkit\Internal\Exporter;

/**
 * Casts nested DTOs or other objects to array.
 *
 * @psalm-suppress UnusedClass
 */
/** @api */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class AsArray extends CastBase
{
    /** @api */
    public function __construct(array $supplementalProps = [], bool $recursive = false)
    {
        parent::__construct([$supplementalProps, $recursive]);
    }

    #[\Override]
    /** @internal */
    public function cast(mixed $value, array $args): mixed
    {
        if (!\is_object($value)) {
            throw TransformException::expected(
                operand: $value,
                expected: 'type.object',
            );
        }

        /** @psalm-suppress UnnecessaryVarAnnotation */
        /** @var array{0: array, 1: bool} $args */
        [$supplementalProps, $recursive] = $args;

        if ($value instanceof \Traversable) {
            // if there are duplicate keys, the ones from $value take precedence over $supplementalProps
            $array = iterator_to_array($value, true) + $supplementalProps;

            return $recursive
                ? Exporter::normalizeNestedDtos(
                    normalizedProps: $array,
                    exportsAsArray: true,
                    recursive: true,
                )
                : $array;
        }

        return Exporter::export(
            source: $value,
            as: 'array',
            supplementalProps: $supplementalProps,
            recursive: $recursive,
        );
    }
}

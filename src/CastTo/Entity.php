<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Contracts\CreatesFromArrayOrEntityInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\Process\TransformException;
use Nandan108\DtoToolkit\Internal\Exporter;

/**
 * Casts arrays or nested DTO to a specified Entity class
 * If input is array, will use CreatesFromArrayOrEntityInterface::newFromArray.
 *
 * @psalm-suppress UnusedClass
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Entity extends CastBase
{
    /**
     * @param ?class-string $entityClass
     */
    public function __construct(?string $entityClass = null, array $extraProps = [], bool $recursive = false)
    {
        parent::__construct([$entityClass, $extraProps, $recursive]);
    }

    #[\Override]
    public function cast(mixed $value, array $args): mixed
    {
        if (!(is_array($value) || $value instanceof BaseDto)) {
            throw TransformException::expected(
                operand: $value,
                methodOrClass: self::class,
                expected: 'DTO|array',
            );
        }

        /** @var class-string|null $explicitEntityClass */
        /** @var bool $recursive */
        /** @var array $extraProps */
        [$explicitEntityClass, $extraProps, $recursive] = $args;

        return Exporter::export(
            source: $value,
            as: 'entity',
            entity: $explicitEntityClass,
            extraProps: $extraProps,
            recursive: $recursive,
        );
    }
}

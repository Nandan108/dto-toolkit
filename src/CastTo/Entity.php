<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Attribute\DefaultOutboundEntity;
use Nandan108\DtoToolkit\Attribute\MapTo;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\Process\TransformException;
use Nandan108\DtoToolkit\Internal\Exporter;

/**
 * Casts arrays or nested DTO to a specified Entity class
 * Actual export is delegated to Exporter::export method.
 *
 * Note: `$entityClass` is intentionally not validated in the constructor.
 * Entity resolution happens at cast time in `Exporter::export()`, which may use
 * a DI container and can support types that are not directly instantiable
 * (for example, interfaces or abstract classes).
 * If resolution/instantiation fails, an `InvalidConfigException` is thrown at cast time.
 *
 * @api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Entity extends CastBase
{
    /**
     * @param ?class-string $entityClass       The entity class to use when exporting. Resolution order:
     *                                         1. If $entityClass param is provided, it is used
     *                                         2. If $value is a DTO with #[DefaultOutboundEntity] attribute, with matching scope, that class is used.
     *                                         3. If $value implements PreparesEntityInterface, it is used to instantiate the entity.
     *                                         4. Otherwise, InvalidConfigException is thrown.
     * @param array         $supplementalProps Additional data to set on the entity. This can be used to set
     *                                         relations or other properties that are not part of the DTO.
     *                                         These properties are not subject to #[MapTo] attributes.
     * @param bool          $recursive         whether to recursively convert nested DTOs to entities
     */
    public function __construct(?string $entityClass = null, array $supplementalProps = [], bool $recursive = false)
    {
        parent::__construct([$entityClass, $supplementalProps, $recursive]);
    }

    #[\Override]
    /** @internal */
    public function cast(mixed $value, array $args): mixed
    {
        /** @psalm-suppress UnnecessaryVarAnnotation  */
        /** @var array{0: ?class-string, 1: array<string, mixed>, 2: bool} $args */
        [$entity, $supplementalProps, $recursive] = $args;

        if (!(\is_array($value) || $value instanceof BaseDto)) {
            throw TransformException::expected(
                operand: $value,
                expected: ['type.dto', 'type.array'],
            );
        }

        return Exporter::export(
            source: $value,
            as: 'entity',
            entity: $entity,
            supplementalProps: $supplementalProps,
            recursive: $recursive,
        );
    }
}

<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Internal;

use Nandan108\DtoToolkit\Attribute\MapTo;
use Nandan108\DtoToolkit\Contracts\PreparesEntityInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Core\ProcessingErrorList;
use Nandan108\DtoToolkit\Enum\ErrorMode;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Support\ContainerBridge;
use Nandan108\PropAccess\PropAccess;

final class Exporter
{
    /**
     * Convert the DTO to an entity.
     *
     * Will auto-fill the entity's public properties with the DTO's public properties
     *
     * @param array|object             $source     the source DTO or array to convert
     * @param class-string|object|null $entity     The entity to fill, directly as an object, or as a class name to instantiate.
     *                                             Resolution order:
     *                                             1. If $entity param is an object, it is used directly.
     *                                             2. If $entity param is a class-string, it is instantiated (via container if possible).
     *                                             3. If $source is a DTO with #[DefaultOutboundEntity] attribute, with matching scope, that class is used.
     *                                             4. If $source implements PreparesEntityInterface, it is used to instantiate the entity.
     *                                             5. Otherwise, InvalidConfigException is thrown.
     * @param array                    $extraProps Additional data to set on the entity. This can be used to set
     *                                             relations or other properties that are not part of the DTO.
     * @param ProcessingErrorList|null $errorList  optional error list to collect processing errors
     * @param ErrorMode|null           $errorMode  optional error mode for processing
     * @param bool                     $recursive  whether to recursively convert nested DTOs to entities.
     *                                             Only DTOs participate in this recursion, arrays are treated as terminal values.
     * @param 'array'|'entity'         $as
     *
     * @return array<non-empty-string, mixed>|object the resulting entity as an array or object
     *
     * @throws InvalidConfigException
     */
    public static function export(
        array | object $source,
        string $as,
        string | object | null $entity = null,
        array $extraProps = [],
        bool $recursive = false,
        ?ProcessingErrorList $errorList = null,
        ?ErrorMode $errorMode = null,
    ): array | object {
        $exportsAsEntity = 'entity' === $as;

        /** @psalm-suppress RedundantCondition */
        if ($source instanceof BaseDto) {

            $errorList and $source->setErrorList($errorList);

            $normalizedProps = ProcessingContext::wrapProcessing(
                dto: $source,
                errorMode: $errorMode,
                callback: function ($frame) use ($source, $recursive, $exportsAsEntity): array {
                    // Get properties already cast, ready to be set on entity
                    /** @psalm-suppress UndefinedMethod */
                    $normalizedProps = $source->toOutboundArray(
                        runPreOutputHook: false,
                        applyOutboundMappings: !$exportsAsEntity,
                    );

                    // Then recursively convert nested DTOs if needed
                    if ($recursive) {
                        foreach ($normalizedProps as $prop => $value) {
                            if ($value instanceof BaseDto) {
                                // $value cannot be from a property with a declared #[CastTo\Entity] attribute, as
                                // it would have already been converted by toOutboundArray().

                                $normalizedProps[$prop] = self::export(
                                    source: $value,
                                    as: $exportsAsEntity ? 'entity' : 'array',
                                    errorList: $frame->errorList,
                                    errorMode: $frame->errorMode,
                                    recursive: true,
                                );
                            }
                        }
                    }

                    return $normalizedProps;
                },
            );

            if ($exportsAsEntity && !$entity) {
                $entity = $source->getDefaultOutboundEntityClass();
            }
        } elseif (\is_array($source)) {
            $normalizedProps = $source;
            if ($recursive) {
                // Recursively convert nested DTOs to entities
                foreach ($normalizedProps as $prop => $value) {
                    if ($value instanceof BaseDto) {
                        $normalizedProps[$prop] = self::export(
                            source: $value,
                            as: $exportsAsEntity ? 'entity' : 'array',
                            errorList: $errorList,
                            errorMode: $errorMode,
                            recursive: true,
                        );
                    }
                }
            }
        } else {
            $normalizedProps = PropAccess::getValueMap($source);
        }

        /** @psalm-suppress InvalidOperand */
        $outboundProps = [...$normalizedProps, ...$extraProps];

        if (!$exportsAsEntity) {
            return $outboundProps;
        }

        $hydrated = false;
        if (\is_object($entity)) {
            // Use provided entity object directly
            // Nothing to do here
        } elseif (\is_string($entity)) {
            // Delegate entity instantiation to container if possible
            /** @var object */
            if (ContainerBridge::has($entity)) {
                $entity = ContainerBridge::get($entity);
            } else {
                if (!class_exists($entity)) {
                    throw new InvalidConfigException(
                        message: "Entity class '$entity' does not exist.",
                        debug: ['source' => $source],
                    );
                }
                $entity = new $entity();
            }
        } elseif ($source instanceof PreparesEntityInterface) {
            ['entity' => $entity, 'hydrated' => $hydrated] =
                $source->prepareEntity($outboundProps);
        } else {
            throw new InvalidConfigException(
                message: 'No target entity object or class name provided. '.
                'Either decorate your DTO with #[DefaultOutboundEntity], implement PreparesEntityInterface, '.
                'or provide an explicit $entityClass argument value.',
                debug: ['source' => $source],
            );
        }

        if ($hydrated) {
            // properties are already set on entity
            /** @var object $entity */
            return $entity;
        }

        if ($source instanceof BaseDto) {
            // $source is a DTO, which means properties may be decorated with #[MapTo] attributes.
            // To respect those, we use the output of MapTo::getSetters to set properties.
            /** @var array<callable(mixed,mixed):void> */
            $setters = MapTo::getSetters($source, array_keys($outboundProps), $entity);
            foreach ($outboundProps as $prop => $value) {
                $setters[$prop]($entity, $value);
            }
            // Finally, run preOutput hook if any
            $source->preOutput($entity);
        } else {
            // Source is not a DTO, use PropAccess to set properties
            /** @var array<callable(mixed,mixed):void> */
            $setters = PropAccess::getSetterMap($entity, array_keys($outboundProps));
            foreach ($outboundProps as $prop => $value) {
                $setters[$prop]($entity, $value);
            }
        }

        return $entity;
    }
}

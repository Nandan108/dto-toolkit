<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Internal;

use Nandan108\DtoToolkit\Attribute\DefaultOutboundEntity;
use Nandan108\DtoToolkit\Attribute\MapTo;
use Nandan108\DtoToolkit\Contracts\PreparesEntityInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Core\ProcessingErrorList;
use Nandan108\DtoToolkit\Enum\ConstructMode;
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
     * @param array|object             $source            the source DTO or array to convert
     * @param class-string|object|null $entity            The entity to fill, directly as an object, or as a class name to instantiate.
     *                                                    Resolution order:
     *                                                    1. If $entity param is an object, it is used directly.
     *                                                    2. If $entity param is a class-string, it is instantiated (via container if possible).
     *                                                    3. If $source is a DTO with #[DefaultOutboundEntity] attribute, with matching scope, that class is used.
     *                                                    4. If $source implements PreparesEntityInterface, it is used to instantiate the entity.
     *                                                    5. Otherwise, InvalidConfigException is thrown.
     * @param array                    $supplementalProps Additional data to set on the entity. This can be used to set
     *                                                    relations or other properties that are not part of the DTO.
     *                                                    These properties are not subject to #[MapTo] attributes.
     *                                                    Properties already present in $source are ignored.
     * @param ProcessingErrorList|null $errorList         optional error list to collect processing errors
     * @param ErrorMode|null           $errorMode         optional error mode for processing
     * @param bool                     $recursive         whether to recursively convert nested DTOs to entities.
     *                                                    Only DTOs participate in this recursion, arrays are treated as terminal values.
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
        array $supplementalProps = [],
        bool $recursive = false,
        ?ProcessingErrorList $errorList = null,
        ?ErrorMode $errorMode = null,
    ): array | object {
        $exportsAsArray = 'array' === $as;

        // 1. Normalize Outbound Props
        $outboundProps = self::buildOutboundProps(
            source: $source,
            recursive: $recursive,
            exportsAsArray: $exportsAsArray,
            supplementalProps: $supplementalProps,
            errorList: $errorList,
            errorMode: $errorMode,
        );

        // If exporting as array, return early
        if ($exportsAsArray) {
            return $outboundProps->toArray();
        }

        // 2. Prepare/Instantiate target entity
        ['entity' => $entity, 'hydrated' => $hydrated] = self::prepareEntity(
            source: $source,
            entity: $entity,
            outboundProps: $outboundProps,
        );

        // 3. Post-instantiation: hydrate and run preOutput hook as needed
        if (!$hydrated) {
            self::hydrateEntity($source, $entity, $outboundProps);
        }

        // Finally, run preOutput hook if available
        if ($outboundProps->sourceWasDto) {
            /** @var BaseDto $source */
            $source->preOutput($entity);
        }

        return $entity;
    }

    /**
     * Helper method to that:
     * - normalizes outbound props from DTO sources using ->toOutboundArray()
     * - if $recursive, recursively converts nested DTOs to entities or arrays
     *
     * @param array<non-empty-string, mixed>|object $source
     * @param array<non-empty-string, mixed>        $supplementalProps
     */
    private static function buildOutboundProps(
        array | object $source,
        bool $recursive,
        bool $exportsAsArray,
        array $supplementalProps,
        ?ProcessingErrorList $errorList,
        ?ErrorMode $errorMode,
    ): OutboundProps {
        if ($source instanceof BaseDto) {

            $errorList && $source->setErrorList($errorList);

            $normalizedProps = ProcessingContext::wrapProcessing(
                dto: $source,
                errorMode: $errorMode,
                callback: function ($frame) use ($source, $recursive, $exportsAsArray): array {
                    // Get properties already cast, ready to be set on entity
                    /** @psalm-suppress UndefinedMethod */
                    $normalizedProps = $source->toOutboundArray(
                        runPreOutputHook: false,
                        applyOutboundMappings: $exportsAsArray,
                    );

                    // Then recursively convert nested DTOs if needed
                    if ($recursive) {
                        foreach ($normalizedProps as $prop => $value) {
                            if ($value instanceof BaseDto) {
                                // $value cannot be from a property with a declared #[CastTo\Entity] attribute, as
                                // it would have already been converted by toOutboundArray().

                                $normalizedProps[$prop] = self::export(
                                    source: $value,
                                    as: $exportsAsArray ? 'array' : 'entity',
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

            return new OutboundProps($normalizedProps, $supplementalProps, true);
        }
        if (\is_array($source)) {
            $normalizedProps = $source;
            if ($recursive) {
                // Recursively convert nested DTOs to entities
                foreach ($normalizedProps as $prop => $value) {
                    if ($value instanceof BaseDto) {
                        $normalizedProps[$prop] = self::export(
                            source: $value,
                            as: $exportsAsArray ? 'array' : 'entity',
                            errorList: $errorList,
                            errorMode: $errorMode,
                            recursive: true,
                        );
                    }
                }
            }

            return new OutboundProps($normalizedProps, $supplementalProps, false);
        } else {
            $normalizedProps = PropAccess::getValueMap($source) ?? [];

            return new OutboundProps($normalizedProps, $supplementalProps, false);
        }
    }

    /**
     * Helper method to instantiate an entity class with error handling.
     *
     * @param class-string $class
     *
     * @throws InvalidConfigException
     */
    private static function instantiate(
        string $class,
        ConstructMode $mode,
        array $args,
        mixed $source,
    ): object {
        try {
            return match ($mode) {
                ConstructMode::Default   => new $class(),
                ConstructMode::Array     => new $class($args),
                ConstructMode::NamedArgs => new $class(...$args),
            };
        } catch (\Throwable $e) {
            throw new InvalidConfigException(
                message: "Failed to instantiate entity class '$class': ".$e->getMessage(),
                debug: ['source' => $source],
                previous: $e,
            );
        }
    }

    /**
     * Helper method to Prepare Entity Instance.
     *
     * @param array<non-empty-string, mixed>|object $source
     * @param class-string|object|null              $entity
     *
     * @return array{entity: object, hydrated: bool} the prepared entity and whether it was already hydrated
     *
     * @throws InvalidConfigException
     */
    private static function prepareEntity(
        array | object $source,
        string | object | null $entity,
        OutboundProps $outboundProps,
    ): array {
        if (\is_object($entity)) {
            // Entity object already instantiated; not yet hydrated
            return ['entity' => $entity, 'hydrated' => false];
        }

        $constructMode = ConstructMode::Default;

        // If we dont't have an entity yet, check for DefaultOutboundEntity attribute on source DTO
        if (!$entity && $outboundProps->sourceWasDto) {
            /** @var BaseDto $source */
            $outboundEntity = DefaultOutboundEntity::resolveForDto($source);
            if ($outboundEntity) {
                $entity = $outboundEntity['class'];
                $constructMode = $outboundEntity['construct'];
            }
        }

        if (\is_string($entity)) {
            // we've got a class string to instantiate
            if (ConstructMode::Default === $constructMode) {
                /** @var object */
                $entity = ContainerBridge::has($entity)
                    // Delegate entity instantiation to container if possible
                    ? ContainerBridge::get($entity)
                    // Instantiate entity via no-args constructor
                    : self::instantiate($entity, ConstructMode::Default, [], $source);
                $hydrated = false;
            } else {
                /** @var BaseDto $source */
                if ($outboundProps->sourceWasDto
                    && ($outboundNamesMap = MapTo::getOutboundNamesMap($source, $outboundProps->dtoKeys()))) {
                    // remap outboundProps keys to match constructor param names
                    $outboundProps->applyRemap($outboundNamesMap);
                }
                // Instantiate entity via constructor with outbound props
                $entity = self::instantiate($entity, $constructMode, $outboundProps->toArray(), $source);
                $hydrated = true;
            }

            return ['entity' => $entity, 'hydrated' => $hydrated];
        }

        if ($source instanceof PreparesEntityInterface) {
            return $source->prepareEntity($outboundProps->toArray());
        }

        throw new InvalidConfigException(
            message: 'No target entity object or class name provided. '.
            'Either decorate your DTO with #[DefaultOutboundEntity], implement PreparesEntityInterface, '.
            'or provide an explicit $entityClass argument value.',
            debug: ['source' => $source],
        );
    }

    /**
     * Helper method to Hydrate Entity if needed.
     *
     * @param array<non-empty-string, mixed>|object $source
     */
    private static function hydrateEntity(
        array | object $source,
        object $entity,
        OutboundProps $outboundProps,
    ): void {
        $props = $outboundProps->toArray();
        if ($outboundProps->sourceWasDto) {
            /** @var BaseDto $source */
            // Source is a DTO: use MapTo to set properties (honors #[MapTo] attributes)
            $setters = MapTo::getSetters(
                dto: $source,
                propNames: array_keys($props),
                targetEntity: $entity,
            );
        } else {
            // Source is not a DTO: use PropAccess to set properties
            $setters = PropAccess::getSetterMapOrThrow(
                target: $entity,
                propNames: array_keys($props),
            );
        }

        foreach ($setters as $prop => $setter) {
            $setter($entity, $props[$prop]);
        }
    }
}

final class OutboundProps
{
    /**
     * Summary of __construct.
     *
     * @param array<non-empty-string, mixed> $props
     * @param array<non-empty-string, mixed> $supplementalProps
     */
    public function __construct(
        public array $props,
        public array $supplementalProps,
        public bool $sourceWasDto,
    ) {
    }

    public function toArray(): array
    {
        return [...$this->props, ...$this->supplementalProps];
    }

    /** @param non-empty-array $nameMap */
    public function applyRemap(array $nameMap): void
    {
        $outboundProps = $this->props;
        foreach ($nameMap as $fromKey => $toKey) {
            if (null !== $toKey) {
                $outboundProps[$toKey] = $this->props[$fromKey];
            }
            unset($outboundProps[$fromKey]);
        }
        $this->props = $outboundProps;
    }

    /** @return list<non-empty-string> */
    public function dtoKeys(): array
    {
        return array_keys($this->props);
    }
}

<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Internal;

use Nandan108\DtoToolkit\Attribute\DefaultOutboundEntity;
use Nandan108\DtoToolkit\Attribute\MapTo;
use Nandan108\DtoToolkit\Contracts\PreparesEntityInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Core\ProcessingErrorList;
use Nandan108\DtoToolkit\Core\ProcessingFrame;
use Nandan108\DtoToolkit\Enum\ConstructMode;
use Nandan108\DtoToolkit\Enum\ErrorMode;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Support\ContainerBridge;
use Nandan108\PropAccess\PropAccess;

/** @internal */
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
     * @return array|object the resulting entity as an array or object
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
     */
    private static function buildOutboundProps(
        array | object $source,
        bool $recursive,
        bool $exportsAsArray,
        array $supplementalProps,
        ?ProcessingErrorList $errorList,
        ?ErrorMode $errorMode,
    ): OutboundProps {
        $sourceIsDto = false;
        if ($source instanceof BaseDto) {
            // DTO source: use toOutboundArray() to get normalized props,
            // if $recursive, further normalize nested DTOs to arrays or entities depending on $exportsAsArray
            $sourceIsDto = true;
            $errorList && $source->setErrorList($errorList);

            $normalizedProps = ProcessingContext::wrapProcessing(
                dto: $source,
                errorMode: $errorMode,
                callback: function (ProcessingFrame $frame) use ($source, $recursive, $exportsAsArray): array {
                    // Get properties already cast, ready to be set on entity
                    /** @psalm-suppress UndefinedMethod */
                    $normalizedProps = $source->toOutboundArray(
                        runPreOutputHook: false,
                        applyOutboundMappings: $exportsAsArray,
                    );

                    // Then recursively convert nested DTOs if needed
                    if ($recursive) {
                        $normalizedProps = self::normalizeNestedDtos(
                            $normalizedProps,
                            $exportsAsArray,
                            true,
                            $frame->errorList,
                            $frame->errorMode,
                        );
                    }

                    return $normalizedProps;
                },
            );
        } elseif (\is_array($source)) {
            // Array source: filter and normalize nested DTOs if needed

            $normalizedProps = $recursive
                ? self::normalizeNestedDtos(
                    $source,
                    $exportsAsArray,
                    true,
                    $errorList,
                    $errorMode,
                )
                : $source;
        } else {
            // Non-DTO Object source: use PropAccess to read accessible content
            // filter and normalize nested DTOs if needed
            $normalizedProps = PropAccess::getValueMap($source) ?? [];
        }

        return new OutboundProps($normalizedProps, $supplementalProps, $sourceIsDto);
    }

    /**
     * Recursively normalizes nested DTOs to entities or arrays, depending on $exportsAsArray.
     *
     * @template TArrayType of array
     *
     * @param TArrayType $normalizedProps
     *
     * @return TArrayType
     */
    public static function normalizeNestedDtos(array $normalizedProps, bool $exportsAsArray, bool $recursive, ?ProcessingErrorList $errorList = null, ?ErrorMode $errorMode = null): array
    {
        /** @var mixed $childDto */
        foreach ($normalizedProps as &$childDto) {
            if (!$childDto instanceof BaseDto) {
                continue;
            }

            $childDto = self::export(
                source: $childDto,
                as: $exportsAsArray ? 'array' : 'entity',
                errorList: $errorList,
                errorMode: $errorMode,
                recursive: $recursive,
            );
        }

        return $normalizedProps;
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
            if (!class_exists($class)) {
                throw new InvalidConfigException(
                    message: "Target entity class '$class' does not exist.",
                    debug: ['source' => $source],
                );
            }
            $ref = new \ReflectionClass($class);

            return match ($mode) {
                ConstructMode::Default   => $ref->newInstance(),
                ConstructMode::Array     => $ref->newInstance($args),
                ConstructMode::NamedArgs => $ref->newInstanceArgs($args),
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
     * @param array<array-key, mixed>|object $source
     * @param class-string|object|null       $entity
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
            $entityClass = $entity;
            // we've got a class string to instantiate
            if (ConstructMode::Default === $constructMode) {
                /** @var object */
                // Resolve via container when available; otherwise fallback to direct instantiation.
                $entity = ContainerBridge::tryGet($entityClass)
                    ?? self::instantiate($entityClass, ConstructMode::Default, [], $source);
                $hydrated = false;
            } else {
                /** @var BaseDto $source */
                if ($outboundProps->sourceWasDto
                    && ($outboundNamesMap = MapTo::getOutboundNamesMap($source, $outboundProps->dtoKeys()))) {
                    // remap outboundProps keys to match constructor param names
                    $outboundProps->applyRemap($outboundNamesMap);
                }
                // Instantiate entity via constructor with outbound props
                $entity = self::instantiate($entityClass, $constructMode, $outboundProps->toArray(), $source);
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
     * Helper method to hydrate entity if needed.
     *
     * @param array<array-key, mixed>|object $source
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
                propNames: $outboundProps->allKeys(),
                targetEntity: $entity,
            );
        } else {
            // Source is not a DTO: use PropAccess to set properties
            $setters = PropAccess::getSetterMapOrThrow(
                target: $entity,
                propNames: $outboundProps->allKeys(),
            );
        }

        foreach ($setters as $prop => $setter) {
            $setter($entity, $props[$prop]);
        }
    }
}

/** @internal */
final class OutboundProps
{
    public function __construct(
        public array $props,
        public array $supplementalProps,
        public bool $sourceWasDto,
    ) {
    }

    /** @return array<array-key, mixed> */
    public function toArray(): array
    {
        return $this->props + $this->supplementalProps;
    }

    /** @param array<array-key, array-key|null> $nameMap */
    public function applyRemap(array $nameMap): void
    {
        $outboundProps = $this->props;
        foreach ($nameMap as $fromKey => $toKey) {
            if (null !== $toKey) {
                /** @psalm-var mixed */
                $outboundProps[$toKey] = $this->props[$fromKey];
            }
            unset($outboundProps[$fromKey]);
        }
        $this->props = $outboundProps;
    }

    /** @return list<truthy-string> */
    public function dtoKeys(): array
    {
        return array_values(array_filter(
            array_keys($this->props),
            fn ($key) => \is_string($key) && (bool) $key,
        ));
    }

    /** @return list<truthy-string> */
    public function allKeys(): array
    {
        return array_values(array_filter(
            array_keys($this->props + $this->supplementalProps),
            fn ($key) => \is_string($key) && (bool) $key,
        ));
    }
}

<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Attribute;

use Nandan108\DtoToolkit\Contracts\PhaseAwareInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Enum\Phase;
use Nandan108\DtoToolkit\Traits\HasPhase;
use Nandan108\PropAccess\PropAccess;

/**
 * Use `MapTo` to rename or discard properties during outbound transformation (DTO → array or entity),
 * or to specify a custom setter method to use when hydrating an entity.
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class MapTo implements PhaseAwareInterface
{
    use HasPhase;

    public bool $nullOutboundName = false;

    public function __construct(
        public ?string $outboundName,
        public ?string $customSetter = null,
    ) {
        $this->isOutbound = true;
        $this->isIoBound = true;
    }

    #[\Override]
    public function setOutbound(bool $isOutbound): void
    {
        // This attribute is only used in the outbound-export phase,
        // so calls to setOutbound() are a no-op.

        // Note: We don't throw an exception here even if $isOutbound is false,
        // so as to not force the use of #[Outbound] on top of #[MapTo] attributes, which would be redundant.
    }

    /**
     * Get the mappers for a given DTO and phase.
     *
     * @param BaseDto           $dto   the DTO instance
     * @param ?array<array-key> $props the properties to return a mapper for
     *
     * @return array<string, self> an array of MapFrom instances, indexed by property name
     */
    public static function getMappers(BaseDto $dto, ?array $props = null): array
    {
        /** @var array<string, self> $mapToAttrByProp */
        $mapToAttrByProp = ($dto::class)::getPhaseAwarePropMeta(Phase::OutboundExport, 'attr', self::class, true);

        // if $props a specified, filter out the mappers that do not match the given properties
        /** @psalm-suppress RiskyTruthyFalsyComparison */
        if ($props) {
            return array_intersect_key($mapToAttrByProp, array_flip($props));
        }

        return $mapToAttrByProp;
    }

    /**
     * Return $output with keys mapped to the outbound names defined by #[MapTo] attributes on the DTO properties.
     *
     * @param array<array-key, mixed> $output
     * @param array<self>             $mappers
     */
    public static function applyOutboundKeys(array $output, BaseDto $dto, ?array $mappers = null): array
    {
        $mappers ??= self::getMappers($dto, array_keys($output));
        // If it returns null, it means no MapTo attributes were found,
        // so we can return the output as is.
        if (!$mappers) {
            // No MapTo attributes found, return the output as is
            return $output;
        }

        $mappedOutput = [];
        /** @psalm-var mixed $value */
        foreach ($output as $propName => $value) {
            $mapper = $mappers[$propName] ?? null;
            $outboundName = $mapper ? $mapper->outboundName : $propName;
            // Skip properties that have #[MapTo(null)] attribute
            if (null !== $outboundName) {
                /** @psalm-var mixed */
                $mappedOutput[$outboundName] = $value;
            }
        }

        return $mappedOutput;
    }

    /**
     * Get the setters for the properties of a DTO that has been filled.
     *
     * @param list<string> $propNames the property names to get setters for
     */
    public static function getSetters(BaseDto $dto, array $propNames, object $targetEntity): array
    {
        $mappers = self::getMappers($dto, $propNames);

        $setters = $propsWithoutCustomSetter = [];
        // we do a first pass, making custom setter closures when a custom setter name is provided
        // and collecting mapped or non-mapped names where a setter name is not provided
        foreach ($propNames as $propName) {
            if ($mapper = $mappers[$propName] ?? null) {
                if (($customSetter = $mapper->customSetter) > '') {
                    // If the mapper has a custom setter, we use it
                    // ⚠️ it might throw an error if the setter method doesn't exist
                    $setters[$propName] = static function (object $entity, mixed $value) use ($customSetter): void {
                        /** @psalm-suppress MixedMethodCall */
                        $entity->$customSetter($value);
                    };
                } elseif (null !== $mapper->outboundName) {
                    $propsWithoutCustomSetter[$propName] = $mapper->outboundName;
                }
            } else {
                $propsWithoutCustomSetter[$propName] = $propName;
            }
        }

        // use the collection of mapped and non-mapped property names to get matching setters
        $standardSetters = PropAccess::getSetterMapOrThrow(
            $targetEntity,
            $propsWithoutCustomSetter,
            ignoreInaccessibleProps: false,
        );

        // values of $propsWithoutCustomSetter may be mapped property names,
        // when merging with custom setters we must use the original property names
        foreach ($propsWithoutCustomSetter as $originalPropName => $setterPropName) {
            $setters[$originalPropName] = $standardSetters[$setterPropName];
        }

        return $setters;
    }

    /**
     * Get the map of outbound names per property.
     * Setters are not included.
     *
     * @param list<string> $propNames the property names to get outbound names for
     */
    public static function getOutboundNamesMap(BaseDto $dto, array $propNames): array
    {
        $mappers = self::getMappers($dto, $propNames);
        $outboundNamesMap = [];
        foreach ($propNames as $propName) {
            if (array_key_exists($propName, $mappers)) {
                $outboundNamesMap[$propName] = $mappers[$propName]->outboundName;
            }
        }

        return $outboundNamesMap;
    }
}

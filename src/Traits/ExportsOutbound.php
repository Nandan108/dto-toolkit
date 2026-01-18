<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\Attribute\MapTo;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\ProcessingErrorList;
use Nandan108\DtoToolkit\Enum\ErrorMode;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Support\ContainerBridge;

/**
 * @psalm-require-extends BaseDto
 **/
trait ExportsOutbound
{
    /**
     * The class name of the entity that this DTO maps to.
     * Optional since not all DTOs are mapped to entities.
     *
     * @var class-string
     *
     * @psalm-suppress PossiblyUnusedProperty
     **/
    protected static ?string $entityClass = null;

    /**
     * Convert the DTO to an entity.
     *
     * Will auto-fill the entity's public properties with the DTO's public properties
     *
     * @param object|null $entity  The entity to fill. If null, a new instance will
     *                             be created from static::$entityClass.
     * @param array       $context Additional data to set on the entity. This can be used to set
     *                             relations or other properties that are not part of the DTO.
     *
     * @throws InvalidConfigException
     */
    public function toEntity(
        ?object $entity = null,
        array $context = [],
        ?ProcessingErrorList $errorList = null,
        ?ErrorMode $errorMode = null,
    ): object {
        /** @psalm-suppress RedundantCondition */
        if (!$this instanceof BaseDto) {
            throw new InvalidConfigException('DTO must extend BaseDto to use ExportsToEntity trait.');
        }

        // Get properties already cast, ready to be set on entity
        /** @psalm-suppress UndefinedMethod */
        $normalizedProps = $this->toOutboundArray($errorList, $errorMode, runPreOutputHook: false, applyOutboundMappings: false);

        /** @psalm-suppress InvalidOperand */
        $propsToSet = [...$normalizedProps, ...$context];

        if (!$entity) {
            // If no entity is passed, create a new one
            /** @psalm-suppress UndefinedMagicMethod */
            [$entity, $propsAreLoaded] = $this->newEntityInstance($propsToSet);
            // Could be that a subclass has its own way of preparing the entity, and the data is already loaded
            if ($propsAreLoaded) {
                // If the entity is already set, we don't need to set the properties again
                return $entity;
            }
        }

        $setters = MapTo::getSetters($this, array_keys($propsToSet), $entity);

        foreach ($propsToSet as $prop => $value) {
            $setters[$prop]($entity, $value);
        }

        // call pre-output hook
        /** @psalm-suppress UndefinedMethod */
        $this->preOutput($entity);

        /** @var object $entity */
        return $entity;
    }

    /**
     * Create a new instance of the entity class.
     * Can be subclassed.
     *
     * @throws InvalidConfigException
     *
     * @psalm-suppress PossiblyUnusedParam
     */
    protected function newEntityInstance(array $inputData = []): array
    {
        // we assume that $this instanceof BaseDto

        /** @psalm-suppress RiskyTruthyFalsyComparison */
        if (empty(static::$entityClass)) {
            throw new InvalidConfigException('No entity class specified on DTO '.$this::class.' for auto-instanciation.');
        }

        if (!class_exists(static::$entityClass)) {
            throw new InvalidConfigException('Entity class '.static::$entityClass.' does not exist');
        }

        // Instanciate entity via container if possible.
        // In a framework context, instantiation will be delegated to the DI container.
        $entity = ContainerBridge::has(static::$entityClass)
            ? ContainerBridge::get(static::$entityClass)
            : new static::$entityClass();

        // TODO: run inject() after instanciation if instance is injectable

        return [$entity, false];
    }
}

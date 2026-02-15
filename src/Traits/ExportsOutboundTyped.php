<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\ProcessingErrorList;
use Nandan108\DtoToolkit\Enum\ErrorMode;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Internal\Exporter;

/**
 * @psalm-require-extends BaseDto
 *
 * @template TEntity
 *
 * @api
 **/
trait ExportsOutboundTyped
{
    /**
     * Convert the DTO to an entity.
     *
     * This trait has a template parameter TEntity which represents the type of the entity being exported to.
     *
     * If you use psalm, place a docblock above `use ExportsOutbound`: /** @use ExportsOutbound\<YourEntityClass\> *\/
     *
     * @param object|class-string|null $entity            The entity to fill. If null, a new instance will be created.
     * @param array                    $supplementalProps Additional data to set on the entity. This can be used to set
     *                                                    relations or other properties that are not part of the DTO.
     * @param bool                     $recursive         whether to recursively convert nested DTOs to entities
     *
     * @psalm-suppress InvalidReturnType
     *
     * @return TEntity
     *
     * Will auto-fill the entity's public properties with the DTO's public properties
     *
     * @throws InvalidConfigException
     */
    public function exportToEntity(
        string | object | null $entity = null,
        array $supplementalProps = [],
        ?ProcessingErrorList $errorList = null,
        ?ErrorMode $errorMode = null,
        bool $recursive = false,
    ): object {
        return Exporter::export(
            source: $this,
            as: 'entity',
            entity: $entity,
            errorList: $errorList,
            supplementalProps: $supplementalProps,
            errorMode: $errorMode,
            recursive: $recursive,
        );
    }
}

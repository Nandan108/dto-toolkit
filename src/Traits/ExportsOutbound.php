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
 **/
trait ExportsOutbound
{
    /**
     * Convert the DTO to an entity.
     *
     * @param object|class-string|null $entity            The entity to fill. If null, a new instance will be created.
     * @param array                    $supplementalProps Additional data to set on the entity. This can be used to set
     *                                                    relations or other properties that are not part of the DTO.
     * @param bool                     $recursive         whether to recursively convert nested DTOs to entities
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
        /** @var object */
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

    public function exportToArray(
        ?ProcessingErrorList $errorList = null,
        ?ErrorMode $errorMode = null,
        bool $recursive = false,
    ): array {
        /** @var array */
        return Exporter::export(
            source: $this,
            as: 'array',
            entity: null,
            errorList: $errorList,
            errorMode: $errorMode,
            recursive: $recursive,
        );
    }
}

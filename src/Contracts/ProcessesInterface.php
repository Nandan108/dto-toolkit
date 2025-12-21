<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Contracts;

use Nandan108\DtoToolkit\Core\ProcessingErrorList;
use Nandan108\DtoToolkit\Enum\ErrorMode;

/**
 * Interface for classes that need to process inbound data.
 *
 * This interface is used to define a method for processing input values
 * to DTO properties after validation.
 *
 * @psalm-suppress UnusedClass
 */
interface ProcessesInterface
{
    /**
     * Apply transformations (casting/coerctions) to input values to DTO properties.
     * This happens AFTER validation, since it makes no sense to validate already-coerced values.
     *
     * @return void
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function processInbound();

    /**
     * Apply outbound transformations to DTO values before mapping to array/entity.
     *
     * @param array                    $props     The properties to process
     * @param ProcessingErrorList|null $errorList An optional list to collect processing errors
     * @param ErrorMode|null           $errorMode The error mode to use during processing
     *
     * @return array The processed key-value pairs
     */
    public function processOutbound(
        array $props,
        ?ProcessingErrorList $errorList = null,
        ?ErrorMode $errorMode = null,
    ): array;
}

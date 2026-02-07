<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Contracts;

/**
 * Optional contract for node producers that want a custom display/origin name.
 */
interface ProvidesProcessingNodeNameInterface
{
    /**
     * @return truthy-string
     */
    public function getProcessingNodeName(): string;
}

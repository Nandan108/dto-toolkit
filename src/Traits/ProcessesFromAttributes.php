<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Core\ProcessingErrorList;
use Nandan108\DtoToolkit\Enum\ErrorMode;
use Nandan108\DtoToolkit\Exception\Process\ProcessingException;

/** @psalm-require-extends \Nandan108\DtoToolkit\Core\BaseDto */
trait ProcessesFromAttributes // implements ProcessesInterface
{
    // will be used if using class implements NormalizesInboundInterface
    #[\Override]
    public function processInbound(?ProcessingErrorList $errorList = null, ?ErrorMode $errorMode = null): void
    {
        $errorMode ??= $this->getErrorMode();

        $processorMap = CastTo::getProcessingNodeClosureMap(dto: $this, outbound: false);

        foreach ($processorMap as $prop => $process) {
            if ($this->_filled[$prop] ?? false) {
                CastTo::setCurrentPropName($prop);
                try {
                    $this->$prop = $process($this->$prop);
                } catch (ProcessingException $e) {
                    if (ErrorMode::FailFast === $errorMode) {
                        throw $e;
                    }

                    $errorList?->add($e);

                    switch ($errorMode) {
                        case ErrorMode::CollectFailToInput:
                            // leave original input value
                            break;
                        case ErrorMode::CollectFailToNull:
                            $this->$prop = null;
                            break;
                        case ErrorMode::CollectNone:
                            unset($this->_filled[$prop]); // mark as unfilled
                            $this->$prop = null;
                            break;
                    }
                } finally {
                    CastTo::setCurrentPropName(null);
                }
            }
        }
    }

    // will be used if using class implements NormalizesOutboundInterface
    #[\Override]
    public function processOutbound(
        array $props,
        ?ProcessingErrorList $errorList = null,
        ?ErrorMode $errorMode = null,
    ): array {
        $errorMode ??= $this->getErrorMode();

        $processorMap = CastTo::getProcessingNodeClosureMap(dto: $this, outbound: true);

        $normalized = [];
        foreach ($props as $prop => $value) {
            if (isset($processorMap[$prop])) {
                CastTo::setCurrentPropName($prop);
                try {
                    $normalized[$prop] = $processorMap[$prop]($value);
                } catch (ProcessingException $e) {
                    if (ErrorMode::FailFast === $errorMode) {
                        throw $e;
                    }

                    $errorList?->add($e);

                    switch ($errorMode) {
                        case ErrorMode::CollectFailToInput:
                            $normalized[$prop] = $value;
                            break;
                        case ErrorMode::CollectFailToNull:
                            $normalized[$prop] = null;
                            break;
                        case ErrorMode::CollectNone:
                            // skip adding this property
                            break;
                    }
                } finally {
                    CastTo::setCurrentPropName(null);
                }
            } else {
                $normalized[$prop] = $value;
            }
        }

        return $normalized;
    }
}

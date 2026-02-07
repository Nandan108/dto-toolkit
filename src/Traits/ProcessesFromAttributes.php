<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Core\ProcessingErrorList;
use Nandan108\DtoToolkit\Core\ProcessingFrame;
use Nandan108\DtoToolkit\Enum\ErrorMode;
use Nandan108\DtoToolkit\Exception\Process\ProcessingException;

/** @psalm-require-extends \Nandan108\DtoToolkit\Core\BaseDto */
trait ProcessesFromAttributes // implements ProcessesInterface
{
    // will be used if using class implements NormalizesInboundInterface
    #[\Override]
    public function processInbound(?ErrorMode $errorMode = null, ?ProcessingErrorList $errorList = null): void
    {
        $errorList && $this->setErrorList($errorList);

        $callback = function (ProcessingFrame $frame): void {
            $processorMap = CastTo::getProcessingNodeClosureMap(dto: $this, outbound: false);
            foreach ($processorMap as $prop => $process) {
                if ($this->_filled[$prop] ?? false) {
                    ProcessingContext::pushPropPath($prop);
                    try {
                        $this->$prop = $process($this->$prop);
                    } catch (ProcessingException $e) {
                        if (ErrorMode::FailFast === $frame->errorMode) {
                            throw $e;
                        }

                        $frame->errorList->add($e);

                        switch ($frame->errorMode) {
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
                        ProcessingContext::popPropPath();
                    }
                }
            }
        };

        ProcessingContext::wrapProcessing($this, $callback, $errorMode);
    }

    // will be used if using class implements NormalizesOutboundInterface
    #[\Override]
    public function processOutbound(
        array $props,
        ?ProcessingErrorList $errorList = null,
        ?ErrorMode $errorMode = null,
    ): array {
        $errorList and $this->setErrorList($errorList);

        $callback = function (ProcessingFrame $frame) use ($props): array {
            $processorMap = CastTo::getProcessingNodeClosureMap(dto: $this, outbound: true);
            $normalized = [];
            foreach ($props as $prop => $value) {
                if (isset($processorMap[$prop])) {
                    ProcessingContext::pushPropPath($prop);
                    try {
                        $normalized[$prop] = $processorMap[$prop]($value);
                    } catch (ProcessingException $e) {
                        if (ErrorMode::FailFast === $frame->errorMode) {
                            throw $e;
                        }

                        $frame->errorList->add($e);
                        switch ($frame->errorMode) {
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
                        ProcessingContext::popPropPath();
                    }
                } else {
                    $normalized[$prop] = $value;
                }
            }

            return $normalized;
        };

        return ProcessingContext::wrapProcessing($this, $callback, $errorMode);
    }
}

<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Core;

use Nandan108\DtoToolkit\Enum\ErrorMode;

final class ProcessingFrame
{
    public BaseDto $dto;

    /** @psalm-suppress PossiblyUnusedProperty */
    public ProcessingErrorList $errorList;

    public ErrorMode $errorMode;

    /** @var array<string, mixed> */
    public array $context;

    /** @var list<int|non-empty-string> */
    public array $propPathSegments;

    /**
     * Build the dotted property path from the frame's property path segments.
     *
     * @return truthy-string|null
     */
    public function propPath(): ?string
    {
        $segmentPath = '';
        $nodePath = [];
        $getNodePath = function () use (&$nodePath): string {
            $path = implode('->', $nodePath);
            $nodePath = [];

            // wrap processing node path in curly braces to distinguish from property path segments
            return $path ? '{'.$path.'}' : '';
        };

        foreach ($this->propPathSegments as $segment) {
            if (\is_int($segment)) {
                $segmentPath .= $getNodePath()."[$segment]";
            } elseif ('#' === $segment[0]) {
                $nodePath[] = substr($segment, 1);
            } else {
                $segmentPath .= $getNodePath().".$segment";
            }
        }

        return ltrim($segmentPath.$getNodePath(), '.') ?: null;
    }

    /**
     * @var list<non-empty-string|array<non-empty-string, non-empty-string>>
     */
    public array $errorTemplateOverrides;

    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        BaseDto $dto,
        ProcessingErrorList $errorList,
        ErrorMode $errorMode,
        array $context = [],
    ) {
        $this->dto = $dto;
        $this->errorList = $errorList;
        $this->errorMode = $errorMode;
        $this->context = $context;
        $this->propPathSegments = [];
        $this->errorTemplateOverrides = [];
    }
}

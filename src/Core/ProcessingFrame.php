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

    /** @var array<int|non-empty-string> */
    public array $propPathSegments;

    /**
     * Build the dotted property path from the frame's property path segments.
     *
     * @return truthy-string|null
     */
    public function propPath(): ?string
    {
        $segmentPath = '';
        foreach ($this->propPathSegments as $segment) {
            $segmentPath .= is_int($segment) ? "[$segment]" : ".$segment";
        }

        return ltrim($segmentPath, '.') ?: null;
    }

    /**
     * @var list<non-empty-string|array<non-empty-string, non-empty-string>>
     */
    public array $errorTemplateOverrides;

    public function __construct(BaseDto $dto, ProcessingErrorList $errorList, ErrorMode $errorMode)
    {
        $this->dto = $dto;
        $this->errorList = $errorList;
        $this->errorMode = $errorMode;
        $this->propPathSegments = [];
        $this->errorTemplateOverrides = [];
    }
}

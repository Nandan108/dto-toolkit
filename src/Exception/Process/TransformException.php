<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Exception\Process;

final class TransformException extends ProcessingException
{
    /** @var non-empty-string */
    public const DOMAIN = 'transform';
}

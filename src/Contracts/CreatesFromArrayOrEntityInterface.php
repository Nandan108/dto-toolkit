<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Contracts;

use Nandan108\DtoToolkit\Core\ProcessingErrorList;
use Nandan108\DtoToolkit\Enum\ErrorMode;

/**
 * @method static static newFromArray(array $input, bool $ignoreUnknownProps = false, ?ProcessingErrorList $errorList = null, ?ErrorMode $errorMode = null)
 * @method static static newFromArrayLoose(array $input, bool $ignoreUnknownProps = false, ?ProcessingErrorList $errorList = null, ?ErrorMode $errorMode = null)
 * @method static static newFromEntity(object $entity, bool $ignoreInaccessibleProps = true, ?ProcessingErrorList $errorList = null, ?ErrorMode $errorMode = null)
 */
interface CreatesFromArrayOrEntityInterface
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public function loadArray(
        array $input,
        bool $ignoreUnknownProps = false,
        ?ProcessingErrorList $errorList = null,
        ?ErrorMode $errorMode = null,
        bool $clear = true,
    ): static;

    /** @psalm-suppress PossiblyUnusedMethod, PossiblyUnusedReturnValue */
    public function loadArrayLoose(
        array $input,
        ?ProcessingErrorList $errorList = null,
        ?ErrorMode $errorMode = null,
        bool $clear = true,
    ): static;

    /** @psalm-suppress PossiblyUnusedMethod */
    public function loadEntity(
        object $entity,
        bool $ignoreInaccessibleProps = true,
        ?ProcessingErrorList $errorList = null,
        ?ErrorMode $errorMode = null,
        bool $clear = true,
    ): static;
}

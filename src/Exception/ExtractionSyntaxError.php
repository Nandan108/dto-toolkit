<?php

namespace Nandan108\DtoToolkit\Exception;

use Nandan108\DtoToolkit\Contracts\DtoToolkitException;

/**
 * Exception thrown when there is a syntax error in the extraction path.
 */
final class ExtractionSyntaxError extends \InvalidArgumentException implements DtoToolkitException
{
}

<?php

namespace Nandan108\DtoToolkit\Exception;

use Nandan108\DtoToolkit\Contracts\DtoToolkitException;

/**
 * Exception thrown when there is an error during the loading process of a DTO.
 *
 * This exception is used to indicate that the loading of a Data Transfer Object (DTO)
 * has failed due to some issue, such as invalid data or a failure in the extraction process.
 */
final class LoadingException extends \RuntimeException implements DtoToolkitException
{
}

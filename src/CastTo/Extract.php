<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Contracts\HasContextInterface;
use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Exception\Config\ExtractionSyntaxError;
use Nandan108\DtoToolkit\Exception\Process\ExtractionException;
use Nandan108\PropPath\Exception\SyntaxError;
use Nandan108\PropPath\PropPath;
use Nandan108\PropPath\Support\ExtractContext;

/**
 * Extracts a nested value from an array/object structure using a dot-delimited path.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Extract extends CastBase
{
    /**
     * The extractor closure that will be used to extract the value from the data structure.
     *
     * @var \Closure(array, ?\Closure(string, ExtractContext): never): mixed
     */
    private \Closure $extractor;

    /** @var \Closure(string, ExtractContext):never */
    private \Closure $evalErrorHandler;

    public function __construct(string | array $paths)
    {
        parent::__construct(constructorArgs: [$paths]);

        try {
            $this->extractor = PropPath::compile($paths);
        } catch (SyntaxError $e) {
            /** @var string $jsonPath */
            $jsonPath = json_encode($paths, JSON_THROW_ON_ERROR);
            throw new ExtractionSyntaxError("Invalid path provided: $jsonPath.", previous: $e);
        }

        $this->evalErrorHandler = function (string $message, ExtractContext $context): never {
            throw ExtractionException::extractFailed(
                message: $message,
                context: $context,
                errorCode: 'transform.extract_failure',
            );
        };
    }

    #[\Override]
    public function cast(mixed $value, array $args): mixed
    {
        $dto = ProcessingContext::dto();
        $roots = ['value' => $value, 'dto'   => $dto];
        if ($dto instanceof HasContextInterface) {
            /** @psalm-var mixed */
            $roots['context'] = $dto->getContext();
        }

        return ($this->extractor)($roots, $this->evalErrorHandler);
    }
}

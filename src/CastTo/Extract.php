<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Contracts\HasContextInterface;
use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\CastingException;
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

    public function __construct(string|array $paths)
    {
        parent::__construct(constructorArgs: [$paths]);

        try {
            $this->extractor = PropPath::compile($paths);
        } catch (SyntaxError $e) {
            /** @var string $jsonPath */
            $jsonPath = json_encode($paths, JSON_THROW_ON_ERROR);
            throw new \InvalidArgumentException("Invalid path provided: $jsonPath.", previous: $e);
        }

        $this->evalErrorHandler = function (string $msg, ExtractContext $context): never {
            $errorMessage = $context->getEvalErrorMessage($msg);
            throw new CastingException($errorMessage, self::class, $context->roots['value']);
        };
    }

    #[\Override]
    public function cast(mixed $value, array $args): mixed
    {
        $dto = self::getCurrentDto();
        $roots = ['value' => $value, 'dto'   => $dto];
        if ($dto instanceof HasContextInterface) {
            /** @psalm-var mixed */
            $roots['context'] = $dto->getContext();
        }

        return ($this->extractor)($roots, $this->evalErrorHandler);
    }
}

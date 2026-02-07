<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Contracts\HasContextInterface;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Config\ExtractionSyntaxError;
use Nandan108\DtoToolkit\Exception\Process\ExtractionException;
use Nandan108\DtoToolkit\Exception\Process\GuardException;
use Nandan108\PropPath\Exception\SyntaxError;
use Nandan108\PropPath\PropPath;
use Nandan108\PropPath\Support\ExtractContext;

/**
 * Validates that a value compares to data extracted via prop-paths using the given operator.
 * Roots for $rightPath:
 * 1. 'dto' => the current DTO instance (default root)
 * 2. 'context' => the current context (if the DTO implements HasContextInterface).
 *
 * $leftPath is optional. If omitted, the validated value is used directly as the left operand.
 * Roots for $leftPath (when provided):
 * 1. 'value' => the value being validated (default root)
 * 2. 'dto' => the current DTO instance
 * 3. 'context' => the current context (if the DTO implements HasContextInterface)
 *
 * Examples:
 * - #[CompareToExtract('==', 'otherProperty')] // loose equality to otherProperty on the same DTO
 * - #[CompareToExtract('>', '$context.minValue')] // greater than minValue from context
 * - #[CompareToExtract(leftPath: 'age', op: '>=', rightPath: '$context.minAge')] // age extracted from the value being validated is >= minAge from context
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class CompareToExtract extends ValidatorBase
{
    /** @var \Closure(array, ?\Closure(string, ExtractContext): never): mixed */
    private \Closure $rightExtractor;

    /** @var \Closure(array, ?\Closure(string, ExtractContext): never)|null */
    private ?\Closure $leftExtractor;

    /** @var \Closure(string, ExtractContext):never */
    private \Closure $evalErrorHandler;

    /**
     * @param '=='|'==='|'!='|'!=='|'<'|'<='|'>'|'>=' $op
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function __construct(string $op, string $rightPath, ?string $leftPath = null)
    {
        parent::__construct(constructorArgs: [$op, $rightPath, $leftPath]);

        try {
            $this->rightExtractor = PropPath::compile($rightPath);
            $this->leftExtractor = null !== $leftPath ? PropPath::compile($leftPath) : null;
        } catch (SyntaxError $e) {
            // psalm gets confused by ternary, so keep as if-else
            if (isset($this->rightExtractor)) {
                // rightExtractor was fine, so the left must be bad
                $path = $leftPath;
            } else {
                $path = $rightPath;
            }
            $jsonPath = json_encode($path, JSON_THROW_ON_ERROR);
            throw new ExtractionSyntaxError("CompareToExtract: Invalid path provided: {$jsonPath}.", previous: $e);
        }

        $this->evalErrorHandler = function (string $message, ExtractContext $context): never {
            throw ExtractionException::extractFailed(
                message: $message,
                context: $context,
            );
        };
    }

    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        [$op, $rightPath, $leftPath] = $this->constructorArgs ?? [];

        $dto = ProcessingContext::dto();
        $roots = ['dto' => $dto];
        if ($dto instanceof HasContextInterface) {
            $roots['context'] = $dto->getContext();
        }

        $rightValue = ($this->rightExtractor)($roots, $this->evalErrorHandler);

        if (null === $this->leftExtractor) {
            $leftValue = $value;
        } else {
            $leftRoots = ['value' => $value, 'dto' => $dto];
            if ($dto instanceof HasContextInterface) {
                $leftRoots['context'] = $dto->getContext();
            }

            $leftValue = ($this->leftExtractor)($leftRoots, $this->evalErrorHandler);
        }

        $matches = CompareTo::compareValues(
            left: $leftValue,
            right: $rightValue,
            op: $op,
            leftIsValue: true,
            rightIsValue: true,
        );

        if (!$matches) {
            throw GuardException::invalidValue(
                value: $value,
                template_suffix: 'compare_to.failed',
                parameters: [
                    'operator'  => $op,
                    'leftPath'  => $leftPath,
                    'rightPath' => $rightPath,
                ],
                debug: [
                    'leftPath'  => $leftValue,
                    'rightPath' => $rightValue,
                ],
            );
        }
    }
}

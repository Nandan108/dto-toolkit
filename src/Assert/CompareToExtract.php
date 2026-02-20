<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Contracts\HasContextInterface;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Config\ExtractionSyntaxError;
use Nandan108\DtoToolkit\Exception\Process\ExtractionException;
use Nandan108\DtoToolkit\Exception\Process\GuardException;
use Nandan108\DtoToolkit\Internal\ValueComparator;
use Nandan108\PropPath\Exception\SyntaxError;
use Nandan108\PropPath\PropPath;
use Nandan108\PropPath\Support\EvaluationFailureDetails;

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
    /** @var \Closure(array, ?\Closure(string, EvaluationFailureDetails): never): mixed */
    private \Closure $rightExtractor;

    /** @var \Closure(array, ?\Closure(string, EvaluationFailureDetails): never)|null */
    private ?\Closure $leftExtractor;

    /** @var \Closure(string, EvaluationFailureDetails):never */
    private \Closure $evalErrorHandler;

    /**
     * @param '=='|'==='|'!='|'!=='|'<'|'<='|'>'|'>=' $op
     *
     * @psalm-suppress PossiblyUnusedMethod
     *
     * @api
     */
    public function __construct(string $op, string $rightPath, ?string $leftPath = null)
    {
        ValueComparator::assertOperator($op);

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

        $this->evalErrorHandler = function (string $message, EvaluationFailureDetails $failure): never {
            throw ExtractionException::extractFailed(
                message: $message,
                failure: $failure,
                errorCode: 'guard.compare_to.extract_failure',
            );
        };

        parent::__construct(constructorArgs: [$op, $rightPath, $leftPath]);
    }

    /**
     * Helper method to perform extraction using the given extractor closure and roots.
     * The roots array is prepared with the necessary data before calling the extractor.
     *
     * @param array<string, mixed> $roots
     */
    private function extract(\Closure $extractor, array $roots = []): mixed
    {
        // prepare roots for extraction closure
        $dto = ProcessingContext::dto();
        $roots['dto'] = $dto;
        if ($dto instanceof HasContextInterface) {
            $roots['context'] = $dto->getContext();
        }

        return $extractor($roots, $this->evalErrorHandler);
    }

    #[\Override]
    /** @internal */
    public function validate(mixed $value, array $args = []): void
    {
        /** @var array{0: non-empty-string, 1: non-empty-string, 2: ?non-empty-string} $args */
        $args = $this->constructorArgs;
        [$op, $rightPath, $leftPath] = $args;

        /** @psalm-var mixed */
        $rightValue = $this->extract($this->rightExtractor);
        /** @psalm-var mixed */
        $leftValue = (null === $this->leftExtractor)
            ? $value
            : $this->extract($this->leftExtractor, ['value' => $value]);

        $matches = ValueComparator::compare(
            left: $leftValue,
            right: $rightValue,
            op: $op,
            leftIsValue: true,
            rightIsValue: true,
        );

        if (!$matches) {
            throw GuardException::invalidValue(
                value: $value,
                template_suffix: 'compare_to_extract',
                parameters: [
                    'operator'  => $op,
                    'leftPath'  => $leftPath ?? '$value',
                    'rightPath' => $rightPath,
                ],
                debug: [
                    'leftPath'  => $leftValue,
                    'rightPath' => $rightValue,
                ],
                errorCode: 'guard.compare_to_extract',
            );
        }
    }
}

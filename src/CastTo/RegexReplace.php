<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Process\TransformException;
use RuntimeException as RtEx;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
/** @psalm-api */
final class RegexReplace extends CastBase
{
    /**
     * @param non-empty-string $pattern
     *
     * @throws InvalidArgumentException
     */
    public function __construct(public readonly string $pattern, public readonly string $replacement = '', public readonly int $limit = -1)
    {
        try {
            set_error_handler(function ($_, $msg): never { throw new RtEx($msg); }, E_WARNING);

            if (false === preg_match($pattern, '')) {
                throw new RtEx(preg_last_error_msg());
            }
        } catch (\Throwable $e) {
            throw new InvalidArgumentException(
                message: "Regex validator: invalid pattern /{$pattern}/",
                debug: ['error' => $e->getMessage()],
            );
        } finally {
            restore_error_handler();
        }

        parent::__construct([$pattern, $replacement, $limit]);

        // Force Psalm to acknowledge these properties are used
        [$this->pattern, $this->replacement, $this->limit];
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod, PossiblyUnusedParam, PossiblyUnusedReturnValue
     */
    #[\Override]
    public function cast(mixed $value, array $args): string
    {
        /** @psalm-suppress UnnecessaryVarAnnotation */
        /** @var array{0: non-empty-string, 1: string, 2: int} $args */
        [$pattern, $replacement, $limit] = $args;

        $value = $this->ensureStringable($value);

        try {
            set_error_handler(function ($_, $msg): never { throw new RtEx($msg); }, E_WARNING);

            $result = preg_replace($pattern, $replacement, $value, $limit);
            if (null === $result) {
                throw new RtEx(preg_last_error_msg());
            }
        } catch (\Throwable $e) {
            // since invalid patterns are already caught in the constructor, any error here must be due to the value or replacement
            // such as invalid UTF-8 sequences, replacement references to non-existent capture groups, etc.
            throw TransformException::reason(
                value: $value,
                template_suffix: 'regex.replace_failed',
                parameters: [
                    'pattern' => $pattern,
                    'error'   => $e->getMessage(),
                ],
                errorCode: 'transform.regex',
            );
        } finally {
            restore_error_handler();
        }

        return $result;
    }
}

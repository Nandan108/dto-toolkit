<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Process\GuardException;
use RuntimeException as RtEx;

// use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;

/**
 * Validates that a string matches (or does not match) a regex pattern.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Regex extends ValidatorBase
{
    /** @psalm-suppress PossiblyUnusedMethod
     *
     * @param non-empty-string $pattern
     *
     * @api
     */
    public function __construct(string $pattern, bool $negate = false)
    {
        /** @psalm-suppress TypeDoesNotContainType */
        if ('' === $pattern) {
            throw new InvalidArgumentException('Regex validator requires a pattern.');
        }

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

        parent::__construct([$pattern, $negate]);
    }

    #[\Override]
    /** @internal */
    public function validate(mixed $value, array $args = []): void
    {
        $value = $this->ensureStringable($value, false);

        /** @psalm-suppress UnnecessaryVarAnnotation */
        /** @var array{0: non-empty-string, 1: bool} $args */
        [$pattern, $negate] = $args;

        try {
            set_error_handler(function ($_, $msg): never { throw new RtEx($msg); }, E_WARNING);

            $matchedRaw = preg_match($pattern, $value);
            if (false === $matchedRaw) {
                throw new RtEx(preg_last_error_msg());
            }

            $matched = 1 === $matchedRaw;
        } catch (\Throwable $e) {
            throw GuardException::reason(
                value: $value,
                template_suffix: 'regex.matching_failed',
                parameters: ['pattern' => $pattern, 'error' => $e->getMessage()],
                errorCode: 'guard.regex',
            );
        } finally {
            restore_error_handler();
        }

        if (!$matched xor $negate) {
            throw GuardException::reason(
                value: $value,
                template_suffix: 'regex.'.($negate ? 'match_forbidden' : 'no_match'),
                parameters: ['pattern' => $pattern],
                errorCode: 'guard.regex',
            );
        }
    }
}

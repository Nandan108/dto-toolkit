<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Validate;

use Nandan108\DtoToolkit\Core\ValidateBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

// use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Regex extends ValidateBase
{
    /** @psalm-suppress PossiblyUnusedMethod
     *
     * @param non-empty-string $pattern
     */
    public function __construct(string $pattern, bool $negate = false)
    {
        /** @psalm-suppress TypeDoesNotContainType */
        if ('' === $pattern) {
            throw new InvalidArgumentException('Regex validator requires a pattern.');
        }

        @preg_match($pattern, '', $matches);

        $error = preg_last_error();
        if (\PREG_NO_ERROR !== $error) {
            $errorMessage = function_exists('preg_last_error_msg') ? preg_last_error_msg() : $error;
            throw new InvalidArgumentException("Regex validator: invalid pattern /{$pattern}/", ['error' => $errorMessage]);
        }

        parent::__construct([$pattern, $negate]);
    }

    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        $value = $this->ensureStringable($value, false);

        /** @psalm-suppress UnnecessaryVarAnnotation */
        /** @var array{0: non-empty-string, 1: bool} $args */
        [$pattern, $negate] = $args;

        $matched = 1 === preg_match($pattern, $value, $matches);
        if (!$matched xor $negate) {
            throw GuardException::reason(
                methodOrClass: static::class,
                value: $value,
                template_suffix: 'regex.'.($negate ? 'match_forbidden' : 'no_match'),
                parameters: ['pattern' => $pattern],
            );
        }
    }
}

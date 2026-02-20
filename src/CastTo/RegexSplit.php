<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Process\TransformException;
use RuntimeException as RtEx;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
/** @psalm-api */
final class RegexSplit extends CastBase
{
    /**
     * @param non-empty-string $pattern the regex pattern to split by
     * @param int<-1, max>     $limit   the maximum number of splits (0 or -1 for no limit)
     */
    public function __construct(string $pattern, int $limit = -1)
    {
        // Validate pattern and limit at construction time to fail fast on invalid config
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

        parent::__construct([$pattern, $limit]);
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod, PossiblyUnusedParam, PossiblyUnusedReturnValue
     */
    #[\Override]
    public function cast(mixed $value, array $args): array
    {
        /** @psalm-suppress UnnecessaryVarAnnotation  */
        /** @var array{0: non-empty-string, 1: int<-1, max>} $args */
        [$pattern, $limit] = $args;

        $value = $this->ensureStringable($value);

        try {
            set_error_handler(function ($_, $msg): never { throw new RtEx($msg); }, E_WARNING);

            $result = preg_split($pattern, $value, $limit);
            if (false === $result) {
                throw new RtEx(preg_last_error_msg());
            }

            return $result;
        } catch (\Throwable $e) {
            // since invalid patterns are already caught in the constructor, any error here must be due to
            // the value, such as invalid UTF-8 sequences, etc.
            throw TransformException::reason(
                value: $value,
                template_suffix: 'regex.split_failed',
                parameters: ['pattern' => $pattern, 'error' => $e->getMessage()],
                errorCode: 'transform.regex',
            );
        } finally {
            restore_error_handler();
        }
    }
}

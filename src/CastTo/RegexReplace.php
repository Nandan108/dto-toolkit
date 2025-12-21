<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\Process\TransformException;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
/** @psalm-api */
final class RegexReplace extends CastBase
{
    public function __construct(public readonly string $pattern, public readonly string $replacement = '', public readonly int $limit = -1)
    {
        parent::__construct([$pattern, $replacement, $limit]);

        // Force Psalm to acknowledge these properties are used
        [$this->pattern, $this->replacement, $this->limit];
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod, PossiblyUnusedParam
     */
    #[\Override]
    public function cast(mixed $value, array $args): string
    {
        [$pattern, $replacement, $limit] = $args;

        $value = $this->ensureStringable($value);

        /** @psalm-suppress InvalidArgument */
        $result = @preg_replace($pattern, $replacement, $value, $limit);

        if (\PREG_NO_ERROR !== preg_last_error()) {
            throw TransformException::reason(
                methodOrClass: static::class,
                value: $value,
                template_suffix: 'regex.replace_failed',
                parameters: [
                    'pattern' => $pattern,
                    'error'   => function_exists('preg_last_error_msg') ? preg_last_error_msg() : preg_last_error(),
                ],
            );
        }

        /** @var string */
        return $result;
    }
}

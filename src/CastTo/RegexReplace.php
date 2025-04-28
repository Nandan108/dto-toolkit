<?php

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Contracts\CasterInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\CastingException;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
/** @psalm-api */
final class RegexReplace extends CastBase implements CasterInterface
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
    public function cast(mixed $value, array $args, BaseDto $dto): string
    {
        [$pattern, $replacement, $limit] = $args;

        $value = $this->throwIfNotStringable($value);

        set_error_handler(function ($errno, $errstr) use ($value) {
            throw CastingException::castingFailure(className: static::class, operand: $value, messageOverride: "Regex error: $errstr");
        });

        try {
            return preg_replace($pattern, $replacement, $value, $limit) ?? '';
        } finally {
            restore_error_handler();
        }
    }
}

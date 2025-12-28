<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class NotBlank extends ValidatorBase
{
    /** @psalm-suppress PossiblyUnusedMethod */
    public function __construct(bool $trim = true)
    {
        parent::__construct([$trim]);
    }

    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        [$trim] = $args ?: $this->args;

        $value = $this->ensureStringable($value, false);

        $check = $trim ? trim($value) : $value;
        if ('' === $check) {
            throw GuardException::invalidValue(
                value: $value,
                template_suffix: 'not_blank',
                methodOrClass: self::class,
            );
        }
    }
}

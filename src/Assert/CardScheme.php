<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Process\GuardException;
use Nandan108\DtoToolkit\Support\CardSchemeDetector;

/**
 * Validates that a card number matches a given scheme.
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class CardScheme extends ValidatorBase
{
    /**
     * @param truthy-string|non-empty-list<truthy-string> $schemes
     */
    public function __construct(string | array $schemes)
    {
        $schemes = (array) $schemes;

        /** @psalm-suppress TypeDoesNotContainType */
        if ([] === $schemes) {
            // path needs test coverage
            throw new InvalidArgumentException('CardScheme validator requires at least one scheme.');
        }

        $normalized = [];
        foreach ($schemes as $scheme) {
            /** @psalm-suppress DocblockTypeContradiction */
            if (!$this->is_stringable($scheme)) {
                // path needs test coverage
                throw new InvalidArgumentException('CardScheme validator expects scheme names as strings.');
            }
            /** @psalm-suppress RedundantCastGivenDocblockType */
            $scheme = strtolower((string) $scheme);
            if (!CardSchemeDetector::isSupportedScheme($scheme)) {
                // path needs test coverage
                throw new InvalidArgumentException("CardScheme validator: unknown scheme '{$scheme}'.");
            }
            $normalized[] = $scheme;
        }

        parent::__construct([$normalized]);
    }

    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        /** @var non-empty-list<truthy-string> $schemes */
        $schemes = $args[0];

        $value = $this->ensureStringable($value, true);

        if (CardSchemeDetector::detectScheme($value, $schemes)) {
            return;
        }

        throw GuardException::invalidValue(
            value: $value,
            template_suffix: 'card_scheme',
            errorCode: 'guard.card_scheme',
        );
    }
}

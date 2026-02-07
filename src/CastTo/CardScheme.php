<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\CastTo;

use Nandan108\DtoToolkit\Core\CastBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Exception\Process\TransformException;
use Nandan108\DtoToolkit\Internal\ProcessingNodeBase;
use Nandan108\DtoToolkit\Support\CardSchemeDetector;

/**
 * Detects and returns the card scheme for a card number.
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class CardScheme extends CastBase
{
    /**
     * @param string|list<string>|null $schemes
     */
    public function __construct(string | array | null $schemes = null)
    {
        if (null !== $schemes) {
            $schemes = \is_array($schemes) ? $schemes : [$schemes];
            if ([] === $schemes) {
                throw new InvalidConfigException('CardScheme caster requires at least one scheme.');
            }

            $normalized = [];
            foreach ($schemes as $scheme) {
                if (!ProcessingNodeBase::is_stringable($scheme)) {
                    throw new InvalidConfigException('CardScheme caster: schemes must be strings.');
                }
                /** @psalm-suppress RedundantCastGivenDocblockType */
                $scheme = strtolower((string) $scheme);
                if (!CardSchemeDetector::isSupportedScheme($scheme)) {
                    throw new InvalidConfigException("CardScheme caster: unknown scheme '{$scheme}'.");
                }
                $normalized[] = $scheme;
            }

            parent::__construct([$normalized]);

            return;
        }

        parent::__construct([null]);
    }

    #[\Override]
    public function cast(mixed $value, array $args): string
    {
        /** @var list<truthy-string>|null $schemes */
        [$schemes] = $args;

        $value = $this->ensureStringable($value, true);

        $match = CardSchemeDetector::detectScheme($value, $schemes ?? []);

        if (null !== $match) {
            return $match;
        }

        throw TransformException::reason(
            value: $value,
            template_suffix: 'card_scheme.no_match',
            errorCode: 'card_scheme.no_match',
        );
    }
}

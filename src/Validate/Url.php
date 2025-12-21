<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Validate;

use Nandan108\DtoToolkit\Core\ValidateBase;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Url extends ValidateBase
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     *
     * @param non-empty-string|list<non-empty-string> $scheme  A string or array of allowed URL schemes (e.g., "http", "https", "ftp"). If empty array (default), all schemes are allowed.
     * @param list<"scheme"|"host"|"path"|"query">    $require An array indicating which URL parts are required ("scheme" | "host" | "path" | "query")
     *
     **/
    public function __construct(
        string | array $scheme = ['http', 'https'],
        array $require = ['scheme', 'host'],
    ) {
        if (is_string($scheme)) {
            $scheme = [$scheme];
        }
        if ($scheme && !in_array('scheme', $require, true)) {
            $require[] = 'scheme';
        }

        parent::__construct([$scheme, $require]);
    }

    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        /** @var string[] $allowedSchemes */
        $allowedSchemes = $args[0];
        /** @var list<"scheme"|"host"|"path"|"query"> $require */
        $require = $args[1];

        $value = $this->ensureStringable($value, true);

        $parsed = \parse_url($value);

        if (false === $parsed) {
            throw GuardException::invalidValue(
                value: $value,
                template_suffix: 'invalid_url',
                methodOrClass: __CLASS__,
            );
        }
        /** @var array<string, string> $parsed */

        // ---- Required parts ----

        foreach ($require as $part) {
            if (!isset($parsed[$part]) || '' === $parsed[$part]) {
                throw GuardException::invalidValue(
                    value: $value,
                    template_suffix: "url_missing_{$part}",
                    methodOrClass: __CLASS__,
                );
            }
        }

        if (\in_array('host', $require, true)) {
            $host = $parsed['host'] ?? null;
            /** @psalm-suppress RiskyTruthyFalsyComparison */
            if (null === $host || !filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
                throw GuardException::invalidValue(
                    value: $value,
                    template_suffix: 'url_invalid_host',
                    methodOrClass: __CLASS__,
                );
            }
        }

        // ---- Validate allowed schemes ----

        if ($allowedSchemes) {
            /** @var string[] $parsed */
            $scheme = strtolower($parsed['scheme']);
            if (!in_array($scheme, $allowedSchemes, true)) {
                throw GuardException::invalidValue(
                    value: $value,
                    template_suffix: 'invalid_url_scheme',
                    methodOrClass: __CLASS__,
                    parameters: [
                        'allowed_schemes' => implode(', ', $allowedSchemes),
                    ],
                );
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

/**
 * Validates that a value is a URL with optional scheme/host rules.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Url extends ValidatorBase
{
    /**
     * @psalm-suppress PossiblyUnusedMethod
     *
     * @param non-empty-string|list<non-empty-string> $scheme  A string or array of allowed URL schemes (e.g., "http", "https", "ftp"). If empty array (default), all schemes are allowed.
     * @param list<"scheme"|"host"|"path"|"query">    $require An array indicating which URL parts are required ("scheme" | "host" | "path" | "query")
     *
     **/
    /** @api */
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
    /** @internal */
    public function validate(mixed $value, array $args = []): void
    {
        /** @var string[] $allowedSchemes */
        $allowedSchemes = $args[0];
        /** @var list<"scheme"|"host"|"path"|"query"> $require */
        $require = $args[1];

        $value = $this->ensureStringable($value, true);

        $parsed = \parse_url($value);

        if (false === $parsed) {
            $this->throw($value, 'invalid_url');
        }
        /** @var array<string, string> $parsed */

        // ---- Required parts ----

        foreach ($require as $part) {
            if (!isset($parsed[$part]) || '' === $parsed[$part]) {
                $this->throw($value, "url_missing_{$part}");
            }
        }

        if (\in_array('host', $require, true)) {
            $host = $parsed['host'] ?? null;
            /** @psalm-suppress RiskyTruthyFalsyComparison */
            if (null === $host || !filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
                $this->throw($value, 'url_invalid_host');
            }
        }

        // ---- Validate allowed schemes ----

        if ($allowedSchemes) {
            /** @var string[] $parsed */
            $scheme = strtolower($parsed['scheme']);
            if (!in_array($scheme, $allowedSchemes, true)) {
                $this->throw($value, 'invalid_url_scheme', [
                    'allowed_schemes' => implode(', ', $allowedSchemes),
                ]);
            }
        }
    }

    /**
     * @param non-empty-string                    $reason
     * @param array<non-empty-string, string|int> $params
     */
    private function throw(mixed $value, string $reason, array $params = []): never
    {
        throw GuardException::reason(
            value: $value,
            template_suffix: $reason,
            errorCode: 'guard.url',
            parameters: $params,
        );
    }
}

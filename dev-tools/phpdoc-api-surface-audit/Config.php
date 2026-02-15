<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\DevTools\PhpDocApiSurfaceAudit;

final class Config
{
    /** @var list<string> */
    public array $paths;

    /** @var list<string> */
    public array $namespacePrefixes;

    /** @var list<string> */
    public array $allowedTags;

    /** @var list<string> */
    public array $excludePaths;

    /** @var list<string> */
    public array $excludeSymbols;

    public bool $strict;

    public string $projectRoot;

    private function __construct()
    {
    }

    public static function load(string $projectRoot, ?string $configPath): self
    {
        $resolved = $configPath ?? rtrim($projectRoot, '/').'/.phpdoc-api-surface-audit.php';
        if (!str_starts_with($resolved, '/')) {
            $resolved = rtrim($projectRoot, '/').'/'.$resolved;
        }

        if (!is_file($resolved)) {
            throw new \RuntimeException("Missing config file: {$resolved}");
        }

        $raw = require $resolved;
        if (!is_array($raw)) {
            throw new \RuntimeException('Config file must return an array.');
        }

        $cfg = new self();
        $cfg->projectRoot = $projectRoot;
        $cfg->paths = self::normalizeStringList($raw['paths'] ?? ['src'], 'paths');
        $cfg->namespacePrefixes = self::normalizeStringList($raw['namespace_prefixes'] ?? ['Nandan108\\DtoToolkit\\'], 'namespace_prefixes');
        $cfg->allowedTags = self::normalizeStringList(
            $raw['allowed_tags'] ?? ['@api', '@psalm-api', '@internal', '@psalm-internal', '@phpstan-internal'],
            'allowed_tags',
        );
        $cfg->excludePaths = self::normalizeStringList($raw['exclude_paths'] ?? [], 'exclude_paths');
        $cfg->excludeSymbols = self::normalizeStringList($raw['exclude_symbols'] ?? [], 'exclude_symbols');
        $cfg->strict = (bool) ($raw['strict'] ?? false);

        return $cfg;
    }

    /** @return list<string> */
    private static function normalizeStringList(mixed $value, string $key): array
    {
        if (!is_array($value)) {
            throw new \RuntimeException("Config `{$key}` must be an array of strings.");
        }

        $out = [];
        foreach ($value as $item) {
            if (!is_string($item) || '' === trim($item)) {
                throw new \RuntimeException("Config `{$key}` must be an array of non-empty strings.");
            }
            $out[] = $item;
        }

        return array_values(array_unique($out));
    }
}

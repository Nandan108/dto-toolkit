<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\DevTools\PhpDocApiSurfaceAudit;

final class Cli
{
    public static function run(array $argv, string $projectRoot): int
    {
        $options = self::parseOptions($argv);
        if ($options['help']) {
            self::printHelp();

            return 0;
        }

        try {
            $config = Config::load($projectRoot, $options['config']);
            $strict = $options['strict'] ?? $config->strict;
            $findings = (new Auditor())->audit($config);

            if ('json' === $options['format']) {
                echo json_encode([
                    'ok'       => [] === $findings,
                    'count'    => count($findings),
                    'findings' => $findings,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
            } else {
                if ([] === $findings) {
                    echo "OK: all public symbols are explicitly annotated with @api or @internal.\n";
                } else {
                    echo 'Public symbol annotation gaps ('.count($findings)."):\n";
                    foreach ($findings as $row) {
                        echo " - {$row}\n";
                    }
                }
            }

            return ($strict && [] !== $findings) ? 1 : 0;
        } catch (\Throwable $e) {
            fwrite(STDERR, "phpdoc-api-surface-audit failed: {$e->getMessage()}\n");

            return 2;
        }
    }

    /** @return array{help:bool,strict:?bool,config:?string,format:string} */
    private static function parseOptions(array $argv): array
    {
        $help = false;
        $strict = null;
        $config = null;
        $format = 'text';

        foreach (array_slice($argv, 1) as $arg) {
            if ('--help' === $arg || '-h' === $arg) {
                $help = true;
                continue;
            }
            if ('--strict' === $arg) {
                $strict = true;
                continue;
            }
            if (str_starts_with($arg, '--config=')) {
                $config = substr($arg, strlen('--config='));
                continue;
            }
            if (str_starts_with($arg, '--format=')) {
                $value = substr($arg, strlen('--format='));
                if (in_array($value, ['text', 'json'], true)) {
                    $format = $value;
                    continue;
                }
                throw new \InvalidArgumentException("Unsupported --format value: {$value}");
            }
            throw new \InvalidArgumentException("Unknown option: {$arg}");
        }

        return [
            'help'   => $help,
            'strict' => $strict,
            'config' => $config,
            'format' => $format,
        ];
    }

    private static function printHelp(): void
    {
        echo <<<TXT
phpdoc-api-surface-audit

Usage:
  php scripts/phpdoc-api-surface-audit [--config=FILE] [--strict] [--format=text|json]

Options:
  --config=FILE   Path to config PHP file returning array
  --strict        Fail with exit code 1 when findings exist
  --format=...    text (default) or json
  --help, -h      Show help
TXT;
        echo "\n";
    }
}

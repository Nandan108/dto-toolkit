<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Assert;

use Nandan108\DtoToolkit\Core\ValidatorBase;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Exception\Process\GuardException;

/**
 * Validates an IP address.
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Ip extends ValidatorBase
{
    public const V4 = '4';
    public const V6 = '6';
    public const ALL = 'all';
    public const V4_NO_PRIV = '4_no_priv';
    public const V6_NO_PRIV = '6_no_priv';
    public const ALL_NO_PRIV = 'all_no_priv';
    public const V4_NO_RES = '4_no_res';
    public const V6_NO_RES = '6_no_res';
    public const ALL_NO_RES = 'all_no_res';

    private const ALLOWED_VERSIONS = [
        self::V4,
        self::V6,
        self::ALL,
        self::V4_NO_PRIV,
        self::V6_NO_PRIV,
        self::ALL_NO_PRIV,
        self::V4_NO_RES,
        self::V6_NO_RES,
        self::ALL_NO_RES,
    ];

    /**
     * @param string|list<string> $version
     */
    public function __construct(string | array $version = self::ALL)
    {
        $versions = \is_array($version) ? $version : [$version];
        if ([] === $versions) {
            throw new InvalidConfigException('Ip validator requires at least one version.');
        }

        foreach ($versions as $v) {
            if (!\in_array($v, self::ALLOWED_VERSIONS, true)) {
                throw new InvalidConfigException("Ip validator: unknown version '{$v}'.");
            }
        }

        parent::__construct([$versions]);
    }

    #[\Override]
    public function validate(mixed $value, array $args = []): void
    {
        /** @var list<string> $versions */
        $versions = $args[0] ?? [];

        $value = $this->ensureStringable($value, true);

        foreach ($versions as $version) {
            $flags = self::flagsForVersion($version);
            if (false !== filter_var($value, FILTER_VALIDATE_IP, $flags)) {
                return;
            }
        }

        throw GuardException::invalidValue(
            value: $value,
            methodOrClass: self::class,
            template_suffix: 'ip.invalid',
            errorCode: 'validate.ip.invalid',
        );
    }

    private static function flagsForVersion(string $version): int
    {
        $flags = 0;

        if (str_starts_with($version, self::V4)) {
            $flags |= FILTER_FLAG_IPV4;
        } elseif (str_starts_with($version, self::V6)) {
            $flags |= FILTER_FLAG_IPV6;
        }

        if (str_contains($version, 'no_priv')) {
            $flags |= FILTER_FLAG_NO_PRIV_RANGE;
        }

        if (str_contains($version, 'no_res')) {
            $flags |= FILTER_FLAG_NO_RES_RANGE;
        }

        return $flags;
    }
}

<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Support;

/**
 * Detects card schemes based on number patterns.
 *
 * @api
 */
final class CardSchemeDetector
{
    public const AMEX = 'amex';
    public const CHINA_UNIONPAY = 'china_unionpay';
    public const DINERS = 'diners';
    public const DISCOVER = 'discover';
    public const INSTAPAYMENT = 'instapayment';
    public const JCB = 'jcb';
    public const LASER = 'laser';
    public const MAESTRO = 'maestro';
    public const MASTERCARD = 'mastercard';
    public const MIR = 'mir';
    public const TROY = 'troy';
    public const UATP = 'uatp';
    public const VISA = 'visa';

    /**
     * List of card schemes and their regex patterns.
     *
     * This list was lifted from Symfony's CardSchemeValidator, and the troy scheme added manually.
     * https://github.com/symfony/symfony/blob/8.0/src/Symfony/Component/Validator/Constraints/CardSchemeValidator.php
     *
     * @var array<truthy-string, list<truthy-string>>
     */
    private const SCHEMES = [
        // American Express card numbers start with 34 or 37 and have 15 digits.
        self::AMEX => [
            '/^3[47][0-9]{13}$/D',
        ],
        // China UnionPay cards start with 62 and have between 16 and 19 digits.
        // Please note that these cards do not follow Luhn Algorithm as a checksum.
        self::CHINA_UNIONPAY => [
            '/^62[0-9]{14,17}$/D',
        ],
        // Diners Club card numbers begin with 300 through 305, 36 or 38. All have 14 digits.
        // There are Diners Club cards that begin with 5 and have 16 digits.
        // These are a joint venture between Diners Club and MasterCard, and should be processed like a MasterCard.
        self::DINERS => [
            '/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/D',
        ],
        // Discover card numbers begin with 6011, 622126 through 622925, 644 through 649 or 65.
        // All have 16 digits.
        self::DISCOVER => [
            '/^6011[0-9]{12}$/D',
            '/^64[4-9][0-9]{13}$/D',
            '/^65[0-9]{14}$/D',
            '/^622(12[6-9]|1[3-9][0-9]|[2-8][0-9][0-9]|91[0-9]|92[0-5])[0-9]{10}$/D',
        ],
        // InstaPayment cards begin with 637 through 639 and have 16 digits.
        self::INSTAPAYMENT => [
            '/^63[7-9][0-9]{13}$/D',
        ],
        // JCB cards beginning with 2131 or 1800 have 15 digits.
        // JCB cards beginning with 35 have 16 digits.
        self::JCB => [
            '/^(?:2131|1800|35[0-9]{3})[0-9]{11}$/D',
        ],
        // Laser cards begin with either 6304, 6706, 6709 or 6771 and have between 16 and 19 digits.
        self::LASER => [
            '/^(6304|670[69]|6771)[0-9]{12,15}$/D',
        ],
        // Maestro international cards begin with 675900..675999 and have between 12 and 19 digits.
        // Maestro UK cards begin with either 500000..509999 or 560000..699999 and have between 12 and 19 digits.
        self::MAESTRO => [
            '/^(6759[0-9]{2})[0-9]{6,13}$/D',
            '/^(50[0-9]{4})[0-9]{6,13}$/D',
            '/^5[6-9][0-9]{10,17}$/D',
            '/^6[0-9]{11,18}$/D',
        ],
        // All MasterCard numbers start with the numbers 51 through 55. All have 16 digits.
        // October 2016 MasterCard numbers can also start with 222100 through 272099.
        self::MASTERCARD => [
            '/^5[1-5][0-9]{14}$/D',
            '/^2(22[1-9][0-9]{12}|2[3-9][0-9]{13}|[3-6][0-9]{14}|7[0-1][0-9]{13}|720[0-9]{12})$/D',
        ],
        // Payment system MIR numbers start with 220, then 1 digit from 0 to 4, then between 12 and 15 digits
        self::MIR => [
            '/^220[0-4][0-9]{12,15}$/D',
        ],
        // TROY cards begin with 9792 and have 16 digits.
        // This scheme was added manually (not part of Symfony 8.0)
        self::TROY => [
            '/^9792|65\d\d|36|2205\d{12}$/',
        ],
        // All UATP card numbers start with a 1 and have a length of 15 digits.
        self::UATP => [
            '/^1[0-9]{14}$/D',
        ],
        // All Visa card numbers start with a 4 and have a length of 13, 16, or 19 digits.
        self::VISA => [
            '/^4([0-9]{12}|[0-9]{15}|[0-9]{18})$/D',
        ],
    ];

    /** @return list<string> */
    public static function supportedSchemes(): array
    {

        return array_keys(self::SCHEMES);
    }

    public static function normalize(string $value): string
    {
        return preg_replace('/[\s-]+/', '', $value) ?? $value;
    }

    public static function isSupportedScheme(string $scheme): bool
    {
        return \array_key_exists($scheme, self::SCHEMES);
    }

    /**
     * Finds the first matching scheme for the given card number.
     *
     * @param array<truthy-string> $restrictTo
     *
     * @return ?truthy-string
     */
    public static function detectScheme(string $value, array $restrictTo = []): ?string
    {
        $normalized = self::normalize($value);
        if ([] !== $restrictTo) {
            $schemes = array_intersect_key(self::SCHEMES, array_flip($restrictTo));
        } else {
            $schemes = self::SCHEMES;
        }

        foreach ($schemes as $scheme => $patterns) {
            foreach ($patterns as $pattern) {
                if (1 === preg_match($pattern, $normalized)) {
                    return $scheme;
                }
            }
        }

        return null;
    }

    /**
     * Checks if the given card number matches the specified scheme.
     *
     * @param truthy-string $scheme
     */
    public static function matchesScheme(string $value, string $scheme): bool
    {
        return self::detectScheme($value, [$scheme]) === $scheme;
    }
}

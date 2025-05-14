<?php

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\CastTo;

trait UsesTimeZoneResolver
{
    use UsesParamResolver;

    /** @var array<string, \DateTimeZone|false> */
    protected static array $timezoneCache = [];

    public function configureTimezoneResolver(?string $timezoneOrProvider = null, string $paramName = 'timezone'): void
    {
        // checking with new DateTimeZone() + throwing exception is expensive, so we cache the result.

        /** @psalm-suppress UndefinedDocblockClass */
        /** @var CastTo|UsesParamResolver $this */
        $this->configureParamResolver(
            paramName: $paramName,
            valueOrProvider: $timezoneOrProvider ?? $this->constructorArgs[$paramName] ?? null,
            checkValid: function (?string $tz): bool {
                if (null === $tz || '' === $tz) {
                    return true;
                }

                // If we have already checked this timezone, return the cached result
                if (isset(static::$timezoneCache[$tz])) {
                    return false !== static::$timezoneCache[$tz];
                }
                try {
                    // making a new timezone object will throw an exception if $tz is invalid
                    static::$timezoneCache[$tz] = new \DateTimeZone($tz);

                    // no exception means the timezone is valid
                    return true;
                } catch (\Exception $e) {
                    return static::$timezoneCache[$tz] = false;
                }
            },
            // Default value for output timeone is input timezone
            // Since this trait may be used in both directions
            fallback: fn (string|\DateTimeInterface $value): ?string => $value instanceof \DateTimeInterface
                ? $value->getTimezone()->getName() // DateTimeInterface -> string: keep same timezone
                : null, // string -> DateTimeInterface: Don't change timezone
            // hydrate function to convert the timezone string to a DateTimeZone object
            // we can use the cached value from the checkValid function, since checkValid() is always
            // called before hydrate()
            /** @psalm-suppress InvalidFalsableReturnType */
            hydrate: fn (?string $tz): ?\DateTimeZone => (null === $tz || '' === $tz) ? null : (static::$timezoneCache[$tz] ?: null),
        );
    }
}

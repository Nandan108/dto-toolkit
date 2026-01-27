<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\HasContextInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Exception\Config\InvalidArgumentException;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Exception\Context\ContextException;

/**
 * This trait provides a way to resolve caster parameters for DTOs.
 *
 * It allows you to configure a parameter resolver for a DTO, and then resolve the parameter at cast time.
 *
 * @psalm-suppress UndefinedDocblockClass
 */
trait UsesParamResolver
{
    /** @var \WeakMap<BaseDto, ParamResolverConfig[]> */
    protected static ?\WeakMap $paramProvidersMap = null;

    protected function getParamResolverConfig(string $paramName): ParamResolverConfig
    {
        // Can't use this trait without being a CastTo
        // $this instanceof CastTo or throw new InvalidConfigException('Caster must implement CastTo to use UsesLocaleResolver');
        $dto = ProcessingContext::dto();

        if (!isset(static::$paramProvidersMap) || !isset(static::$paramProvidersMap[$dto])) {
            throw new InvalidConfigException('configureParamResolver() must be called before resolveParamProvider()');
        }
        /** @var array<string, ParamResolverConfig> $resolverConfigs */
        $resolverConfigs = static::$paramProvidersMap[$dto];

        $config = $resolverConfigs[$paramName] ?? null;

        // Config should be set up in the bootOnDto() method. If it's not, something went wrong - throw an exception.
        $config or throw new InvalidConfigException("Parameter '$paramName' has not been configured by configureParamResolver().");

        return $config;
    }

    /**
     * This resolves the required provider. It should be called in the constructor.
     *
     * @throws \InvalidArgumentException
     * @throws InvalidConfigException
     *
     * @psalm-suppress UndefinedDocblockClass
     */
    protected function resolveParamProvider(ParamResolverConfig $config, ?string $paramValueOrProviderClass, BaseDto $dto): \Closure
    {
        // $config = $this->getParamResolverConfig($paramName);

        /** @var \Closure|null $provider */
        /** @psalm-suppress UnsupportedPropertyReferenceUsage,PossiblyNullArrayOffset */
        $provider = &$config->providers[$paramValueOrProviderClass] ?? null;
        if ($provider) {
            return $provider;
        }

        $paramName = $config->paramName;
        $defaultParamGetter = 'get'.ucfirst($paramName);

        $attemptJsonDecode = function (?string $value): mixed {
            if (null === $value) {
                return null;
            }
            $decoded = json_decode($value, true);
            if (JSON_ERROR_NONE !== json_last_error()) {
                return $value;
            }

            return $decoded;
        };

        // A value was provided
        if (null !== $paramValueOrProviderClass) {
            // 1. Declared DTO source ? Return a provider that calls $dto->get$paramName()
            if (preg_match('/^<dto(:([a-zA-Z0-9_]+)(?::(.*))?)?$/', $paramValueOrProviderClass, $matches)) {
                $paramGetter = $matches[2] ?? $defaultParamGetter;
                $extraParam = $attemptJsonDecode($matches[3] ?? null);

                if (!method_exists($dto, $paramGetter)) {
                    throw new InvalidConfigException(
                        "DTO does not have a $paramGetter() method.",
                        ['dtoClass' => get_class($dto), 'paramGetter' => $paramGetter],
                    );
                }

                return $provider = function (mixed $value, ?string $prop, BaseDto $dto) use ($paramGetter, $extraParam): mixed {
                    return [$dto, $paramGetter]($value, $prop, $extraParam);
                };
            }

            // 2. Declared context source ? Return a provider that calls $dto->getContext($paramName) and returns the value (context-val) cast as a boolean.
            // If a css selector is added (e.g. "<context:myKeyName~=someVal"), return the selector's result rather than (bool)context-val.
            if (preg_match('/^<context(?::([-.a-z0-9_]+)(?:=([^=].*))?)?$/ix', $paramValueOrProviderClass, $matches)) {
                $contextKey = $matches[1] ?? $paramName;
                $compareVal = $matches[2] ?? null;

                // Determine if it's a regex comparison
                $checkClosure = null;

                if (null !== $compareVal) {
                    if (preg_match('#^/(.*)/([a-z]*)$#', $compareVal, $regexParts)) {
                        [, $pattern, $flags] = $regexParts;
                        $delimitedPattern = "/$pattern/$flags";
                        $checkClosure = fn (mixed $value): bool => is_string($value) && 1 === preg_match($delimitedPattern, $value);
                    } else {
                        $checkClosure = fn (mixed $value): bool => $value === $compareVal;
                    }
                }

                // if the value is '<context', return a provider that gets the value from the context
                if (!$dto instanceof HasContextInterface) {
                    throw new InvalidConfigException("To use '<context' as a parameter value, the DTO must implement HasContextInterface.");
                }

                if (!$dto->contextHas($contextKey)) {
                    throw new InvalidConfigException("Cannot resolve context key '$contextKey' (no context set) for caster ".static::class);
                }

                return $provider = function (mixed $value, ?string $prop, BaseDto & HasContextInterface $dto) use ($contextKey, $checkClosure, $paramName, $config): mixed {
                    // attempt to get the parameter value from the context
                    $paramValue = $dto->contextGet($contextKey);

                    if (null !== $paramValue && null !== $checkClosure) {
                        $paramValue = $checkClosure($paramValue);
                    }

                    if ($config->checkValid && !($config->checkValid)($paramValue, $paramName)) {
                        // if the value is not valid, throw an exception
                        /** @psalm-suppress RiskyTruthyFalsyComparison */
                        $paramValue = json_encode($paramValue) ?: '(type: '.get_debug_type($paramValue).')';
                        throw new InvalidConfigException("Prop $prop: Invalid $paramName $paramValue in context key '$contextKey' for ".static::class);
                    }

                    return $paramValue;
                };
            }

            // 3. It's a valid provider class ? Return a provider that static-calls it.
            [$classProvider, $paramGetter] = explode('::', $paramValueOrProviderClass, 2) + [null, $defaultParamGetter];
            if (class_exists($classProvider)) {
                if (method_exists($classProvider, $paramGetter)) {
                    // if the class has a method that matches the getter, use it
                    return $provider = fn (mixed $value, ?string $prop): mixed => [$classProvider, $paramGetter]($value, $prop, $dto);
                }
                throw new InvalidArgumentException("Class $classProvider does not have a $paramGetter() method.");
            }

            // 4. If we have a validity checker and the value passes
            if ($config->checkValid && ($config->checkValid)($paramValueOrProviderClass, $paramName)) {
                // if the value directly provided is valid, return a provider that returns this value.
                return $provider = fn () => $paramValueOrProviderClass;
            }

            throw new InvalidConfigException("Cannot resolve $paramName from \"$paramValueOrProviderClass\" for ".static::class);
        }

        // Null value provided or non-null but not early-resolvable?
        // Return a late-binding provider that runs at cast time, and checks in order:
        // 1. $dto->get$paramName(), 2. $dto->getContext($paramName), 3. fallback provider.
        return $provider = function (mixed $value, ?string $prop, BaseDto $dto) use ($paramName, $defaultParamGetter, $config): mixed {
            // initializing $paramValue to keep psalm happy
            $paramValue = null;

            if ($dto instanceof HasContextInterface && $dto->contextHas($paramName)) {
                // attempt to get the parameter value from the context
                $paramValue = $dto->contextGet($paramName);
                $source = "\$dto->getContext('$paramName')";
            } elseif (method_exists($dto, $defaultParamGetter)) {
                // attempt resolution via $dto->{"get$paramName"}()
                $paramValue = [$dto, $defaultParamGetter]($value, $prop);
                $source = "\$dto->$defaultParamGetter()";
            } elseif (isset($config->fallback)) {
                $paramValue = ($config->fallback)($value, $dto);
                $source = 'fallback provider';
            }

            // this syntax is used to allow full coverage without the incovenience of having to test the sad path separately
            isset($source) or throw new InvalidConfigException("No value or provider given, unable to resolve $paramName for ".static::class);

            // if checkValid is set, check the value and throw if invalid
            if ($config->checkValid && !($config->checkValid)($paramValue, $paramName)) {
                /** @psalm-suppress RiskyTruthyFalsyComparison */
                $jsonVal = json_encode($paramValue) ?: '(type: '.get_debug_type($paramValue).')';
                throw new InvalidConfigException(
                    __METHOD__.": $source returned an invalid value $jsonVal",
                    ['params' => $paramName, 'paramValue' => $paramValue, 'source' => $source],
                );
            }

            return $paramValue;
        };
    }

    protected function configureParamResolver(string $paramName, mixed $valueOrProvider, ?\Closure $checkValid = null, ?\Closure $hydrate = null, ?\Closure $fallback = null): void
    {
        $dto = ProcessingContext::dto();

        // Can't use ??= here, because psalm complains about type coersion
        if (null === static::$paramProvidersMap) {
            /** @var \WeakMap<BaseDto, ParamResolverConfig[]> */
            static::$paramProvidersMap = new \WeakMap();
        }
        // initialize the map for this DTO if it doesn't exist
        if (!isset(static::$paramProvidersMap[$dto])) {
            static::$paramProvidersMap[$dto] = [];
        }

        $config = new ParamResolverConfig($paramName, $checkValid, $fallback, $hydrate);

        /** @psalm-suppress InvalidArgument */
        static::$paramProvidersMap[$dto][$paramName] = $config;

        $this->resolveParamProvider($config, $valueOrProvider, $dto);
    }

    /**
     * returns the locale for the given value, locale provider, and context (propName, dto).
     *
     * @throws InvalidConfigException
     *
     * @psalm-suppress UndefinedDocblockClass
     */
    protected function resolveParam(string $paramName, mixed $value, ?string $paramValueOrProviderClass = null): mixed
    {
        $config = $this->getParamResolverConfig($paramName);

        // if no $paramValueOrProviderClass is passed, check for a constructorArg with the same name as the parameter to resolve
        $paramValueOrProviderClass ??= $this->constructorArgs[$paramName] ?? null;

        $dto = ProcessingContext::dto();

        /** @var CastTo|UsesParamResolver $this */
        $provider = $this->resolveParamProvider($config, $paramValueOrProviderClass, $dto);

        $paramValue = $provider($value, $this->getCurrentPropName(), $dto);

        $hydrate = $config->hydrate;

        return $hydrate ? $hydrate($paramValue) : $paramValue;
    }

    /**
     * Returns the current property name from the ProcessingContext.
     *
     * @return non-empty-string
     *
     * @throws ContextException
     */
    private function getCurrentPropName(): string
    {
        $segment = ProcessingContext::current()->propPathSegments[0] ?? null;
        if (null === $segment) {
            throw new ContextException('Out-of-context call: ProcessingContext prop path is not set.');
        }

        return (string) $segment;
    }
}

/**
 * @internal Internal class to hold the configuration for a parameter resolver.
 * This class is not intended to be used directly by users of the library.
 */
final class ParamResolverConfig
{
    /** @var \Closure[] */
    public array $providers = [];

    public function __construct(
        public string $paramName,
        public ?\Closure $checkValid,
        public ?\Closure $fallback,
        public ?\Closure $hydrate,
    ) {
    }
}

<?php

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\CastTo;
use Nandan108\DtoToolkit\Contracts\HasContextInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Exception\CastingException;

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
        $this instanceof CastTo or throw new \RuntimeException('Caster must implement CastTo to use UsesLocaleResolver');
        $dto = static::getCurrentDto();

        if (!isset(static::$paramProvidersMap) || !isset(static::$paramProvidersMap[$dto])) {
            throw new \RuntimeException('Please call configureParamResolver() before resolveParamProvider()');
        }
        /** @var array<string, ParamResolverConfig> $resolverConfigs */
        $resolverConfigs = static::$paramProvidersMap[$dto];

        $config = $resolverConfigs[$paramName] ?? null;

        // Config should be set up in the bootOnDto() method. If it's not, something went wrong - throw an exception.
        $config or throw new \RuntimeException("Parameter '$paramName' has not been configured by configureParamResolver().");

        return $config;
    }

    /**
     * This resolves the required provider. It should be called in the constructor.
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     *
     * @psalm-suppress UndefinedDocblockClass
     */
    protected function resolveParamProvider(ParamResolverConfig $config, ?string $paramValueOrProviderClass, BaseDto $dto): \Closure
    {
        // $config = $this->getParamResolverConfig($paramName);

        /** @var \Closure|null $provider */
        /** @psalm-suppress UnsupportedPropertyReferenceUsage */
        $provider = &$config->providers[$paramValueOrProviderClass] ?? null;
        if ($provider) {
            return $provider;
        }

        $paramName = $config->paramName;
        $paramGetter = 'get'.ucfirst($paramName);

        // 1. Declared DTO source ? Return a provider that calls $dto->get$paramName()
        if ('<dto' === $paramValueOrProviderClass) {
            if (!method_exists($dto, $paramGetter)) {
                throw new \BadMethodCallException("DTO does not have a $paramGetter() method.");
            }

            return $provider = function (mixed $value, ?string $prop, BaseDto $dto) use ($paramGetter): mixed {
                return [$dto, $paramGetter]($value, $prop);
            };
        }

        // 2. Declared context source ? Return a provider that calls $dto->getContext($paramName)
        if ('<context' === $paramValueOrProviderClass) {
            // if the value is '<context', return a provider that gets the value from the context
            if (!$dto instanceof HasContextInterface) {
                throw new \RuntimeException("To use '<context' as a parameter value, the DTO must implement HasContextInterface.");
            }
            if (!$dto->hasContext($paramName)) {
                throw new \RuntimeException("Cannot resolve $paramName (no context set) for caster ".static::class);
            }

            return $provider = function (mixed $value, ?string $prop, BaseDto&HasContextInterface $dto) use ($paramName, $config): mixed {
                // attempt to get the parameter value from the context

                $paramValue = $dto->getContext($paramName);
                if (!($config->checkValid)($paramValue, $paramName)) {
                    // if the value is not valid, throw an exception
                    /** @psalm-suppress RiskyTruthyFalsyComparison */
                    $paramValue = json_encode($paramValue) ?: '(type: '.get_debug_type($paramValue).')';
                    throw new \RuntimeException("Invalid $paramName $paramValue in context for ".static::class);
                }

                return $paramValue;
            };
        }

        // 3. Valid value directly provided ? Return a provider that returns this value.
        if (null !== $paramValueOrProviderClass && ($config->checkValid)($paramValueOrProviderClass, $paramName)) {
            return $provider = fn (): mixed => $paramValueOrProviderClass;
        }

        // 4. Valid provider class provided ? Return a provider that static-calls it.
        if (null !== $paramValueOrProviderClass) {
            if (class_exists($paramValueOrProviderClass)) {
                if (method_exists($paramValueOrProviderClass, $paramGetter)) {
                    // if the class has a method that matches the getter, use it
                    return $provider = fn (mixed $value, ?string $prop): mixed => [$paramValueOrProviderClass, $paramGetter]($value, $prop, $dto);
                }
                throw new \InvalidArgumentException("Class $paramValueOrProviderClass does not have a $paramGetter() method.");
            }
            throw new \RuntimeException("Cannot resolve $paramName from \"$paramValueOrProviderClass\" for ".static::class);
        }

        // Null value provided ? Return a late-binding provider that runs at cast time, and checks in order:
        // 1. $dto->get$paramName(), 2. $dto->getContext($paramName), 3. fallback provider.
        return $provider = function (mixed $value, ?string $prop, BaseDto $dto) use ($paramName, $paramGetter, $config): mixed {
            // initializing $paramValue to keep psalm happy
            $paramValue = null;

            if ($dto instanceof HasContextInterface && $dto->hasContext($paramName)) {
                // attempt to get the parameter value from the context
                $paramValue = $dto->getContext($paramName);
                $source = "\$dto->getContext('$paramName')";
            } elseif (method_exists($dto, $paramGetter)) {
                // attempt resolution via $dto->{"get$paramName"}()
                $paramValue = [$dto, $paramGetter]($value, $prop);
                $source = "\$dto->$paramGetter()";
            } elseif (isset($config->fallback)) {
                $paramValue = ($config->fallback)($value, $dto);
                $source = 'fallback provider';
            }

            // this syntax is used to allow full coverage without the incovenience of having to test the sad path separately
            isset($source) or throw new \RuntimeException("No value or provider given, unable to resolve $paramName for ".static::class);

            if (($config->checkValid)($paramValue, $paramName)) {
                return $paramValue;
            }

            // if the value is not valid, throw an exception
            /** @psalm-suppress RiskyTruthyFalsyComparison */
            $paramValue = json_encode($paramValue) ?: '';
            throw CastingException::castingFailure(static::class, $paramValue, messageOverride: "$source returned an invalid value $paramValue for ".static::class);
        };
    }

    protected function configureParamResolver(string $paramName, mixed $valueOrProvider, \Closure $checkValid, ?\Closure $hydrate = null, ?\Closure $fallback = null): void
    {
        $dto = static::getCurrentDto();

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
     * @throws \RuntimeException
     *
     * @psalm-suppress UndefinedDocblockClass
     */
    protected function resolveParam(string $paramName, mixed $value, ?string $localeOrProviderClass = null): mixed
    {
        $config = $this->getParamResolverConfig($paramName);

        // if no $localeOrProviderClass is passed, check for a constructorArg with the same name as the parameter to resolve
        $localeOrProviderClass ??= $this->constructorArgs[$paramName] ?? null;

        /** @var CastTo|UsesParamResolver $this */
        $dto = static::$currentDto;
        $dto or throw new \RuntimeException('Cannot resolve parameter without a DTO (CastTo::$currentDto is null)');

        $provider = $this->resolveParamProvider($config, $localeOrProviderClass, $dto);

        $paramValue = $provider($value, static::$currentPropName, $dto);

        $hydrate = $config->hydrate;

        return $hydrate ? $hydrate($paramValue) : $paramValue;
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
        public \Closure $checkValid,
        public ?\Closure $fallback,
        public ?\Closure $hydrate,
    ) {
    }
}

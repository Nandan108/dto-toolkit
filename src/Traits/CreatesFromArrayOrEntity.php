<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\Attribute\MapFrom;
use Nandan108\DtoToolkit\Contracts\ProcessesInterface;
use Nandan108\DtoToolkit\Contracts\ScopedPropertyAccessInterface;
use Nandan108\DtoToolkit\Core\ProcessingContext;
use Nandan108\DtoToolkit\Core\ProcessingErrorList;
use Nandan108\DtoToolkit\Enum\ErrorMode;
use Nandan108\DtoToolkit\Enum\Phase;
use Nandan108\DtoToolkit\Enum\PresencePolicy;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;
use Nandan108\DtoToolkit\Exception\Process\ExtractionException;
use Nandan108\PropAccess\PropAccess;

/**
 * @method static static newFromArray(array $input, bool $ignoreUnknownProps = false, ?ProcessingErrorList $errorList = null, ?ErrorMode $errorMode = null)
 * @method static static newFromArrayLoose(array $input, bool $ignoreUnknownProps = false, ?ProcessingErrorList $errorList = null, ?ErrorMode $errorMode = null)
 * @method static static newFromEntity(object $entity, bool $ignoreInaccessibleProps = true, ?ProcessingErrorList $errorList = null, ?ErrorMode $errorMode = null)
 *
 * These methods are dynamically routed via __callStatic() to their corresponding instance methods.
 * Static analyzers require the above annotations to avoid false positives.
 **/
trait CreatesFromArrayOrEntity
{
    /**
     * Create a new instance of the DTO from an array.
     *
     * @param bool $ignoreUnknownProps If true, unknown properties will be ignored
     *
     * @throws InvalidConfigException
     *
     * @psalm-suppress MethodSignatureMismatch, MoreSpecificReturnType
     */
    public function loadArray(
        array $input,
        bool $ignoreUnknownProps = false,
        ?ProcessingErrorList $errorList = null,
        ?ErrorMode $errorMode = null,
        bool $clear = true,
    ): static {
        $errorList and $this->setErrorList($errorList);

        $callback = function () use ($input, $ignoreUnknownProps, $clear): mixed {
            // check for unknown properties
            if (!$ignoreUnknownProps) {
                $unknownProperties = array_diff(array_keys($input), $this->getFillable());
                if ($unknownProperties) {
                    throw new InvalidConfigException('Unknown properties: '.implode(', ', $unknownProperties));
                }
            }

            // determine the input to be filled
            $inputToBeFilled = $this->getInputToBeFilled($input);

            // and fill the DTO
            $this->fill($inputToBeFilled);

            // reset unfilled properties to default values
            if ($clear) {
                $this->clear(excludedPropsMap: $inputToBeFilled, clearErrors: false);
            }

            // cast the values to their respective types and return the DTO
            if ($this instanceof ProcessesInterface) {
                /** @psalm-suppress UnusedMethodCall */
                $this->processInbound();
            }

            // call post-load hook
            /** @psalm-suppress UndefinedMethod */
            $this->postLoad();

            return $this;
        };

        return ProcessingContext::wrapProcessing($this, $callback, $errorMode);
    }

    protected function getInputToBeFilled(array $input): array
    {
        /** @psalm-suppress InaccessibleMethod */
        $fillables = $this->getFillable();

        // If needed, restrict properties to current scope
        if ($this instanceof ScopedPropertyAccessInterface) {
            $propsInScope = $this->getPropertiesInScope(Phase::InboundLoad);
            $toBeFilled = array_intersect($fillables, $propsInScope);
        } else {
            $toBeFilled = $fillables;
        }

        $presencePolicy = static::getPresencePolicy();
        $defaultValues = static::getDefaultValues();

        // get mappers ($[MapFrom] attribute) for the properties to be filled
        $mappers = MapFrom::getMappers($this, $toBeFilled);

        $inputToBeFilled = array_intersect_key($input, array_flip($toBeFilled));

        // PresencePolicy determine which props are marked as "filled" :
        // Default:             any prop with a value present in input, including NULL values.
        // NullMeansMissing:    Same as Default, NULL values excluded (treated as missing)
        // MissingMeansDefault: Always marked as filled, even if missing in input (gets default value)

        // Get data from mappers. Only fill if we actually get a value.
        foreach ($presencePolicy as $prop => $policy) {
            if ($mapper = $mappers[$prop] ?? null) {
                try {
                    // get value from mapper
                    $inputToBeFilled[$prop] = $mapper($input, $this);
                } catch (ExtractionException $e) {
                    // Currently, extraction failures will be treated as missing input, and #[MapFrom(path)] fails
                    // immediately (by default) on missing input, even as part of a multi-path expression.
                    // This can be adjusted via ThrowMode passed to MapFrom attribute: #[MapFrom(paths, ThrowMode::NEVER)],
                    // but in this case, we lose distinction between "missing input" vs. "null input", which is not ideal.
                    // TODO: refine prop-path exception handling, so that we can
                    // distinguish between "missing input" vs. "null input" and handle accordingly.
                    unset($inputToBeFilled[$prop]);
                    continue;
                }
            }
            // handle MissingMeansDefault policy
            if (!array_key_exists($prop, $inputToBeFilled)) {
                if (PresencePolicy::MissingMeansDefault === $policy) {
                    $inputToBeFilled[$prop] = $defaultValues[$prop];
                    continue;
                }
            }
            // handle NullMeansMissing policy
            elseif (null === $inputToBeFilled[$prop]) {
                if (PresencePolicy::NullMeansMissing === $policy) {
                    unset($inputToBeFilled[$prop]);
                }
            }
        }

        return $inputToBeFilled;
    }

    /**
     * Create a new instance of the DTO from a request, ignoring unknown properties.
     *
     * @throws InvalidConfigException
     *
     * @psalm-suppress PossiblyUnusedMethod, PossiblyUnusedReturnValue, MethodSignatureMismatch
     */
    public function loadArrayLoose(
        array $input,
        ?ProcessingErrorList $errorList = null,
        ?ErrorMode $errorMode = null,
        bool $clear = true,
    ): static {
        /** @psalm-suppress NoValue */
        return $this->loadArray($input, true, $errorList, $errorMode, $clear);
    }

    /**
     * @psalm-suppress PossiblyUnusedMethod, MethodSignatureMismatch
     */
    public function loadEntity(
        object $entity,
        bool $ignoreInaccessibleProps = true,
        ?ProcessingErrorList $errorList = null,
        ?ErrorMode $errorMode = null,
        bool $clear = true,
    ): static {
        /** @var array */
        $inputData = PropAccess::getValueMap(
            valueSource: $entity,
            propNames: $this->getFillable(),
            ignoreInaccessibleProps: $ignoreInaccessibleProps,
        );

        return $this->loadArray($inputData, true, $errorList, $errorMode, $clear);
    }
}

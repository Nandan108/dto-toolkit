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
 *
 * @api
 */
trait CreatesFromArrayOrEntity
{
    /**
     * Create a new instance of the DTO from an array.
     *
     * @param array                    $input              The input array to load into the DTO
     * @param bool                     $ignoreUnknownProps If false, unknown properties will cause an exception, otherwise they will be ignored
     * @param ProcessingErrorList|null $errorList          Optional error list to collect processing errors
     * @param ErrorMode|null           $errorMode          Optional policy that determines whether to fail fast or collect, and
     *                                                     if collecting, which values to set for properties that fail processing (null, input value, or omit entirely)
     * @param bool                     $clear              If true, unfilled properties will be reset to their default values
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
        $errorList && $this->setErrorList($errorList);

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

    /**
     * This method determines which input values should be used to fill the DTO properties,
     * based on the fillable properties, presence policy, default values, and any mappers defined via #[MapFrom].
     *
     * @return array<truthy-string, mixed>
     */
    protected function getInputToBeFilled(array $input): array
    {
        /** @psalm-suppress InaccessibleMethod */
        $fillables = $this->getFillable();

        // If needed, restrict properties to current scope
        /** @var list<truthy-string> $toBeFilled */
        $toBeFilled = $this instanceof ScopedPropertyAccessInterface
            ? array_intersect($fillables, $this->getPropertiesInScope(Phase::InboundLoad))
            : $fillables;

        $presencePolicy = static::getPresencePolicy();
        $defaultValues = static::getDefaultValues();

        // get mappers ($[MapFrom] attribute) for the properties to be filled
        /** @var array<truthy-string, MapFrom> $mappers */
        $mappers = MapFrom::getMappers($this, $toBeFilled);

        /** @var array<truthy-string, mixed> $inputToBeFilled */
        $inputToBeFilled = array_intersect_key($input, array_flip($toBeFilled));

        // PresencePolicy determine which props are marked as "filled" :
        // Default:             any prop with a value present in input, including NULL values.
        // NullMeansMissing:    Same as Default, NULL values excluded (treated as missing)
        // MissingMeansDefault: Always marked as filled, even if missing in input (gets default value)

        // Get data from mappers. Only fill if we actually get a value.
        foreach ($presencePolicy as $prop => $policy) {
            if ($mapper = $mappers[$prop] ?? false) {
                try {
                    // get value from mapper
                    /** @psalm-var mixed */
                    $inputToBeFilled[$prop] = $mapper($input, $this);
                } catch (ExtractionException $e) {
                    unset($inputToBeFilled[$prop]);
                    continue;
                }
            }
            // handle MissingMeansDefault policy
            if (!array_key_exists($prop, $inputToBeFilled)) {
                if (PresencePolicy::MissingMeansDefault === $policy) {
                    /** @psalm-var mixed */
                    $inputToBeFilled[$prop] = $defaultValues[$prop];
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

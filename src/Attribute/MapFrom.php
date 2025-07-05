<?php

namespace Nandan108\DtoToolkit\Attribute;

use Nandan108\DtoToolkit\Contracts\HasContextInterface;
use Nandan108\DtoToolkit\Contracts\PhaseAwareInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Enum\Phase;
use Nandan108\DtoToolkit\Exception\ExtractionSyntaxError;
use Nandan108\DtoToolkit\Exception\LoadingException;
use Nandan108\DtoToolkit\Traits\HasPhase;
use Nandan108\PropPath\Exception\SyntaxError;
use Nandan108\PropPath\PropPath;
use Nandan108\PropPath\Support\ExtractContext;

/**
 * This attribute is used to specify the scoping groups for a property.
 * If it is positioned after a #[Outbound] attribute, the groups will be set for the outbound phase.
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class MapFrom implements PhaseAwareInterface
{
    use HasPhase;

    /** @var \Closure(array, ?\Closure(string, ExtractContext): never): mixed */
    private \Closure $extractor;

    /** @var \Closure(string, ExtractContext):never */
    private \Closure $evalErrorHandler;

    public function __construct(string|array $paths)
    {
        $this->isIoBound = true;

        try {
            $this->extractor = PropPath::compile($paths);
        } catch (SyntaxError $e) {
            /** @var string $jsonPath */
            $jsonPath = json_encode($paths, JSON_THROW_ON_ERROR);
            throw new ExtractionSyntaxError(message: "MapFrom: Invalid path provided: $jsonPath.", previous: $e);
        }

        $this->evalErrorHandler = function (string $msg, ExtractContext $context): never {
            throw new LoadingException($context->getEvalErrorMessage($msg));
        };
    }

    public function __invoke(array $input, BaseDto $dto): mixed
    {
        $roots = [
            'input' => $input,
            'dto'   => $dto,
        ];
        if ($dto instanceof HasContextInterface) {
            /** @var array */
            $roots['context'] = $dto->getContext();
        }

        return ($this->extractor)($roots, $this->evalErrorHandler);
    }

    #[\Override]
    public function setOutbound(bool $isOutbound): void
    {
        if ($isOutbound) {
            throw new \LogicException('The MapFrom attribute cannot be used in the outbound phase.');
        }
    }

    /**
     * Get the mappers for a given DTO and phase.
     *
     * @param BaseDto       $dto       the DTO instance
     * @param array<string> $propNames the properties to return a mapper for
     *
     * @return array<MapFrom> an array of MapFrom instances, indexed by property name
     */
    public static function getMappers(BaseDto $dto, ?array $propNames = null): array
    {
        /** @var array<string, self> $mappers */
        $mappers = ($dto::class)::loadPhaseAwarePropMeta(Phase::InboundLoad, 'attr', self::class, true);

        return null !== $propNames ? array_intersect_key($mappers, array_flip($propNames)) : $mappers;
    }
}

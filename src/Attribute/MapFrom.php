<?php

namespace Nandan108\DtoToolkit\Attribute;

use Nandan108\DtoToolkit\Contracts\PhaseAwareInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Enum\Phase;
use Nandan108\DtoToolkit\Traits\HasPhase;

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

    // const TAKE_FROM_ORIGINAL = '^';
    public const THROW_IF_MISSING = '!';

    public function __construct(public array|string $sources)
    {
        $this->isIoBound = true;
    }

    public function __invoke(array $origValues, BaseDto $dto, array|string|null $sources = null, string $path = ''): mixed
    {
        $output = [];
        $sources ??= $this->sources;

        $singleField = false;
        if (is_string($sources)) {
            $sources = [$sources];
            $singleField = true;
        }

        foreach ($sources as $key => $source) {
            if (is_string($source)) {
                [$flags, $source] = $this->splitFlagsFromSourceName($source);
                if ($flags[self::THROW_IF_MISSING] && !array_key_exists($source, $origValues)) {
                    throw new \InvalidArgumentException(sprintf('Key \'%s\' not found in input values', $source));
                }
                if ($flags[self::THROW_IF_MISSING] > 1 && !isset($origValues[$source])) {
                    throw new \InvalidArgumentException(sprintf('Key \'%s\' should not be blank', $source));
                }
                $output[$key] = $origValues[$source] ?? null;
            } elseif (is_array($source)) {
                $output[$key] = $this($origValues, $dto, $source, $path.($path ? '.' : '').$key);
            }
        }

        if ($singleField) {
            $output = reset($output);
        }

        return $output;
    }

    #[\Override]
    public function setOutbound(bool $isOutbound): void
    {
        if ($isOutbound) {
            throw new \LogicException('The MapFrom attribute cannot be used in the outbound phase.');
        }
    }

    /**
     * Source names may start with one or more of the following flag characters:
     *  - a caret (^) to indicate that the value should be taken from the original input values\
     *  rather than the current value of the property (default).
     *  - an excalamation mark (!) to indicate to throw an exception if the source is not\
     *  found in the original values.
     *
     * @return array{0: array<string, int>, 1: string} an array with the flags and the source name
     *                                                 Te flags are returned as an associative array with the flag characters as keys and\
     *                                                 the number of occurrences as values
     */
    private function splitFlagsFromSourceName(string $source): array
    {
        $allowed_flags = [/* self::TAKE_FROM_ORIGINAL, */ self::THROW_IF_MISSING];
        $flags = array_fill_keys($allowed_flags, 0);

        // flag characters may come in any order, so loop on the string
        // until we find a non-flag character
        for ($i = 0; $i < strlen($source); ++$i) {
            if (!in_array($source[$i], $allowed_flags)) {
                break;
            }
            ++$flags[$source[$i]];
        }

        $source = substr($source, array_sum($flags));

        return [$flags, $source];
    }

    /**
     * Get the mappers for a given DTO and phase.
     *
     * @param $dto   the DTO instance
     * @param $props the properties to return a mapper for
     *
     * @return array<string, self> an array of MapFrom instances, indexed by property name
     */
    public static function getMappers(BaseDto $dto, ?array $props = null): array
    {
        /** @var array<string, self[]> $mappers */
        $mappersByProp = ($dto::class)::loadPhaseAwarePropMeta(Phase::InboundLoad, 'attr', self::class);

        // if no properties are given, return all mappers
        $props ??= array_keys($mappersByProp);

        $mappersByProp = array_filter(
            array: array_map(
                callback: fn (array $arr): ?MapFrom => $arr[0] ?? null,
                array: array_intersect_key($mappersByProp, array_flip($props)),
            ),
        );

        return $mappersByProp;
    }
}

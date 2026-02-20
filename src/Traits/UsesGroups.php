<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\Attribute\PropGroups;
use Nandan108\DtoToolkit\Contracts\HasGroupsInterface;
use Nandan108\DtoToolkit\Enum\Phase;

/**
 * Trait for DTOs that want to use group-based property filtering for processing and validation.
 *
 * @method static static newWithGroups(array|string $all = [], array|string $inbound = [], array|string $inboundCast = [], array|string $outbound = [], array|string $outboundCast = [], array|string $validation = [])
 *
 * This trait implements the HasGroupsInterface and ScopedPropertyAccessInterface
 *
 * @api
 */
trait UsesGroups // user must implement HasGroupsInterface, ScopedPropertyAccessInterface
{
    use HasContext;

    /**
     * Create a new DTO instance with the given groups.
     * implements HasGroupsInterface.
     *
     * @param array|string $inbound      group names to filter properties for loading data (inbound.io phase)
     * @param array|string $inboundCast  sequences of group name(s) for scope-aware (inbound.cast phase)
     * @param array|string $outboundCast sequences of group name(s) for scope-aware (outbound.cast phase)
     * @param array|string $outbound     group names to filter properties for loading data (outbound.io phase)
     */
    #[\Override]
    public function withGroups(array | string $all = [], array | string $inbound = [], array | string $inboundCast = [], array | string $outbound = [], array | string $outboundCast = [], array | string $validation = []): static
    {
        foreach ([&$all, &$inbound, &$inboundCast, &$outbound, &$outboundCast, &$validation] as &$groups) {
            if (is_string($groups)) {
                $groups = [$groups];
            }
        }

        return $this->withContext([
            'groups.inbound.io'       => $inbound ?: $all,
            'groups.inbound.cast'     => $inboundCast ?: $inbound ?: $all,
            'groups.outbound.io'      => $outbound ?: $all,
            'groups.outbound.cast'    => $outboundCast ?: $outbound ?: $all,
        ]);
    }

    /**
     * Get the per-property list of groups defined for the given phase.
     *
     * @return array<string, array<array-key, PropGroups>>
     */
    public function getPropGroups(Phase $phase): array
    {
        /** @var array<string, PropGroups[]> $groupAttrByProp */
        $groupAttrByProp = static::getPhaseAwarePropMeta($phase, 'attr', PropGroups::class);

        // Since Groups may be applied to both inbound and outbound phases, it is a repeatable attribute.
        // Therefore, there may be multiple PropGroups attributes for the same property (even in the same phase).
        // So for each property, we need to merge the groups from all PropGroups attributes.
        foreach ($groupAttrByProp as &$propGroups) {
            $propGroups = array_reduce(
                array: array_map(fn ($g): array => (array) $g->groups, $propGroups),
                callback: 'array_merge',
                initial: [],
            );
        }

        return $groupAttrByProp;
    }

    /**
     * @return array<string> list of groups in the scope of the given phase
     *
     * implements HasGroupsInterface
     **/
    #[\Override]
    public function getActiveGroups(Phase $phase): array
    {
        /** @var array<string> */
        return (array) $this->contextGet('groups.'.$phase->value, []);
    }

    /**
     * @return bool true if at least one of the given groups is in scope for the given phase,
     *              or if no groups are given (i.e. no group filtering is required)
     *
     * implements HasGroupsInterface
     **/
    #[\Override]
    public function groupsAreInScope(Phase $phase, array $groups): bool
    {
        return !$groups || array_intersect($this->getActiveGroups($phase), $groups);
    }

    /**
     * implements ScopedPropertyAccessInterface.
     *
     * @psalm-suppress LessSpecificImplementedReturnType
     */
    #[\Override]
    public function getPropertiesInScope(Phase $phase): array
    {
        $propGroupsForPhase = $this->getPropGroups($phase);
        $activeGroups = (array) $this->contextGet('groups.'.$phase->value, []);

        /** @var string[] $inScope */
        $inScope = [];

        foreach ($propGroupsForPhase as $propName => $groups) {
            if (!$groups || array_intersect($activeGroups, $groups)) {
                $inScope[] = $propName;
            }
        }

        return $inScope;
    }
}

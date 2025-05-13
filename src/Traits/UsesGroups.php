<?php

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\Attribute\PropGroups;
use Nandan108\DtoToolkit\Enum\Phase;

/**
 * @method static static withGroups(array|string $all = [], array|string $inbound = [], array|string $inboundCast = [], array|string $outbound = [], array|string $outboundCast = [], array|string $validation = [])
 *
 * $this trait implements the HasGroupsInterface and ScopedPropertyAccessInterface
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
    public function _withGroups(array|string $all = [], array|string $inbound = [], array|string $inboundCast = [], array|string $outbound = [], array|string $outboundCast = [], array|string $validation = []): static
    {
        foreach ([$all, $inbound, $inboundCast, $outbound, $outboundCast, $validation] as &$groups) {
            if (is_string($groups)) {
                $groups = [$groups];
            }
        }

        return $this->_withContext([
            'groups.inbound.io'    => $inbound ?: $all,
            'groups.inbound.valid' => $validation ?: $all,
            'groups.inbound.cast'  => $inboundCast ?: $inbound ?: $all,
            'groups.outbound.io'   => $outbound ?: $all,
            'groups.outbound.cast' => $outboundCast ?: $outbound ?: $all,
        ]);
    }

    /**
     * Get the per-property list of groups defined for the given phase.
     *
     * @return array [propName => [groups]]
     */
    public function getPropGroups(Phase $phase): array
    {
        $meta = static::loadPropertyMetadata($phase);
        $groupsByPropname = [];

        foreach ($meta as $propName => &$propMeta) {
            if (!isset($propMeta['groups'])) {
                $groups = [];

                foreach ($propMeta['attr'] ?? [] as $attr) {
                    if ($attr instanceof PropGroups) {
                        $groups[] = (array) $attr->groups;
                    }
                }

                $propMeta['groups'] = array_merge(...$groups);
            }
            $groupsByPropname[$propName] = $propMeta['groups'];
        }

        return $groupsByPropname;
    }

    /**
     * @return string[] list of groups in the scope of the given phase
     *                  implements HasGroupsInterface
     **/
    #[\Override]
    public function getActiveGroups(Phase $phase): array
    {
        return (array) $this->getContext('groups.'.$phase->value, []);
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
     **/
    #[\Override]
    public function getPropertiesInScope(Phase $phase): array
    {
        $propGroupsForPhase = $this->getPropGroups($phase);
        $activeGroups = (array) $this->getContext('groups.'.$phase->value, []);

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

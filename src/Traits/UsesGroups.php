<?php

namespace Nandan108\DtoToolkit\Traits;

use Nandan108\DtoToolkit\Attribute\PropGroups;
use Nandan108\DtoToolkit\Enum\Phase;

/**
 * @method static static withGroups(array|string $all = [], array|string $inbound = [], array|string $inboundCast = [], array|string $outbound = [], array|string $outboundCast = [], array|string $validation = []): static
 */
trait UsesGroups // user must implement HasGroupsInterface, ScopedPropertyAccessInterface
{
    use HasContext;

    /**
     * Create a new DTO instance with the given groups.
     *
     * @param array|string $inbound      group names to filter properties for loading data (inbound.io phase)
     * @param array|string $inboundCast  sequences of group name(s) for scope-aware (inbound.cast phase)
     * @param array|string $outboundCast sequences of group name(s) for scope-aware (outbound.cast phase)
     * @param array|string $outbound     group names to filter properties for loading data (outbound.io phase)
     */
    #[\Override] // implements HasGroupsInterface
    public function _withGroups(array|string $all = [], array|string $inbound = [], array|string $inboundCast = [], array|string $outbound = [], array|string $outboundCast = [], array|string $validation = []): static
    {
        foreach ([$all, $inbound, $inboundCast, $outbound, $outboundCast, $validation] as &$groups) {
            if (is_string($groups)) {
                $groups = [$groups];
            }
        }

        return $this->withContext([
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

    #[\Override]
    public function getActiveGroups(Phase $phase): array
    {
        return (array) $this->getContext('groups.'.$phase->value, []);
    }

    #[\Override]
    public function groupsAreInScope(Phase $phase, array $groups): bool
    {
        return !$groups || array_intersect($this->getActiveGroups($phase), $groups);
    }

    #[\Override] // ScopedPropertyAccessInterface
    public function getPropertiesInScope(Phase $phase): array
    {
        $propGroupsForPhase = $this->getPropGroups($phase);
        $activeGroups = (array) $this->getContext('groups.'.$phase->value, []);

        $inScope = [];

        foreach ($propGroupsForPhase as $propName => $groups) {
            if (!$groups || array_intersect($activeGroups, $groups)) {
                $inScope[] = $propName;
            }
        }

        return $inScope;
    }
}

<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Contracts;

use Nandan108\DtoToolkit\Enum\Phase;

interface HasGroupsInterface
{
    /** @psalm-suppress PossiblyUnusedMethod, PossiblyUnusedReturnValue*/
    public function _withGroups(
        array | string $all = [], // all-phases default
        array | string $inbound = [],
        array | string $inboundCast = [],
        array | string $outbound = [],
        array | string $outboundCast = [],
        array | string $validation = [],
    ): static;

    /** @psalm-suppress PossiblyUnusedMethod */
    public function groupsAreInScope(Phase $phase, array $groups): bool;

    /**
     * @return array<string> list of groups in the scope of the given phase
     */
    public function getActiveGroups(Phase $phase): array;
}

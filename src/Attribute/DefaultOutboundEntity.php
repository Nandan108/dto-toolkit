<?php

declare(strict_types=1);

namespace Nandan108\DtoToolkit\Attribute;

use Nandan108\DtoToolkit\Contracts\HasGroupsInterface;
use Nandan108\DtoToolkit\Core\BaseDto;
use Nandan108\DtoToolkit\Enum\ConstructMode;
use Nandan108\DtoToolkit\Enum\Phase;
use Nandan108\DtoToolkit\Exception\Config\InvalidConfigException;

/**
 * This attribute is used to specify the default entity class to map to when exporting.
 *
 * Can be repeated to provide different entity classes for different scoping groups.
 * In case of multiple matches, the first matching one will be used.
 *
 * @psalm-type OutboundEntityData = array{class: class-string, construct: ConstructMode}
 * @psalm-type OutboundEntityClassMap = array<class-string, array{groups: list<string>, construct: ConstructMode}>
 *
 * @psalm-api
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
class DefaultOutboundEntity
{
    /**
     * @param class-string        $entityClass
     * @param string|list<string> $groups
     */
    public function __construct(
        public string $entityClass,
        public ConstructMode $construct = ConstructMode::Default,
        public string | array $groups = [],
    ) {
        if (is_string($this->groups)) {
            $this->groups = [$this->groups];
        }
    }

    /**
     * @var OutboundEntityClassMap
     */
    private static array $_classMapCache = [];

    /**
     * Get the default outbound entity class for this DTO, based on the DefaultOutboundEntity attributes.
     *
     * @return ?OutboundEntityData
     *
     * @throws InvalidConfigException
     */
    public static function resolveForDto(BaseDto $dto): ?array
    {
        $dtoClass = $dto::class;
        /** @psalm-suppress UnsupportedPropertyReferenceUsage */
        $meta = &self::$_classMapCache[$dtoClass];
        /** @var OutboundEntityClassMap|null $meta */

        // initialize defaultOutboundEntityClasses cache if not done yet
        if (null === $meta) {
            $meta = [];
            // initialize values from DefaultOutboundEntity attribute
            $refClass = $dtoClass::getClassRef();
            $attrs = $refClass->getAttributes(DefaultOutboundEntity::class);
            $implementsGroups = $dto instanceof HasGroupsInterface;
            foreach ($attrs as $attrRef) {
                $attrInstance = $attrRef->newInstance();
                if ($attrInstance->groups && !$implementsGroups) {
                    throw new InvalidConfigException('The DefaultOutboundEntity attribute on DTO '.$dtoClass.' declares scoping groups, but the DTO does not implement HasGroupsInterface.');
                }
                // throw if entity class does not exist
                if (!class_exists($attrInstance->entityClass)) {
                    throw new InvalidConfigException("Class \"$attrInstance->entityClass\" not found");
                }
                $meta[$attrInstance->entityClass] = [
                    'groups'    => (array) $attrInstance->groups,
                    'construct' => $attrInstance->construct,
                ];
            }
        }

        // filter by scoping groups if any -- Phase: OutboundExport
        foreach ($meta as $entityClass => ['groups' => $groups, 'construct' => $construct]) {
            if (empty($groups)
                || ($dto instanceof HasGroupsInterface
                    && $dto->groupsAreInScope(Phase::OutboundExport, $groups))) {
                return ['class' => $entityClass, 'construct' => $construct];
            }
        }

        return null;
    }
}

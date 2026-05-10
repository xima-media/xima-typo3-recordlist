<?php

namespace Xima\XimaTypo3Recordlist\Utility;

final class RelationFilterResult
{
    public function __construct(
        public readonly bool $isEmpty = false,
        public readonly bool $useFindInSet = false,
        public readonly ?string $findInSetValue = null,
        public readonly array $parentUids = [],
    ) {
    }
}

<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Dto\Tree;

use TYPO3\CMS\Backend\Dto\Tree\TreeItem;

/**
 * Category tree item that includes the storage pid for creating new categories
 */
final readonly class CategoryTreeItem implements \JsonSerializable
{
    public function __construct(
        public TreeItem $item,
        public int $doktype,
        public string $nameSourceField,
        public int $workspaceId,
        public bool $locked,
        public bool $stopPageTree,
        public int $mountPoint,
        public int $storagePid,
        public int $sorting,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            'type' => 'CategoryTreeItem',
            ...$this->item->jsonSerialize(),
            'doktype' => $this->doktype,
            'nameSourceField' => $this->nameSourceField,
            'workspaceId' => $this->workspaceId,
            'locked' => $this->locked,
            'stopPageTree' => $this->stopPageTree,
            'mountPoint' => $this->mountPoint,
            'storagePid' => $this->storagePid,
            'sorting' => $this->sorting,
        ];
    }
}

<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Event;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Listeners to this event will be able to modify the prepared category tree items for the category tree
 */
final class AfterCategoryTreeItemsPreparedEvent
{
    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function __construct(
        private readonly ServerRequestInterface $request,
        private array $items
    ) {
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function setItems(array $items): void
    {
        $this->items = $items;
    }
}

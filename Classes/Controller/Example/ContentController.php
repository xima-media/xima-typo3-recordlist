<?php

namespace Xima\XimaTypo3Recordlist\Controller\Example;

use Xima\XimaTypo3Recordlist\Controller\AbstractBackendController;

/**
 * Example module listing `tt_content` records.
 *
 * `tt_content` is configured with a `sortby` field (`sorting`) and no `default_sortby`, so the
 * list is shown in its manual sort order by default. This makes the move-up / move-down sorting
 * actions available out of the box and serves as the reference example for that feature.
 */
class ContentController extends AbstractBackendController
{
    public function getRecordPid(): int
    {
        return 3;
    }

    public function getTableNames(): array
    {
        return ['tt_content'];
    }

    public function modifyTableConfiguration(): void
    {
        $this->tableConfiguration['tt_content']['columns']['header']['defaultPosition'] = 1;
        $this->tableConfiguration['tt_content']['columns']['CType']['defaultPosition'] = 2;
    }
}

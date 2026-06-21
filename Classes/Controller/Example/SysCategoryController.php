<?php

namespace Xima\XimaTypo3Recordlist\Controller\Example;

use Xima\XimaTypo3Recordlist\Controller\AbstractBackendController;
use Xima\XimaTypo3Recordlist\Dto\RecordSource;

class SysCategoryController extends AbstractBackendController
{
    protected function getRecordSources(): array
    {
        return [new RecordSource(17, includeSubpages: true, depth: 1)];
    }

    public function getTableNames(): array
    {
        return ['sys_category'];
    }
}

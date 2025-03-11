<?php

namespace Xima\XimaTypo3Recordlist\Controller\Example;

use Xima\XimaTypo3Recordlist\Controller\AbstractBackendController;

class SysCategoryController extends AbstractBackendController
{
    public function getRecordPid(): int
    {
        return 17;
    }

    public function getTableName(): string
    {
        return 'sys_category';
    }
}

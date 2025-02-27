<?php

namespace Xima\XimaTypo3Recordlist\Controller\Example;

use Xima\XimaTypo3Recordlist\Controller\AbstractBackendController;

class PagesController extends AbstractBackendController
{
    protected const TEMPLATE_NAME = 'Example/Pages';

    public function getRecordPid(): int
    {
        return 0;
    }

    public function getTableName(): string
    {
        return 'pages';
    }
}

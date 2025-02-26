<?php

namespace Xima\XimaTypo3RecordlistExamples\Controller;

use Xima\XimaTypo3Recordlist\Controller\AbstractBackendController;

class PagesController extends AbstractBackendController
{
    protected const TEMPLATE_NAME = 'Pages';

    public function getRecordPid(): int
    {
        return 0;
    }

    public function getTableName(): string
    {
        return 'pages';
    }
}

<?php

namespace Xima\XimaTypo3Recordlist\Controller\Example;

use Xima\XimaTypo3Recordlist\Controller\AbstractBackendController;

class NewsController extends AbstractBackendController
{
    protected const TEMPLATE_NAME = 'Example/News';

    public function getRecordPid(): int
    {
        return 15;
    }

    public function getTableName(): string
    {
        return 'tx_news_domain_model_news';
    }
}

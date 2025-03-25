<?php

namespace Xima\XimaTypo3Recordlist\Controller\Example;

use Xima\XimaTypo3Recordlist\Controller\AbstractBackendController;

class NewsController extends AbstractBackendController
{
    protected const TEMPLATE_NAME = 'Example/News';

    public const WORKSPACE_ID = 1;

    public function getRecordPid(): int
    {
        return 15;
    }

    public function getTableName(): string
    {
        return 'tx_news_domain_model_news';
    }

    public function getTableConfiguration(): array
    {
        $configuration = parent::getTableConfiguration();
        $configuration['columns']['teaser']['partial'] = 'TextInlineEdit';
        $configuration['columns']['workspace-status']['defaultPosition'] = 2;

        return $configuration;
    }
}

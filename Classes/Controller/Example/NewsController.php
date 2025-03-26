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

    public function modifyTableConfiguration(): void
    {
        $this->tableConfiguration['columns']['author']['partial'] = 'TextInlineEdit';

        $this->tableConfiguration['columns']['fal_media']['defaultPosition'] = 2;
        $this->tableConfiguration['columns']['author']['defaultPosition'] = 3;
        $this->tableConfiguration['columns']['sitemap_changefreq']['defaultPosition'] = 4;
        $this->tableConfiguration['columns']['workspace-status']['defaultPosition'] = 5;
    }
}

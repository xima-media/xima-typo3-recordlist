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

    public function getTableNames(): array
    {
        return ['tx_news_domain_model_news'];
    }

    public function modifyTableConfiguration(): void
    {
        $this->tableConfiguration['tx_news_domain_model_news']['columns']['author']['partial'] = 'TextInlineEdit';

        $this->tableConfiguration['tx_news_domain_model_news']['columns']['fal_media']['defaultPosition'] = 2;
        $this->tableConfiguration['tx_news_domain_model_news']['columns']['author']['defaultPosition'] = 3;
        $this->tableConfiguration['tx_news_domain_model_news']['columns']['sitemap_changefreq']['defaultPosition'] = 4;
        $this->tableConfiguration['tx_news_domain_model_news']['columns']['sys_language_uid']['defaultPosition'] = 5;
        $this->tableConfiguration['tx_news_domain_model_news']['columns']['workspace-status']['defaultPosition'] = 6;
    }
}

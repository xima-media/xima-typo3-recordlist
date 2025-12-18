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
        $this->tableConfiguration['columns']['sys_language_uid']['defaultPosition'] = 5;
        $this->tableConfiguration['columns']['workspace-status']['defaultPosition'] = 6;
    }

    public function modifyQueryBuilder(): void
    {
        $this->queryBuilder->addSelectLiteral('CASE WHEN t1.l10n_parent != 0 THEN t1.l10n_parent ELSE t1.uid END AS ' . $this->queryBuilder->quoteIdentifier('sys_language_ordering'));
    }

    protected function addOrderConstraint(): void
    {
        parent::addOrderConstraint();
        $this->queryBuilder->addOrderBy('sys_language_ordering', 'ASC');
    }
}

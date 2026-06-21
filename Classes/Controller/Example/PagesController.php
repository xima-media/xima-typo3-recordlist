<?php

namespace Xima\XimaTypo3Recordlist\Controller\Example;

use Xima\XimaTypo3Recordlist\Controller\AbstractBackendController;
use Xima\XimaTypo3Recordlist\Dto\RecordSource;

class PagesController extends AbstractBackendController
{
    protected function getRecordSources(): array
    {
        return [new RecordSource(1, includeSubpages: true, depth: 1)];
    }

    public function getTableNames(): array
    {
        return ['pages'];
    }

    public function getTemplateConfigurations(): array
    {
        return [
            'Default' => [
                'title' => 'LLL:EXT:xima_typo3_recordlist/Resources/Private/Language/Example/locallang_pages_module.xlf:template.list.title',
                'icon' => 'actions-list',
            ],
            'Example/PagesCards' => [
                'title' => 'LLL:EXT:xima_typo3_recordlist/Resources/Private/Language/Example/locallang_pages_module.xlf:template.cards.title',
                'icon' => 'actions-menu',
                'actions' => ['templateSelection', 'tableSelection', 'languageSelection', 'newRecord'],
            ],
        ];
    }

    public function modifyTableConfiguration(): void
    {
        $this->tableConfiguration['pages']['columns']['title']['defaultPosition'] = 2;
        $this->tableConfiguration['pages']['columns']['subtitle']['defaultPosition'] = 3;
        $this->tableConfiguration['pages']['columns']['description']['defaultPosition'] = 4;
    }

    public function modifyQueryBuilder(): void
    {
        $this->queryBuilder->addSelectLiteral('CASE WHEN t1.l10n_parent != 0 THEN t1.l10n_parent ELSE t1.uid END AS ' . $this->queryBuilder->quoteIdentifier('sys_language_ordering'));
    }

    protected function addOrderConstraint(): void
    {
        $GLOBALS['TCA']['pages']['ctrl']['default_sortby'] = 'pid ASC, sys_language_ordering ASC';
        parent::addOrderConstraint();
    }
}

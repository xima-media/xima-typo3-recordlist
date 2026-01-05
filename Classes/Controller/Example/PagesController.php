<?php

namespace Xima\XimaTypo3Recordlist\Controller\Example;

use Xima\XimaTypo3Recordlist\Controller\AbstractBackendController;

class PagesController extends AbstractBackendController
{
    public function getRecordPid(): int
    {
        return 1;
    }

    public function getTableNames(): array
    {
        return ['pages'];
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

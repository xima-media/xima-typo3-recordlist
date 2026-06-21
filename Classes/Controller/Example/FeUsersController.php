<?php

namespace Xima\XimaTypo3Recordlist\Controller\Example;

use Xima\XimaTypo3Recordlist\Controller\AbstractBackendController;
use Xima\XimaTypo3Recordlist\Dto\RecordSource;

class FeUsersController extends AbstractBackendController
{
    protected function getRecordSources(): array
    {
        return [new RecordSource(19, includeSubpages: true, depth: 1)];
    }

    public function getTableNames(): array
    {
        return ['fe_users', 'fe_groups'];
    }

    protected function getTemplateConfigurations(): array
    {
        if ($this->getTableName() === 'fe_users') {
            return ['Example/FeUsers' => []];
        }
        return ['Default' => []];
    }

    protected function modifyQueryBuilder(): void
    {
        $selectedUserCountry = $this->request->getParsedBody()['user_country'] ?? null;
        if ($selectedUserCountry) {
            $this->queryBuilder->andWhere(
                $this->queryBuilder->expr()->eq(
                    'country',
                    $this->queryBuilder->createNamedParameter($selectedUserCountry)
                )
            );
        }
    }

    protected function assignViewVariables(): void
    {
        parent::assignViewVariables();

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('fe_users');
        $userCountries = $queryBuilder
            ->select('country')
            ->from('fe_users')
            ->where(
                $queryBuilder->expr()->neq('country', $queryBuilder->createNamedParameter(''))
            )
            ->groupBy('country')
            ->orderBy('country')
            ->executeQuery()
            ->fetchAllAssociative();

        $this->moduleTemplate->assign('userCountries', $userCountries);
    }
}

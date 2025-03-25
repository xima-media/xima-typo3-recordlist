<?php

namespace Xima\XimaTypo3Recordlist\Controller\Example;

use TYPO3\CMS\Core\Resource\ResourceFactory;
use Xima\XimaTypo3Recordlist\Controller\AbstractBackendController;

class FilesController extends AbstractBackendController
{
    protected ResourceFactory $resourceFactory;

    public function injectResourceFactory(ResourceFactory $resourceFactory): void
    {
        $this->resourceFactory = $resourceFactory;
    }

    public function getTableName(): string
    {
        return 'sys_file_metadata';
    }

    public function getRecordPid(): int
    {
        return 0;
    }

    public function modifyQueryBuilder(): void
    {
        $this->queryBuilder->addSelect(
            's1.uid AS s1_uid',
            's1.name as s1_name',
            's1.identifier AS s1_identifier',
            's1.storage as s1_storage',
            's1.extension AS s1_extension'
        );
        $this->queryBuilder->leftJoin(
            't1',
            'sys_file',
            's1',
            $this->queryBuilder->expr()->eq('t1.file', $this->queryBuilder->quoteIdentifier('s1.uid'))
        );

        $this->queryBuilder->andWhere($this->queryBuilder->expr()->gte('s1.storage', 1));
    }

    public function modifyRecord(array &$record): void
    {
        $record['sysFile'] = $this->resourceFactory->getFileObjectByStorageAndIdentifier($record['s1_storage'], $record['s1_identifier']);
    }

    public function getTableConfiguration(): array
    {
        $tableConfiguration = parent::getTableConfiguration();

        $tableConfiguration['columns']['fileinfo'] = [
            'label' => 'Vorschau',
            'columnName' => 'fileinfo',
            'partial' => 'Thumbnail',
            'defaultPosition' => 1,
        ];

        $tableConfiguration['columns']['file']['partial'] = 'SysFile';
        $tableConfiguration['columns']['file']['defaultPosition'] = 2;

        $tableConfiguration['columns']['title']['defaultPosition'] = 3;
        $tableConfiguration['columns']['alternative']['defaultPosition'] = 4;
        $tableConfiguration['columns']['description']['defaultPosition'] = 5;

        $tableConfiguration['groupActions'] = [
            'Edit',
            'DeleteFile',
        ];
        return $tableConfiguration;
    }

    protected function addOrderConstraint(): void
    {
    }
}

<?php

namespace Xima\XimaTypo3Recordlist\Controller\Example;

use Xima\XimaTypo3Recordlist\Controller\AbstractBackendController;
use Xima\XimaTypo3Recordlist\Dto\RecordSource;

class FilesController extends AbstractBackendController
{
    public function getTableNames(): array
    {
        return ['sys_file_metadata'];
    }

    protected function getRecordSources(): array
    {
        return [new RecordSource(0, includeSubpages: true, depth: 1)];
    }

    protected function addNewButtonToModuleTemplate(): void
    {
    }

    public function modifyTableConfiguration(): void
    {
        $this->tableConfiguration['sys_file_metadata']['columns']['title']['defaultPosition'] = 2;
        $this->tableConfiguration['sys_file_metadata']['columns']['alternative']['defaultPosition'] = 3;
        $this->tableConfiguration['sys_file_metadata']['columns']['description']['defaultPosition'] = 4;

        $this->tableConfiguration['sys_file_metadata']['showIconColumn'] = false;

        $this->tableConfiguration['sys_file_metadata']['groupActions'] = [
            'View',
            'Edit',
            'DeleteFile',
        ];
    }
}

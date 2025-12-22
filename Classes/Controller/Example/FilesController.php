<?php

namespace Xima\XimaTypo3Recordlist\Controller\Example;

use Xima\XimaTypo3Recordlist\Controller\AbstractBackendController;

class FilesController extends AbstractBackendController
{
    public function getTableNames(): array
    {
        return ['sys_file_metadata'];
    }

    public function getRecordPid(): int
    {
        return 0;
    }

    protected function addNewButtonToModuleTemplate(): void
    {
    }

    public function modifyTableConfiguration(): void
    {
        $this->tableConfiguration['sys_file_metadata']['columns']['title']['defaultPosition'] = 2;
        $this->tableConfiguration['sys_file_metadata']['columns']['alternative']['defaultPosition'] = 3;
        $this->tableConfiguration['sys_file_metadata']['columns']['description']['defaultPosition'] = 4;

        $this->tableConfiguration['sys_file_metadata']['groupActions'] = [
            'View',
            'Edit',
            'DeleteFile',
        ];
    }
}

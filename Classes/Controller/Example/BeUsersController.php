<?php

namespace Xima\XimaTypo3Recordlist\Controller\Example;

use Xima\XimaTypo3Recordlist\Controller\AbstractBackendController;

class BeUsersController extends AbstractBackendController
{
    public function getRecordPid(): int
    {
        return 0;
    }

    public function getTableNames(): array
    {
        return ['be_users', 'be_groups', 'sys_filemounts'];
    }

    protected function modifyTableConfiguration(): void
    {
        $this->tableConfiguration['be_users']['columns']['username']['defaultPosition'] = 1;
        $this->tableConfiguration['be_users']['columns']['realName']['defaultPosition'] = 2;
        $this->tableConfiguration['be_users']['columns']['email']['defaultPosition'] = 3;
    }
}

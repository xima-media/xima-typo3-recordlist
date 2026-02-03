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

    public function getTemplateConfigurations(): array
    {
        return ['Example/BeUsers' => []];
    }
}

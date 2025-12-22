<?php

namespace Xima\XimaTypo3Recordlist\Controller\Example;

use Xima\XimaTypo3Recordlist\Controller\AbstractBackendController;

class FeUsersController extends AbstractBackendController
{
    public function getRecordPid(): int
    {
        return 19;
    }

    public function getTableNames(): array
    {
        return ['fe_users', 'fe_groups'];
    }
}

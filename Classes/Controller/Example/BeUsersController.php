<?php

namespace Xima\XimaTypo3Recordlist\Controller\Example;

use Xima\XimaTypo3Recordlist\Controller\AbstractBackendController;
use Xima\XimaTypo3Recordlist\Dto\RecordSource;

class BeUsersController extends AbstractBackendController
{
    protected function getRecordSources(): array
    {
        return [new RecordSource(0, includeSubpages: true, depth: 1)];
    }

    public function getTableNames(): array
    {
        return ['be_users', 'be_groups', 'sys_filemounts'];
    }

    public function getTemplateConfigurations(): array
    {
        return ['Example/BeUsers' => []];
    }

    protected function modifyTableConfiguration(): void
    {
        $this->tableConfiguration['be_users']['columns']['username']['defaultPosition'] = 1;
        $this->tableConfiguration['be_users']['columns']['realName']['defaultPosition'] = 2;
        $this->tableConfiguration['be_users']['columns']['email']['defaultPosition'] = 3;
    }
}

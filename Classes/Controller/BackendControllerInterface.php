<?php

namespace Xima\XimaTypo3Recordlist\Controller;

interface BackendControllerInterface
{
    public function getRecordPid(): int;

    /**
     * @return array<string>
     */
    public function getTableNames(): array;
}

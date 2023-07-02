<?php

namespace Xima\XimaTypo3Recordlist\Controller;

interface BackendControllerInterface
{
    public function getTableName(): string;

    public function getRecordPid(): int;
}

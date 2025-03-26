<?php

namespace Xima\XimaTypo3Recordlist\Controller;

interface BackendControllerInterface
{
    public function getRecordPid(): int;

    public function getTableName(): string;
}

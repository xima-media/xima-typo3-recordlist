<?php

namespace Xima\XimaTypo3Recordlist\Controller;

interface BackendControllerInterface
{
    /**
     * @deprecated since 14.6.0, will be removed in 15.0.0. Implement getRecordSources() instead.
     */
    public function getRecordPid(): int;

    /**
     * @return array<string>
     */
    public function getTableNames(): array;
}

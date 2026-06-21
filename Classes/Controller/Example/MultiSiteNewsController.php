<?php

namespace Xima\XimaTypo3Recordlist\Controller\Example;

use Xima\XimaTypo3Recordlist\Controller\AbstractBackendController;
use Xima\XimaTypo3Recordlist\Dto\RecordSource;

/**
 * Example module aggregating news records from folders in two different sites.
 *
 * Demonstrates {@see getRecordSources()} with entry points that live in
 * separate sites (mandants). Because the folders share the same name ("News"),
 * the directory dropdown and the new-record modal prefix each entry with its
 * site title.
 */
class MultiSiteNewsController extends AbstractBackendController
{
    public function getTableNames(): array
    {
        return ['tx_news_domain_model_news'];
    }

    protected function getRecordSources(): array
    {
        return [
            new RecordSource(15), // "News" folder in the main site
            new RecordSource(22), // "News" folder in the second site
        ];
    }
}

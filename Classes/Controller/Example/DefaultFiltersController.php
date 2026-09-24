<?php

namespace Xima\XimaTypo3Recordlist\Controller\Example;

use Xima\XimaTypo3Recordlist\Controller\AbstractBackendController;
use Xima\XimaTypo3Recordlist\Dto\RecordSource;

/**
 * One default filter per filter element. Together they narrow the news folder
 * down to four records, and removing any single one of them widens the list.
 */
class DefaultFiltersController extends AbstractBackendController
{
    protected function getRecordSources(): array
    {
        return [new RecordSource(15)];
    }

    public function getTableNames(): array
    {
        return ['tx_news_domain_model_news'];
    }

    public function modifyTableConfiguration(): void
    {
        $columns = &$this->tableConfiguration['tx_news_domain_model_news']['columns'];
        $columns['datetime']['defaultPosition'] = 2;
        $columns['categories']['defaultPosition'] = 3;
        $columns['sys_language_uid']['defaultPosition'] = 4;
        $columns['related']['defaultPosition'] = 5;
        $columns['related_links']['defaultPosition'] = 6;
    }

    protected function getDefaultFilters(): array
    {
        return [
            // Text
            'title' => ['value' => 'Launch', 'expr' => 'notLike'],
            // Select, with "0" as a value that must not count as empty
            'sys_language_uid' => ['value' => '0', 'expr' => 'eq'],
            // Category: "Technology"
            'categories' => ['value' => '18', 'expr' => 'in'],
            // Group: related to "Future Plans and Roadmap Revealed"
            'related' => ['value' => 'tx_news_domain_model_news_60', 'expr' => 'eq'],
            // Inline: searches the titles of the related links
            'related_links' => ['value' => 'Press kit'],
            // Date range
            'datetime' => ['value' => '2024-01-01', 'valueEnd' => '2024-06-30', 'expr' => 'between'],
        ];
    }
}

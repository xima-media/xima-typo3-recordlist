<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\News;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class NewsExportCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $I->openModule('example_news');
    }

    public function exportWorkspaceRecordsAsCsv(AcceptanceTester $I): void
    {
        $I->wantTo('export workspace records as CSV');

        $I->exportRecords('csv');
    }

    public function exportGermanLanguageRecords(AcceptanceTester $I): void
    {
        $I->wantTo('export German language records only');

        $I->click('.toggleSearchButton');
        $I->waitForElementVisible('select[name="language"]', 5);
        $I->selectOption('select[name="language"]', '1');
        $I->click('button[type="submit"]');
        $I->wait(1);

        $I->exportRecords('json');
    }

    public function exportAsXlsx(AcceptanceTester $I): void
    {
        $I->wantTo('export news records as XLSX');

        $I->exportRecords('xlsx');
    }
}

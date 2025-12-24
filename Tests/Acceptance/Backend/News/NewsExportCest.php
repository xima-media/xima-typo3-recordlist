<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\News;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class NewsExportCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsEditor();
        $this->navigateToModule($I, 'example_news');
    }

    public function exportWorkspaceRecordsAsCsv(AcceptanceTester $I): void
    {
        $I->wantTo('export workspace records as CSV');

        $I->click('.recordlist-download-button');
        $I->waitForElement('.download-modal', 5);
        $I->selectOption('select[name="format"]', 'csv');
        $I->click('.download-modal button[type="submit"]');

        $I->wait(2);
    }

    public function exportGermanLanguageRecords(AcceptanceTester $I): void
    {
        $I->wantTo('export German language records only');

        $I->selectOption('select[name="language"]', '1');
        $I->click('button[type="submit"]');
        $I->wait(1);

        $I->click('.recordlist-download-button');
        $I->waitForElement('.download-modal', 5);
        $I->selectOption('select[name="format"]', 'json');
        $I->click('.download-modal button[type="submit"]');

        $I->wait(2);
    }

    public function exportAsXlsx(AcceptanceTester $I): void
    {
        $I->wantTo('export news records as XLSX');

        $I->click('.recordlist-download-button');
        $I->waitForElement('.download-modal', 5);
        $I->selectOption('select[name="format"]', 'xlsx');
        $I->click('.download-modal button[type="submit"]');

        $I->wait(2);
    }

    protected function navigateToModule(AcceptanceTester $I, string $moduleIdentifier): void
    {
        $I->switchToMainFrame();
        $I->click('//a[@data-moduleroute-identifier="' . $moduleIdentifier . '"]');
        $I->wait(1);
        $I->switchToContentFrame();
        $I->waitForElement('.recordlist-table', 10);
    }
}

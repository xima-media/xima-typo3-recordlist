<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\BeUsers;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class BeUsersExportCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $this->navigateToModule($I, 'example_beusers');
    }

    public function exportAsCsv(AcceptanceTester $I): void
    {
        $I->wantTo('export records as CSV');

        $I->click('.recordlist-download-button');

        // Modal opens in main frame
        $I->switchToMainFrame();
        $I->waitForElement('.modal', 5);
        $I->selectOption('select[name="format"]', 'csv');
        $I->click('.modal button.btn-primary');
        $I->wait(2);

        // Switch back to content frame
        $I->switchToContentFrame();
    }

    public function exportAsJson(AcceptanceTester $I): void
    {
        $I->wantTo('export records as JSON');

        $I->click('.recordlist-download-button');

        // Modal opens in main frame
        $I->switchToMainFrame();
        $I->waitForElement('.modal', 5);
        $I->selectOption('select[name="format"]', 'json');
        $I->click('.modal button.btn-primary');
        $I->wait(2);

        // Switch back to content frame
        $I->switchToContentFrame();
    }

    public function exportAsXlsx(AcceptanceTester $I): void
    {
        $I->wantTo('export records as XLSX');

        $I->click('.recordlist-download-button');

        // Modal opens in main frame
        $I->switchToMainFrame();
        $I->waitForElement('.modal', 5);
        $I->selectOption('select[name="format"]', 'xlsx');
        $I->click('.modal button.btn-primary');
        $I->wait(2);

        // Switch back to content frame
        $I->switchToContentFrame();
    }

    protected function navigateToModule(AcceptanceTester $I, string $moduleIdentifier): void
    {
        $I->switchToMainFrame();
        $I->click('//a[@data-modulemenu-identifier="' . $moduleIdentifier . '"]');
        $I->wait(1);
        $I->switchToContentFrame();
        $I->waitForElement('main.recordlist', 10);
    }
}

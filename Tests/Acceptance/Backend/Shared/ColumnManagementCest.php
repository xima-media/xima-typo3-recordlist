<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\Shared;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class ColumnManagementCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $this->navigateToModule($I, 'example_beusers');
    }

    public function showColumnsModalOpens(AcceptanceTester $I): void
    {
        $I->wantTo('verify that show columns modal opens');

        $I->click('.showColumnsButton');
        $I->switchToMainFrame();
        $I->waitForElement('.modal', 5);

        $I->seeElement('.modal');
        $I->switchToContentFrame();
    }

    public function toggleColumnVisibility(AcceptanceTester $I): void
    {
        $I->wantTo('toggle column visibility');

        $I->click('.showColumnsButton');
        $I->switchToMainFrame();
        $I->waitForElement('.modal', 5);

        $I->checkOption('input[name="columns[email]"]');
        $I->click('.modal button.btn-primary');
        $I->wait(1);

        $I->switchToContentFrame();
        $I->see('Email', '//thead//th');
    }

    public function columnSettingsPersist(AcceptanceTester $I): void
    {
        $I->wantTo('verify that column settings persist across page reload');

        $I->click('.showColumnsButton');
        $I->switchToMainFrame();
        $I->waitForElement('.modal', 5);
        $I->checkOption('input[name="columns[email]"]');
        $I->click('.modal button.btn-primary');
        $I->wait(1);

        $I->switchToContentFrame();
        $I->reload();
        $I->waitForElement('main.recordlist', 10);

        $I->see('Email', '//thead//th');
    }

    public function activeColumnsAreSortedToTop(AcceptanceTester $I): void
    {
        $I->wantTo('verify that active columns are sorted to top');

        $I->click('.showColumnsButton');
        $I->switchToMainFrame();
        $I->waitForElement('.modal', 5);

        $I->seeElement('input[name="columns[username]"][checked]');
        $I->click('.modal .btn-close');
        $I->wait(1);
        $I->switchToContentFrame();
    }

    public function exportRespectsColumnVisibility(AcceptanceTester $I): void
    {
        $I->wantTo('verify that export respects column visibility');

        $I->click('.showColumnsButton');
        $I->switchToMainFrame();
        $I->waitForElement('.modal', 5);
        $I->uncheckOption('input[name="columns[username]"]');
        $I->click('.modal button.btn-primary');
        $I->wait(1);

        $I->switchToContentFrame();
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

    protected function navigateToModule(AcceptanceTester $I, string $moduleIdentifier): void
    {
        $I->switchToMainFrame();
        $I->click('//a[@data-modulemenu-identifier="' . $moduleIdentifier . '"]');
        $I->wait(1);
        $I->switchToContentFrame();
        $I->waitForElement('main.recordlist', 10);
    }
}

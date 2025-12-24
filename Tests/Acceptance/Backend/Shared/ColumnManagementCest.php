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
        $I->waitForElement('.column-settings-modal', 5);

        $I->see('Manage Columns');
    }

    public function toggleColumnVisibility(AcceptanceTester $I): void
    {
        $I->wantTo('toggle column visibility');

        $I->click('.showColumnsButton');
        $I->waitForElement('.column-settings-modal', 5);

        $I->checkOption('input[name="columns[email]"]');
        $I->click('.column-settings-modal button[type="submit"]');
        $I->wait(1);

        $I->see('Email', '//thead//th');
    }

    public function columnSettingsPersist(AcceptanceTester $I): void
    {
        $I->wantTo('verify that column settings persist across page reload');

        $I->click('.showColumnsButton');
        $I->waitForElement('.column-settings-modal', 5);
        $I->checkOption('input[name="columns[email]"]');
        $I->click('.column-settings-modal button[type="submit"]');
        $I->wait(1);

        $I->reload();
        $I->waitForElement('main.recordlist', 10);

        $I->see('Email', '//thead//th');
    }

    public function activeColumnsAreSortedToTop(AcceptanceTester $I): void
    {
        $I->wantTo('verify that active columns are sorted to top');

        $I->click('.showColumnsButton');
        $I->waitForElement('.column-settings-modal', 5);

        $I->seeElement('input[name="columns[username]"][checked]');
    }

    public function exportRespectsColumnVisibility(AcceptanceTester $I): void
    {
        $I->wantTo('verify that export respects column visibility');

        $I->click('.showColumnsButton');
        $I->waitForElement('.column-settings-modal', 5);
        $I->uncheckOption('input[name="columns[username]"]');
        $I->click('.column-settings-modal button[type="submit"]');
        $I->wait(1);

        $I->click('.recordlist-download-button');
        $I->waitForElement('.download-modal', 5);
        $I->selectOption('select[name="format"]', 'csv');
        $I->click('.download-modal button[type="submit"]');

        $I->wait(2);
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

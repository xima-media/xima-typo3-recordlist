<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\Shared;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class ColumnManagementCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $I->switchToMainFrame();
        $I->openModule('example_beusers');
    }

    public function showColumnsModalOpens(AcceptanceTester $I): void
    {
        $I->wantTo('verify that columns setting modal opens');

        $I->openColumnsModal();

        $I->seeElement('.modal');
        $I->switchToContentFrame();
    }

    public function addNewColumn(AcceptanceTester $I): void
    {
        $I->wantTo('display new column in record list');

        $I->dontSee('Last login', '//thead//th');

        $I->toggleColumn('lastlogin');

        $I->see('Last login', '//thead//th');
    }

    public function columnSettingsPersist(AcceptanceTester $I): void
    {
        $I->wantTo('verify that column settings persist across page reload');

        $I->dontSee('Last login', '//thead//th');

        $I->toggleColumn('lastlogin');

        $I->reloadPage();

        $I->switchToContentFrame();
        $I->waitForElement('main.recordlist', 10);
        $I->see('Last login', '//thead//th');
    }

    public function uncheckingAllColumnsRestoresDefaults(AcceptanceTester $I): void
    {
        $I->wantTo('verify that unchecking all columns restores default columns');

        // First, add some custom columns
        $I->toggleColumn('lastlogin');
        $I->toggleColumn('description');
        $I->see('Last login', '//thead//th');
        $I->see('Description', '//thead//th');

        // Open column modal and use the "select none" button
        $I->openColumnsModal();
        $I->switchToIFrame();
        $I->waitForElement('.modal', 5);

        // Click the "select none" button to uncheck all columns
        $I->click('button[data-action="select-none"]');
        $I->wait(0.5);

        // Close modal
        $I->click('.modal button.btn-primary');
        $I->wait(1);
        $I->switchToContentFrame();

        // Verify default columns are now visible
        $I->see('Username', '//thead//th');
        $I->see('Name', '//thead//th');

        // Verify custom columns are gone
        $I->dontSee('Last login', '//thead//th');
        $I->dontSee('Description', '//thead//th');
    }

    public function toggleSelectionButton(AcceptanceTester $I): void
    {
        $I->wantTo('verify that toggle selection button inverts column selection');

        // Verify default columns are visible
        $I->see('Username', '//thead//th');
        $I->see('Name', '//thead//th');
        $I->dontSee('Admin', '//thead//th');
        $I->dontSee('Limit to languages', '//thead//th');

        // Open column modal and click toggle selection
        $I->openColumnsModal();
        $I->switchToIFrame();
        $I->waitForElement('.modal', 5);

        // Click the toggle selection button to invert all selections
        $I->click('button[data-action="select-toggle"]');
        $I->wait(0.5);

        // Close modal
        $I->click('.modal button.btn-primary');
        $I->wait(1);
        $I->switchToContentFrame();

        // Verify that previously hidden columns are now visible
        $I->see('Admin', '//thead//th');
        $I->see('Limit to languages', '//thead//th');

        // Verify that previously visible columns are now hidden
        $I->dontSee('Username', '//thead//th');
        $I->dontSee('Name', '//thead//th');

        // Toggle again to verify it inverts back
        $I->openColumnsModal();
        $I->switchToIFrame();
        $I->waitForElement('.modal', 5);

        $I->click('button[data-action="select-toggle"]');
        $I->wait(0.5);

        $I->click('.modal button.btn-primary');
        $I->wait(1);
        $I->switchToContentFrame();

        // Verify we're back to the original state
        $I->see('Username', '//thead//th');
        $I->see('Name', '//thead//th');
        $I->dontSee('Admin', '//thead//th');
        $I->dontSee('Limit to languages', '//thead//th');
    }

    public function searchFilterInColumnsModal(AcceptanceTester $I): void
    {
        $I->wantTo('verify that search filter in columns modal filters available options');

        // Open column modal
        $I->openColumnsModal();
        $I->switchToIFrame();
        $I->waitForElement('.modal', 5);

        // Verify all columns are initially visible
        $I->seeElement('//label[contains(., "Username")]');
        $I->seeElement('//label[contains(., "Name")]');
        $I->seeElement('//label[contains(., "Last login")]');
        $I->seeElement('//label[contains(., "Description")]');

        // Type "user" in the filter - should show Username but hide others
        $I->fillField('input[name="columns-filter"]', 'user');
        // Trigger input event to ensure the filter is applied
        $I->executeJS("document.querySelector('input[name=\"columns-filter\"]').dispatchEvent(new Event('input', { bubbles: true }));");
        $I->wait(0.5);

        $I->seeElement('//label[contains(., "Username")]');
        $I->dontSeeElement('//label[contains(., "Name")]');
        $I->dontSeeElement('//label[contains(., "Last login")]');
        $I->dontSeeElement('//label[contains(., "Description")]');

        // Type "login" in the filter - should show Last login
        $I->fillField('input[name="columns-filter"]', 'login');
        // Trigger input event to ensure the filter is applied
        $I->executeJS("document.querySelector('input[name=\"columns-filter\"]').dispatchEvent(new Event('input', { bubbles: true }));");
        $I->wait(0.5);

        $I->dontSeeElement('//label[contains(., "Username")]');
        $I->dontSeeElement('//label[contains(., "Name")]');
        $I->seeElement('//label[contains(., "Last login")]');
        $I->dontSeeElement('//label[contains(., "Description")]');

        // Clear the filter - all columns should be visible again
        $I->fillField('input[name="columns-filter"]', '');
        // Trigger input event to ensure the filter is applied
        $I->executeJS("document.querySelector('input[name=\"columns-filter\"]').dispatchEvent(new Event('input', { bubbles: true }));");
        $I->wait(0.5);

        $I->seeElement('//label[contains(., "Username")]');
        $I->seeElement('//label[contains(., "Name")]');
        $I->seeElement('//label[contains(., "Last login")]');
        $I->seeElement('//label[contains(., "Description")]');

        // Close modal
        $I->click('.modal button.btn-primary');
        $I->wait(1);
        $I->switchToContentFrame();
    }
}

<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\BeUsers;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class BeUsersModuleCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsEditor();
    }

    public function moduleLoadsSuccessfully(AcceptanceTester $I): void
    {
        $I->wantTo('verify that the BeUsers module loads successfully');

        $I->switchToMainFrame();
        $I->click('//a[@data-moduleroute-identifier="example_beusers"]');
        $I->wait(1);

        $I->switchToContentFrame();
        $I->waitForElement('.recordlist-table', 10);
        $I->see('Backend Users');
    }

    public function allTablesAreAccessible(AcceptanceTester $I): void
    {
        $I->wantTo('verify that all three tables are accessible');

        $this->navigateToModule($I, 'example_beusers');

        $I->see('be_users', '.table-selector');
        $I->seeElement('//tr[@data-uid]');

        $I->selectOption('.table-selector', 'be_groups');
        $I->wait(1);
        $I->seeElement('.recordlist-table');

        $I->selectOption('.table-selector', 'sys_filemounts');
        $I->wait(1);
        $I->seeElement('.recordlist-table');
    }

    public function tableHeadersAreDisplayed(AcceptanceTester $I): void
    {
        $I->wantTo('verify that table headers are displayed correctly');

        $this->navigateToModule($I, 'example_beusers');

        $I->seeElement('//thead//th');
        $I->see('Username');
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

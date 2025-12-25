<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\BeUsers;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class BeUsersModuleCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $I->openModule('example_beusers');
    }

    public function allTablesAreAccessible(AcceptanceTester $I): void
    {
        $I->wantTo('verify that all three tables are accessible');

        $I->see('be_users', '.table-selector');
        $I->seeElement('//tr[@data-uid]');

        $I->switchTable('be_groups');
        $I->seeElement('main.recordlist');

        $I->switchTable('sys_filemounts');
        $I->seeElement('main.recordlist');
    }

    public function tableHeadersAreDisplayed(AcceptanceTester $I): void
    {
        $I->wantTo('verify that table headers are displayed correctly');

        $I->openModule('example_beusers');

        $I->seeElement('//thead//th');
        $I->see('Username');
    }
}

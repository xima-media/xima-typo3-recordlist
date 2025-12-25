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

        $I->toggleColumn('lastlogin');

        $I->see('Last login', '//thead//th');
    }

    public function columnSettingsPersist(AcceptanceTester $I): void
    {
        $I->wantTo('verify that column settings persist across page reload');

        $I->toggleColumn('lastlogin');

        $I->reload();
        $I->waitForElement('main.recordlist', 10);

        $I->see('Last login', '//thead//th');
    }
}

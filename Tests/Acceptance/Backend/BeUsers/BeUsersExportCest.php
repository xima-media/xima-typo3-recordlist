<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\BeUsers;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class BeUsersExportCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $I->openModule('example_beusers');
    }

    public function downloadModalOpens(AcceptanceTester $I): void
    {
        $I->click('.recordlist-download-button');

        // Modal opens in main frame
        $I->switchToIFrame();
        $I->waitForElement('.modal');
        $I->wait(2);
        $I->seeOptionIsSelected('.modal select[name="format"]', 'CSV');

        // Close modal
        $I->click('.modal button.btn-primary');
        $I->waitForElementNotVisible('.modal');
    }
}

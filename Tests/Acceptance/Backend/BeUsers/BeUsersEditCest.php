<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\BeUsers;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class BeUsersEditCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $I->openModule('example_beusers');
    }

    public function editButtonOpensForm(AcceptanceTester $I): void
    {
        $I->wantTo('verify that edit button opens full edit form');

        $uid = $I->getFirstRecordUid();

        $I->click('//tr[@data-uid="' . $uid . '"]//a[@aria-label="Edit"]');
        $I->waitForElement('.module-docheader', 5);

        $I->see('Edit');
    }
}

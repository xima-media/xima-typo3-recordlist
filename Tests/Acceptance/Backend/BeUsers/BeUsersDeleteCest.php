<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\BeUsers;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class BeUsersDeleteCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $I->openModule('example_beusers');
    }

    public function deleteRecordWithConfirmation(AcceptanceTester $I): void
    {
        $I->wantTo('delete a record with confirmation');

        $uid = $I->getFirstRecordUid();

        $I->deleteRecord($uid);

        $I->dontSeeRecordInTable($uid);
    }

    public function deleteCancelled(AcceptanceTester $I): void
    {
        $I->wantTo('cancel record deletion');

        $uid = $I->getFirstRecordUid();

        $I->click('//tr[@data-uid="' . $uid . '"]//a[@data-delete2]');

        // Modal opens in main frame
        $I->switchToMainFrame();
        $I->waitForElement('.modal', 5);
        $I->click('.modal button.btn-default');
        $I->wait(1);

        // Switch back to content frame to verify record still exists
        $I->switchToContentFrame();
        $I->seeRecordInTable($uid);
    }
}

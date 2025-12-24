<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\BeUsers;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class BeUsersDeleteCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $this->navigateToModule($I, 'example_beusers');
    }

    public function deleteRecordWithConfirmation(AcceptanceTester $I): void
    {
        $I->wantTo('delete a record with confirmation');

        $uid = $this->getFirstRecordUid($I);

        $I->click('//tr[@data-uid="' . $uid . '"]//a[@data-delete2]');

        // Modal opens in main frame
        $I->switchToMainFrame();
        $I->waitForElement('.modal', 5);
        $I->see('Are you sure');
        $I->click('.modal button.btn-warning');
        $I->wait(1);

        // Switch back to content frame to verify deletion
        $I->switchToContentFrame();
        $I->dontSeeElement('//tr[@data-uid="' . $uid . '"]');
    }

    public function deleteCancelled(AcceptanceTester $I): void
    {
        $I->wantTo('cancel record deletion');

        $uid = $this->getFirstRecordUid($I);

        $I->click('//tr[@data-uid="' . $uid . '"]//a[@data-delete2]');

        // Modal opens in main frame
        $I->switchToMainFrame();
        $I->waitForElement('.modal', 5);
        $I->click('.modal button.btn-secondary');
        $I->wait(1);

        // Switch back to content frame to verify record still exists
        $I->switchToContentFrame();
        $I->seeElement('//tr[@data-uid="' . $uid . '"]');
    }

    protected function navigateToModule(AcceptanceTester $I, string $moduleIdentifier): void
    {
        $I->switchToMainFrame();
        $I->click('//a[@data-modulemenu-identifier="' . $moduleIdentifier . '"]');
        $I->wait(1);
        $I->switchToContentFrame();
        $I->waitForElement('main.recordlist', 10);
    }

    protected function getFirstRecordUid(AcceptanceTester $I): int
    {
        $uid = $I->grabAttributeFrom('//tr[@data-uid]', 'data-uid');
        return (int) $uid;
    }
}

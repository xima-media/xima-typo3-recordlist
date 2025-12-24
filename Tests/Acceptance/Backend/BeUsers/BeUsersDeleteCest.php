<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\BeUsers;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class BeUsersDeleteCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsEditor();
        $this->navigateToModule($I, 'example_beusers');
    }

    public function deleteRecordWithConfirmation(AcceptanceTester $I): void
    {
        $I->wantTo('delete a record with confirmation');

        $uid = $this->getFirstRecordUid($I);

        $I->click('//tr[@data-uid="' . $uid . '"]//button[@data-action="delete"]');
        $I->waitForElement('.modal', 5);
        $I->see('Are you sure');
        $I->click('.modal button.btn-danger');
        $I->wait(1);

        $I->dontSeeElement('//tr[@data-uid="' . $uid . '"]');
    }

    public function deleteCancelled(AcceptanceTester $I): void
    {
        $I->wantTo('cancel record deletion');

        $uid = $this->getFirstRecordUid($I);

        $I->click('//tr[@data-uid="' . $uid . '"]//button[@data-action="delete"]');
        $I->waitForElement('.modal', 5);
        $I->click('.modal button.btn-secondary');
        $I->wait(1);

        $I->seeElement('//tr[@data-uid="' . $uid . '"]');
    }

    protected function navigateToModule(AcceptanceTester $I, string $moduleIdentifier): void
    {
        $I->switchToMainFrame();
        $I->click('//a[@data-moduleroute-identifier="' . $moduleIdentifier . '"]');
        $I->wait(1);
        $I->switchToContentFrame();
        $I->waitForElement('.recordlist-table', 10);
    }

    protected function getFirstRecordUid(AcceptanceTester $I): int
    {
        $uid = $I->grabAttributeFrom('//tr[@data-uid]', 'data-uid');
        return (int) $uid;
    }
}

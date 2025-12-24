<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\BeUsers;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class BeUsersEditCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $this->navigateToModule($I, 'example_beusers');
    }

    public function editButtonOpensForm(AcceptanceTester $I): void
    {
        $I->wantTo('verify that edit button opens full edit form');

        $uid = $this->getFirstRecordUid($I);

        $I->click('//tr[@data-uid="' . $uid . '"]//a[@aria-label="Edit"]');
        $I->waitForElement('.module-docheader', 5);

        $I->see('Edit');
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
        return (int)$uid;
    }
}

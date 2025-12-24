<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\BeUsers;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class BeUsersSortCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $this->navigateToModule($I, 'example_beusers');
    }

    public function sortByUsernameAscending(AcceptanceTester $I): void
    {
        $I->wantTo('sort backend users by username ascending');

        $I->click('//thead//th//a[contains(text(), "Username")]');
        $I->wait(1);

        $I->seeElement('main.recordlist');
    }

    public function sortByUsernameDescending(AcceptanceTester $I): void
    {
        $I->wantTo('sort backend users by username descending');

        $I->click('//thead//th//a[contains(text(), "Username")]');
        $I->wait(1);
        $I->click('//thead//th//a[contains(text(), "Username")]');
        $I->wait(1);

        $I->seeElement('main.recordlist');
    }

    public function sortByUid(AcceptanceTester $I): void
    {
        $I->wantTo('sort backend users by UID');

        $I->click('//thead//th[1]//a');
        $I->wait(1);

        $I->seeElement('main.recordlist');
    }

    protected function navigateToModule(AcceptanceTester $I, string $moduleIdentifier): void
    {
        $I->switchToMainFrame();
        $I->click('//a[@data-modulemenu-identifier="' . $moduleIdentifier . '"]');
        $I->wait(1);
        $I->switchToContentFrame();
        $I->waitForElement('main.recordlist', 10);
    }
}

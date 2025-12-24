<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\BeUsers;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class BeUsersSortCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsEditor();
        $this->navigateToModule($I, 'example_beusers');
    }

    public function sortByUsernameAscending(AcceptanceTester $I): void
    {
        $I->wantTo('sort backend users by username ascending');

        $I->click('//th[@data-column="username"]//a');
        $I->wait(1);

        $I->seeElement('//th[@data-column="username"][contains(@class, "sorted-asc")]');
    }

    public function sortByUsernameDescending(AcceptanceTester $I): void
    {
        $I->wantTo('sort backend users by username descending');

        $I->click('//th[@data-column="username"]//a');
        $I->wait(1);
        $I->click('//th[@data-column="username"]//a');
        $I->wait(1);

        $I->seeElement('//th[@data-column="username"][contains(@class, "sorted-desc")]');
    }

    public function sortByUid(AcceptanceTester $I): void
    {
        $I->wantTo('sort backend users by UID');

        $I->click('//th[@data-column="uid"]//a');
        $I->wait(1);

        $I->seeElement('.recordlist-table');
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

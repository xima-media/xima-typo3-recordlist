<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\BeUsers;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class BeUsersSortCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $I->openModule('example_beusers');
    }

    public function sortByUsernameAscending(AcceptanceTester $I): void
    {
        $I->wantTo('sort backend users by username ascending');

        $I->sortBy('Username', 'ASC');

        $I->seeElement('main.recordlist');
    }

    public function sortByUsernameDescending(AcceptanceTester $I): void
    {
        $I->wantTo('sort backend users by username descending');

        $I->sortBy('Username', 'DESC');

        $I->seeElement('main.recordlist');
    }
}

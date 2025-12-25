<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\BeUsers;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class BeUsersFilterCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $I->openModule('example_beusers');
    }

    public function filterByExactMatch(AcceptanceTester $I): void
    {
        $I->wantTo('filter backend users by exact match');

        $I->applyFilter('username', 'editor');

        $I->see('editor');
        $I->seeElement('//tr[@data-uid]');
    }

    public function clearAllFilters(AcceptanceTester $I): void
    {
        $I->wantTo('clear all filters');

        $I->applyFilter('username', 'editor');

        $I->click('input[name="reset"]');
        $I->wait(1);

        $I->seeElement('main.recordlist');
    }
}

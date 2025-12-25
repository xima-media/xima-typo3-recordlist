<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\BeUsers;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class BeUsersSearchCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $I->openModule('example_beusers');
    }

    public function searchByUsername(AcceptanceTester $I): void
    {
        $I->wantTo('search for backend users by username');

        $I->searchFor('editor');

        $I->see('editor');
    }

    public function searchWithNoResults(AcceptanceTester $I): void
    {
        $I->wantTo('verify that search with no results shows empty table');

        $I->searchFor('nonexistentuser');

        $I->dontSeeElement('//tr[@data-uid]');
    }

    public function searchFieldPersists(AcceptanceTester $I): void
    {
        $I->wantTo('verify that search field persists after search');

        $I->searchFor('editor');

        $I->seeInField('input[name="search_field"]', 'editor');
    }
}

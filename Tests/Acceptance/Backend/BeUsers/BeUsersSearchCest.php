<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\BeUsers;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class BeUsersSearchCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsEditor();
        $this->navigateToModule($I, 'example_beusers');
    }

    public function searchByUsername(AcceptanceTester $I): void
    {
        $I->wantTo('search for backend users by username');

        $I->fillField('input[name="search_field"]', 'editor');
        $I->click('button[type="submit"]');
        $I->wait(1);

        $I->see('editor');
    }

    public function searchWithNoResults(AcceptanceTester $I): void
    {
        $I->wantTo('verify that search with no results shows empty table');

        $I->fillField('input[name="search_field"]', 'nonexistentuser');
        $I->click('button[type="submit"]');
        $I->wait(1);

        $I->see('No records found');
    }

    public function searchFieldPersists(AcceptanceTester $I): void
    {
        $I->wantTo('verify that search field persists after search');

        $I->fillField('input[name="search_field"]', 'editor');
        $I->click('button[type="submit"]');
        $I->wait(1);

        $I->seeInField('input[name="search_field"]', 'editor');
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

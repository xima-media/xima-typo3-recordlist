<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\BeUsers;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class BeUsersFilterCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $this->navigateToModule($I, 'example_beusers');
    }

    public function filterByExactMatch(AcceptanceTester $I): void
    {
        $I->wantTo('filter backend users by exact match');

        $I->click('.toggleSearchButton');
        $I->waitForElementVisible('input[name="filter[username][value]"]', 5);
        $I->fillField('input[name="filter[username][value]"]', 'editor');
        $I->click('button[type="submit"]');
        $I->wait(1);

        $I->see('editor');
        $I->seeElement('//tr[@data-uid]');
    }

    public function clearAllFilters(AcceptanceTester $I): void
    {
        $I->wantTo('clear all filters');

        $I->click('.toggleSearchButton');
        $I->waitForElementVisible('input[name="filter[username][value]"]', 5);
        $I->fillField('input[name="filter[username][value]"]', 'editor');
        $I->click('button[type="submit"]');
        $I->wait(1);

        $I->click('input[name="reset"]');
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

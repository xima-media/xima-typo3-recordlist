<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\BeUsers;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class BeUsersFilterCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsEditor();
        $this->navigateToModule($I, 'example_beusers');
    }

    public function filterByExactMatch(AcceptanceTester $I): void
    {
        $I->wantTo('filter backend users by exact match');

        $I->fillField('input[name="filter[username][value]"]', 'editor');
        $I->click('button[type="submit"]');
        $I->wait(1);

        $I->see('editor');
        $I->seeElement('//tr[@data-uid]');
    }

    public function filterByLikeOperator(AcceptanceTester $I): void
    {
        $I->wantTo('filter backend users using LIKE operator');

        $I->fillField('input[name="filter[username][value]"]', 'edit%');
        $I->selectOption('select[name="filter[username][expr]"]', 'like');
        $I->click('button[type="submit"]');
        $I->wait(1);

        $I->see('editor');
    }

    public function clearAllFilters(AcceptanceTester $I): void
    {
        $I->wantTo('clear all filters');

        $I->fillField('input[name="filter[username][value]"]', 'editor');
        $I->click('button[type="submit"]');
        $I->wait(1);

        $I->click('//button[@title="Clear filters"]');
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

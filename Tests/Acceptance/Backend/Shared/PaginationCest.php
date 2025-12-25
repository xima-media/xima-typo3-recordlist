<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\Shared;

use Codeception\Attribute\Skip;
use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class PaginationCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $I->switchToMainFrame();
        $I->openModule('example_beusers');
    }

    #[Skip('Not enough records in fixture to trigger pagination')]
    public function paginationIsVisible(AcceptanceTester $I): void
    {
        $I->wantTo('verify that pagination controls are visible');

        $I->seeElement('.pagination');
    }

    #[Skip('Not enough records in fixture to trigger pagination')]
    public function navigateToNextPage(AcceptanceTester $I): void
    {
        $I->wantTo('navigate to the next page');

        $I->click('.pagination .next');
        $I->wait(1);

        $I->seeElement('main.recordlist');
    }

    #[Skip('Not enough records in fixture to trigger pagination')]
    public function navigateToPreviousPage(AcceptanceTester $I): void
    {
        $I->wantTo('navigate to the previous page');

        $I->click('.pagination .next');
        $I->wait(1);
        $I->click('.pagination .previous');
        $I->wait(1);

        $I->seeElement('main.recordlist');
    }

    #[Skip('Not enough records in fixture to trigger pagination')]
    public function jumpToSpecificPage(AcceptanceTester $I): void
    {
        $I->wantTo('jump to a specific page number');

        $I->goToPage(2);

        $I->see('2', '.pagination .active');
    }

    #[Skip('Not enough records in fixture to trigger pagination')]
    public function paginationPersistsFilters(AcceptanceTester $I): void
    {
        $I->wantTo('verify that pagination persists filters');

        $I->click('.toggleSearchButton');
        $I->waitForElementVisible('input[name="search_field"]', 5);
        $I->fillField('input[name="search_field"]', 'editor');
        $I->click('button[type="submit"]');
        $I->wait(1);

        $I->click('.pagination .next');
        $I->wait(1);

        $I->seeInField('input[name="search_field"]', 'editor');
    }
}

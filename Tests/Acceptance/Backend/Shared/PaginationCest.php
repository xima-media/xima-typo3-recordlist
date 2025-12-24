<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\Shared;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class PaginationCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $this->navigateToModule($I, 'example_beusers');
    }

    public function paginationIsVisible(AcceptanceTester $I): void
    {
        $I->wantTo('verify that pagination controls are visible');

        $I->seeElement('.pagination');
    }

    public function navigateToNextPage(AcceptanceTester $I): void
    {
        $I->wantTo('navigate to the next page');

        $I->click('.pagination .next');
        $I->wait(1);

        $I->seeElement('main.recordlist');
    }

    public function navigateToPreviousPage(AcceptanceTester $I): void
    {
        $I->wantTo('navigate to the previous page');

        $I->click('.pagination .next');
        $I->wait(1);
        $I->click('.pagination .previous');
        $I->wait(1);

        $I->seeElement('main.recordlist');
    }

    public function jumpToSpecificPage(AcceptanceTester $I): void
    {
        $I->wantTo('jump to a specific page number');

        $I->click('//button[@data-page="2"]');
        $I->wait(1);

        $I->see('2', '.pagination .active');
    }

    public function paginationPersistsFilters(AcceptanceTester $I): void
    {
        $I->wantTo('verify that pagination persists filters');

        $I->fillField('input[name="search_field"]', 'editor');
        $I->click('button[type="submit"]');
        $I->wait(1);

        $I->click('.pagination .next');
        $I->wait(1);

        $I->seeInField('input[name="search_field"]', 'editor');
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

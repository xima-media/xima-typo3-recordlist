<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\Shared;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class PaginationCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $I->switchToMainFrame();
        $I->openModule('example_news');
    }

    public function paginationIsVisible(AcceptanceTester $I): void
    {
        $I->wantTo('verify that pagination controls are visible');

        $I->seeElement('.pagination');
    }

    public function navigateToNextPage(AcceptanceTester $I): void
    {
        $I->wantTo('navigate to the next page');

        $I->click('.pagination .page-link[title="Next"]');
        $I->wait(1);

        $I->seeElement('main.recordlist');
    }

    public function navigateToPreviousPage(AcceptanceTester $I): void
    {
        $I->wantTo('navigate to the previous page');

        $I->click('.pagination .page-link[title="Next"]');
        $I->wait(1);
        $I->click('.pagination .page-link[title="Previous"]');
        $I->wait(1);

        $I->seeElement('main.recordlist');
    }

    public function jumpToSpecificPage(AcceptanceTester $I): void
    {
        $I->wantTo('jump to a specific page number');

        $I->fillField('.pagination .paginator-input', '2');
        $I->click('.pagination a[data-action="pagination-jump"]');
        $I->wait(1);
        $I->switchToContentFrame();

        $I->seeInField('.pagination .paginator-input', '2');
    }

    public function paginationPersistsFilters(AcceptanceTester $I): void
    {
        $I->wantTo('verify that pagination persists filters');

        $I->searchFor('E');

        $I->click('.pagination .page-link[title="Next"]');
        $I->wait(1);

        $I->seeInField('input[name="search_field"]', 'E');
    }

    public function itemsPerPageDropdownIsVisible(AcceptanceTester $I): void
    {
        $I->wantTo('verify that items per page dropdown is visible');

        $I->seeElement('select[name="items_per_page"]');
    }

    public function changeItemsPerPage(AcceptanceTester $I): void
    {
        $I->wantTo('change items per page value');

        $I->selectOption('select[name="items_per_page"]', '50');
        $I->click('.pagination a[data-action="pagination-jump"]');

        $I->seeOptionIsSelected('select[name="items_per_page"]', '50');
    }

    public function itemsPerPagePersistsAcrossRequests(AcceptanceTester $I): void
    {
        $I->wantTo('verify that items per page setting persists across requests');

        $I->selectOption('select[name="items_per_page"]', '50');
        $I->wait(1);

        $I->reloadPage();
        $I->switchToContentFrame();

        $I->seeOptionIsSelected('select[name="items_per_page"]', '50');
    }

    public function itemsPerPageResetsWithResetButton(AcceptanceTester $I): void
    {
        $I->wantTo('verify that items per page resets when reset button is clicked');

        $I->selectOption('select[name="items_per_page"]', '200');
        $I->wait(1);

        $I->click('.toggleSearchButton');

        $I->scrollTo('input[name="reset"]');
        $I->wait(0.3);
        $I->executeJS('document.querySelector(\'input[name="reset"]\').click();');
        $I->wait(1);

        $I->seeOptionIsSelected('select[name="items_per_page"]', '25');
    }
}

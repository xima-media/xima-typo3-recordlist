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

    public function navigateToNextAndPreviousPage(AcceptanceTester $I): void
    {
        $I->wantTo('verify that pagination working for next and previous page navigation');
        $I->seeNumberOfElements('.pagination', 2);

        $I->amGoingTo('navigate to the next page');
        $I->click('.pagination .page-link[title="Next"]');
        $I->wait(1);
        $I->waitForText('Records 26 - 50', 5, '.pagination');

        $I->amGoingTo('navigate to the previous page');
        $I->click('.pagination .page-link[title="Previous"]');
        $I->wait(1);
        $I->waitForText('Records 1 - 25', 5, '.pagination');
    }

    public function jumpToSpecificPage(AcceptanceTester $I): void
    {
        $I->wantTo('jump to a specific page number');

        $I->fillField('.pagination input[name="current_page"]', '3');
        $I->click('.pagination a[data-action="pagination-jump"]');
        $I->wait(1);

        $I->waitForText('Records 51 - 75', 5, '.pagination');
        $I->seeInField('.pagination .paginator-input', '3');
    }

    public function paginationPersistsFilters(AcceptanceTester $I): void
    {
        $I->wantTo('verify that pagination persists filters');

        $I->searchFor('E');

        $I->click('.pagination .page-link[title="Next"]');
        $I->wait(1);

        $I->seeInField('input[name="search_field"]', 'E');
    }

    public function changeItemsPerPage(AcceptanceTester $I): void
    {
        $I->wantTo('change items per page value');

        $I->seeElement('select[name="items_per_page"]');
        $I->selectOption('select[name="items_per_page"]', '50');
        $I->click('.pagination a[data-action="pagination-jump"]');
        $I->wait(1);

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

        $I->click('.toggleFiltersButton');

        $I->scrollTo('input[name="reset"]');
        $I->wait(0.3);
        $I->executeJS('document.querySelector(\'input[name="reset"]\').click();');
        $I->wait(1);

        $I->seeOptionIsSelected('select[name="items_per_page"]', '25');
    }
}

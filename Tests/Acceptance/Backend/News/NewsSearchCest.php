<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\News;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class NewsSearchCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsEditor();
        $this->navigateToModule($I, 'example_news');
    }

    public function searchByTitle(AcceptanceTester $I): void
    {
        $I->wantTo('search for news by title');

        $I->fillField('input[name="search_field"]', 'News');
        $I->click('button[type="submit"]');
        $I->wait(1);

        $I->see('News');
        $I->seeElement('//tr[@data-uid]');
    }

    public function searchByAuthor(AcceptanceTester $I): void
    {
        $I->wantTo('search for news by author');

        $I->fillField('input[name="search_field"]', 'Author');
        $I->click('button[type="submit"]');
        $I->wait(1);

        $I->seeElement('.recordlist-table');
    }

    public function searchWithNoResults(AcceptanceTester $I): void
    {
        $I->wantTo('verify that search with no results shows empty message');

        $I->fillField('input[name="search_field"]', 'NonexistentNewsTitle12345');
        $I->click('button[type="submit"]');
        $I->wait(1);

        $I->see('No records found');
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

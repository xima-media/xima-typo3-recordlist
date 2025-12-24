<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\News;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class NewsSearchCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $this->navigateToModule($I, 'example_news');
    }

    public function searchByTitle(AcceptanceTester $I): void
    {
        $I->wantTo('search for news by title');

        $I->click('.toggleSearchButton');
        $I->waitForElementVisible('input[name="search_field"]', 5);
        $I->fillField('input[name="search_field"]', 'News');
        $I->click('button[type="submit"]');
        $I->wait(1);

        $I->see('News');
        $I->seeElement('//tr[@data-uid]');
    }

    public function searchByAuthor(AcceptanceTester $I): void
    {
        $I->wantTo('search for news by author');

        $I->click('.toggleSearchButton');
        $I->waitForElementVisible('input[name="search_field"]', 5);
        $I->fillField('input[name="search_field"]', 'Author');
        $I->click('button[type="submit"]');
        $I->wait(1);

        $I->seeElement('main.recordlist');
    }

    public function searchWithNoResults(AcceptanceTester $I): void
    {
        $I->wantTo('verify that search with no results shows empty message');

        $I->click('.toggleSearchButton');
        $I->waitForElementVisible('input[name="search_field"]', 5);
        $I->fillField('input[name="search_field"]', 'NonexistentNewsTitle12345');
        $I->click('button[type="submit"]');
        $I->wait(1);

        $I->dontSeeElement('//tr[@data-uid]');
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

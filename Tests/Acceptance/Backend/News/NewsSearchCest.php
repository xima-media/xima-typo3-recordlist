<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\News;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class NewsSearchCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $I->openModule('example_news');
    }

    public function searchByTitle(AcceptanceTester $I): void
    {
        $I->wantTo('search for news by title');

        $I->searchFor('Employee');

        $I->see('Employee');
        $I->seeNumberOfElements('//tr[@data-uid]', 3);
    }

    public function searchByGermanTitle(AcceptanceTester $I): void
    {
        $I->wantTo('search for news by german title');

        $I->searchFor('Kundenzufriedenheit');

        $I->see('Kundenzufriedenheit');
        $I->seeNumberOfElements('//tr[@data-uid]', 1);
    }

    public function searchWithNoResults(AcceptanceTester $I): void
    {
        $I->wantTo('verify that search with no results shows empty message');

        $I->searchFor('NonexistentNewsTitle12345');

        $I->dontSeeElement('//tr[@data-uid]');
    }
}

<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\News;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class NewsSortCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $I->openModule('example_news');
    }

    public function sortByAuthorAscending(AcceptanceTester $I): void
    {
        $I->wantTo('sort news by author name ascending and validate first author is Albert Kelly');

        // Sort by author column ascending
        $I->sortBy('Author');

        // Get the first record's author name
        $firstAuthor = $I->grabTextFrom('//tr[@data-uid][1]//span[contains(@id, "author-")]');

        // Validate that the first author name is 'Albert Kelly'
        $I->assertEquals('Albert Kelly', $firstAuthor, 'First author should be "Albert Kelly"');
    }

    public function sortByAuthorDescending(AcceptanceTester $I): void
    {
        $I->wantTo('sort news by author name descending and validate first author is Zoe Bennett');

        // Sort by author column descending
        $I->sortBy('Author', 'DESC');

        // Get the first record's author name
        $firstAuthor = $I->grabTextFrom('//tr[@data-uid][1]//span[contains(@id, "author-")]');

        // Validate that the first author name is 'Zoe Bennett'
        $I->assertEquals('Zoe Bennett', $firstAuthor, 'First author should be "Zoe Bennett"');
    }
}

<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\News;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class NewsFilterCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $I->openModule('example_news');
    }

    public function filterByLanguageEnglish(AcceptanceTester $I): void
    {
        $I->wantTo('filter news by English language');

        $I->click('.toggleSearchButton');
        $I->waitForElementVisible('select[name="language"]', 5);
        $I->selectOption('select[name="language"]', '0');
        $I->click('button[type="submit"]');
        $I->wait(1);

        $I->seeElement('//tr[@data-uid]');
    }

    public function filterByLanguageGerman(AcceptanceTester $I): void
    {
        $I->wantTo('filter news by German language');

        $I->click('.toggleSearchButton');
        $I->waitForElementVisible('select[name="language"]', 5);
        $I->selectOption('select[name="language"]', '1');
        $I->click('button[type="submit"]');
        $I->wait(1);

        $I->seeElement('main.recordlist');
    }

    public function filterByAuthor(AcceptanceTester $I): void
    {
        $I->wantTo('filter news by author');

        $I->applyFilter('author', 'Author');

        $I->seeElement('main.recordlist');
    }
}

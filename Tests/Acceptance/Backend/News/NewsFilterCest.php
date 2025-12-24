<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\News;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class NewsFilterCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsEditor();
        $this->navigateToModule($I, 'example_news');
    }

    public function filterByLanguageEnglish(AcceptanceTester $I): void
    {
        $I->wantTo('filter news by English language');

        $I->selectOption('select[name="language"]', '0');
        $I->click('button[type="submit"]');
        $I->wait(1);

        $I->seeElement('//tr[@data-uid]');
    }

    public function filterByLanguageGerman(AcceptanceTester $I): void
    {
        $I->wantTo('filter news by German language');

        $I->selectOption('select[name="language"]', '1');
        $I->click('button[type="submit"]');
        $I->wait(1);

        $I->seeElement('.recordlist-table');
    }

    public function filterByAuthor(AcceptanceTester $I): void
    {
        $I->wantTo('filter news by author');

        $I->fillField('input[name="filter[author][value]"]', 'Author');
        $I->click('button[type="submit"]');
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

<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\News;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class NewsModuleCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
    }

    public function moduleLoadsSuccessfully(AcceptanceTester $I): void
    {
        $I->wantTo('verify that the News module loads successfully');

        $I->switchToMainFrame();
        $I->click('//a[@data-modulemenu-identifier="example_news"]');
        $I->wait(1);

        $I->switchToContentFrame();
        $I->waitForElement('main.recordlist', 10);
        $I->see('News');
    }

    public function defaultColumnsAreDisplayed(AcceptanceTester $I): void
    {
        $I->wantTo('verify that default columns are displayed');

        $this->navigateToModule($I, 'example_news');

        $I->seeElement('//thead//th');
        $I->seeElement('//tr[@data-uid]');
    }

    public function recordsFromCorrectPidAreShown(AcceptanceTester $I): void
    {
        $I->wantTo('verify that records from PID 15 are displayed');

        $this->navigateToModule($I, 'example_news');

        $I->seeElement('//tr[@data-uid]');
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

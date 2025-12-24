<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\News;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class NewsEditCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $this->navigateToModule($I, 'example_news');
    }

    public function inlineEditTitle(AcceptanceTester $I): void
    {
        $I->wantTo('edit news title field inline');

        $uid = $this->getFirstRecordUid($I);

        $I->click('//tr[@data-uid="' . $uid . '"]//input[@data-field="title"]');
        $I->fillField('//tr[@data-uid="' . $uid . '"]//input[@data-field="title"]', 'Modified News Title');
        $I->pressKey('//tr[@data-uid="' . $uid . '"]//input[@data-field="title"]', \Facebook\WebDriver\WebDriverKeys::ENTER);
        $I->wait(1);

        $I->see('Modified News Title');
    }

    public function editButtonOpensForm(AcceptanceTester $I): void
    {
        $I->wantTo('verify that edit button opens full edit form');

        $uid = $this->getFirstRecordUid($I);

        $I->click('//tr[@data-uid="' . $uid . '"]//a[@title="Edit"]');
        $I->waitForElement('.module-docheader', 5);

        $I->see('Edit');
    }

    public function workspaceVersionIsCreated(AcceptanceTester $I): void
    {
        $I->wantTo('verify that workspace version is created on edit');

        $uid = $this->getFirstRecordUid($I);

        $I->click('//tr[@data-uid="' . $uid . '"]//a[@title="Edit"]');
        $I->waitForElement('.module-docheader', 5);

        $I->fillField('input[data-formengine-input-name="data[tx_news_domain_model_news][' . $uid . '][title]"]', 'Workspace Modified Title');
        $I->click('button[name="_savedok"]');
        $I->wait(2);

        $this->navigateToModule($I, 'example_news');

        $I->see('Workspace Modified Title');
    }

    protected function navigateToModule(AcceptanceTester $I, string $moduleIdentifier): void
    {
        $I->switchToMainFrame();
        $I->click('//a[@data-modulemenu-identifier="' . $moduleIdentifier . '"]');
        $I->wait(1);
        $I->switchToContentFrame();
        $I->waitForElement('main.recordlist', 10);
    }

    protected function getFirstRecordUid(AcceptanceTester $I): int
    {
        $uid = $I->grabAttributeFrom('//tr[@data-uid]', 'data-uid');
        return (int) $uid;
    }
}

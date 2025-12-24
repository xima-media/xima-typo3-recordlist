<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\News;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class NewsPublishCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $this->navigateToModule($I, 'example_news');
    }

    public function markRecordAsReadyToPublish(AcceptanceTester $I): void
    {
        $I->wantTo('mark a news record as ready to publish');

        $uid = $this->getFirstRecordUid($I);

        $I->click('//tr[@data-uid="' . $uid . '"]//a[@aria-label="Edit"]');
        $I->waitForElement('.module-docheader', 5);

        $I->fillField('input[data-formengine-input-name="data[tx_news_domain_model_news][' . $uid . '][title]"]', 'Modified Title');
        $I->click('button[name="_savedok"]');
        $I->wait(2);

        $this->navigateToModule($I, 'example_news');

        $I->click('//tr[@data-uid="' . $uid . '"]//button[@data-action="ready-to-publish"]');
        $I->wait(2);

        $I->see('Waiting for Review');
    }

    public function filterReadyToPublishRecords(AcceptanceTester $I): void
    {
        $I->wantTo('filter to show only records ready to publish');

        $I->click('.toggleSearchButton');
        $I->waitForElementVisible('input[name="is_ready_to_publish"]', 5);
        $I->checkOption('input[name="is_ready_to_publish"]');
        $I->click('button[type="submit"]');
        $I->wait(1);

        $I->seeElement('main.recordlist');
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
        return (int)$uid;
    }
}

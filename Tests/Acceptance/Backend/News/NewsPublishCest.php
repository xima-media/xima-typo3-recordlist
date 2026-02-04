<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\News;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class NewsPublishCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $I->openModule('example_news');
    }

    public function markRecordAsReadyToPublish(AcceptanceTester $I): void
    {
        $I->wantTo('mark a news record as ready to publish');

        // Get the UID of the first record and open its edit form
        $uid = $I->getFirstRecordUid();
        $I->click('//tr[@data-uid="' . $uid . '"]//a[@aria-label="Edit"]');
        $I->waitForElement('.module-docheader');

        // Modify the record to create a workspace version
        $I->fillField('input[data-formengine-input-name="data[tx_news_domain_model_news][' . $uid . '][title]"]', 'Modified Title');
        $I->click('button[name="_savedok"]');
        $I->wait(1);
        $I->click('a.t3js-editform-close');
        $I->waitForElementVisible('main.recordlist', 5);

        $I->click('//tr[@data-t3ver_oid="' . $uid . '"]//a[@data-workspace-action="sendToSpecificStageExecute"][@data-workspace-stage="-10"]');

        // See and fill confirmation modal
        $I->switchToIFrame();
        $I->waitForElement('.modal');
        $I->see('Send to stage', '.modal');
        $I->fillField('.modal textarea[name="comments"]', 'Ready to publish this news item.');
        $I->click('.modal button.btn-primary');
        $I->wait(2);

        // Verify the record is now marked as "Awaiting review" in the list
        $I->switchToContentFrame();
        $I->see('Awaiting review', '//tr[@data-t3ver_oid="' . $uid . '"]');
    }
}

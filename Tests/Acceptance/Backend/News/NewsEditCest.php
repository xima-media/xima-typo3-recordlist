<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\News;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class NewsEditCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $I->openModule('example_news');
    }

    public function inlineEditTitle(AcceptanceTester $I): void
    {
        $I->wantTo('edit news title field inline');

        $uid = $I->getFirstRecordUid();

        $I->inlineEdit($uid, 'title', 'Modified News Title');

        $I->see('Modified News Title');
    }

    public function editButtonOpensForm(AcceptanceTester $I): void
    {
        $I->wantTo('verify that edit button opens full edit form');

        $uid = $I->getFirstRecordUid();

        $I->click('//tr[@data-uid="' . $uid . '"]//a[@aria-label="Edit"]');
        $I->waitForElement('.module-docheader', 5);

        $I->see('Edit');
    }

    public function workspaceVersionIsCreated(AcceptanceTester $I): void
    {
        $I->wantTo('verify that workspace version is created on edit');

        $uid = $I->getFirstRecordUid();

        $I->click('//tr[@data-uid="' . $uid . '"]//a[@aria-label="Edit"]');
        $I->waitForElement('.module-docheader', 5);

        $I->fillField('input[data-formengine-input-name="data[tx_news_domain_model_news][' . $uid . '][title]"]', 'Workspace Modified Title');
        $I->click('button[name="_savedok"]');
        $I->wait(2);

        $I->openModule('example_news');

        $I->see('Workspace Modified Title');
    }
}

<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\News;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class NewsWorkspaceCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $I->openModule('example_news');
    }

    public function workspaceColumnIsAvailable(AcceptanceTester $I): void
    {
        $I->wantTo('verify that workspace status column is available');

        $I->click('.showColumnsButton');
        $I->switchToMainFrame();
        $I->waitForElement('.modal', 5);
        $I->seeElement('input[name="columns[workspace-status]"]');
        $I->click('.modal .btn-close');
        $I->wait(1);
        $I->switchToContentFrame();
    }

    public function workspaceRecordsShowStatusBadge(AcceptanceTester $I): void
    {
        $I->wantTo('verify that workspace records show status badge');

        $I->click('.showColumnsButton');
        $I->switchToMainFrame();
        $I->waitForElement('.modal', 5);
        $I->checkOption('input[name="columns[workspace-status]"]');
        $I->click('.modal button.btn-primary');
        $I->wait(1);

        $I->switchToContentFrame();
        $I->seeElement('//thead//th[contains(text(), "Workspace")]');
    }

    public function filterOfflineRecords(AcceptanceTester $I): void
    {
        $I->wantTo('filter to show only offline records');

        $I->click('.toggleSearchButton');
        $I->waitForElementVisible('input[name="is_offline"]', 5);
        $I->checkOption('input[name="is_offline"]');
        $I->click('button[type="submit"]');
        $I->wait(1);

        $I->seeElement('main.recordlist');
    }
}

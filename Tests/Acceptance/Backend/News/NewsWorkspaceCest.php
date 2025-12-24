<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\News;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class NewsWorkspaceCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $this->navigateToModule($I, 'example_news');
    }

    public function workspaceColumnIsAvailable(AcceptanceTester $I): void
    {
        $I->wantTo('verify that workspace status column is available');

        $I->click('.showColumnsButton');
        $I->waitForElement('.column-settings-modal', 5);
        $I->seeElement('input[name="columns[workspace-status]"]');
        $I->click('.column-settings-modal button.btn-close');
    }

    public function workspaceRecordsShowStatusBadge(AcceptanceTester $I): void
    {
        $I->wantTo('verify that workspace records show status badge');

        $I->click('.showColumnsButton');
        $I->waitForElement('.column-settings-modal', 5);
        $I->checkOption('input[name="columns[workspace-status]"]');
        $I->click('.column-settings-modal button[type="submit"]');
        $I->wait(1);

        $I->seeElement('//th[@data-column="workspace-status"]');
    }

    public function filterOfflineRecords(AcceptanceTester $I): void
    {
        $I->wantTo('filter to show only offline records');

        $I->checkOption('input[name="is_offline"]');
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
}

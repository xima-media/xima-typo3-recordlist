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

    private function assertRecordIsModified(AcceptanceTester $I): void
    {
        // Verify the record has modified state
        $I->seeElement('//tr[@data-state="modified"]');

        // Verify the title has workspace-state-modified class
        $I->seeElement('//span[@class="workspace-state-modified"]');

        // Verify "Copy" badge is visible for modified records
        $I->see('Copy', '.badge-warning');
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
        $I->waitForElement('.module-docheader');

        $I->fillField('input[data-formengine-input-name="data[tx_news_domain_model_news][' . $uid . '][title]"]', 'Workspace Modified Title');
        $I->click('button[name="_savedok"]');
        $I->wait(1);
        $I->click('a.t3js-editform-close');
        $I->waitForElementVisible('main.recordlist');

        $I->see('Workspace Modified Title');
    }

    public function editedRecordIsMarkedAsModified(AcceptanceTester $I): void
    {
        $I->wantTo('verify that edited records are marked with modified state');

        $uid = $I->getFirstRecordUid();

        $I->click('//tr[@data-uid="' . $uid . '"]//a[@aria-label="Edit"]');
        $I->waitForElement('.module-docheader', 5);

        $I->fillField('input[data-formengine-input-name="data[tx_news_domain_model_news][' . $uid . '][title]"]', 'Modified Record State Test');
        $I->click('button[name="_savedok"]');
        $I->wait(2);

        $I->switchToMainFrame();
        $I->openModule('example_news');

        $this->assertRecordIsModified($I);
    }

    public function deletedRecordIsMarkedInWorkspace(AcceptanceTester $I): void
    {
        $I->wantTo('verify that record deletion is marked in workspace (not permanently deleted)');

        $uid = $I->getFirstRecordUid();

        $I->deleteRecord($uid);

        // Verify the record is marked as deleted
        $I->seeElement('//tr[@data-state="deleted"]');

        // Verify the record shows workspace-state-deleted styling
        $I->seeElement('//span[@class="workspace-state-deleted"]');
    }

    public function inlineEditAuthorCreatesWorkspaceVersion(AcceptanceTester $I): void
    {
        $I->wantTo('verify that inline editing of author field creates workspace version');

        $uid = $I->getFirstRecordUid();

        // Click the inline editable author field
        $I->click('//tr[@data-uid="' . $uid . '"]//span[@id="author-' . $uid . '"]');
        $I->wait(0.5);

        // Clear and type new value
        $I->executeJS('document.getElementById("author-' . $uid . '").textContent = ""');
        $I->type('Workspace Author Edit');

        // Click save button
        $I->click('//tr[@data-uid="' . $uid . '"]//button[@data-action="save"]');
        $I->wait(1);

        // Reload to see the changes
        $I->reloadPage();
        $I->switchToContentFrame();

        // Verify the record is marked as modified
        $this->assertRecordIsModified($I);

        // Verify the new author value is displayed
        $I->see('Workspace Author Edit');
    }
}

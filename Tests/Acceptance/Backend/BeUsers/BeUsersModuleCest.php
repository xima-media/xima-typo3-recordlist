<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\BeUsers;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class BeUsersModuleCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $I->openModule('example_beusers');
    }

    public function allTablesAreAccessible(AcceptanceTester $I): void
    {
        $I->wantTo('verify that all three tables are accessible');

        $I->see('Backend user');
        $I->seeElement('//tr[@data-uid]');

        $I->switchTable('Backend usergroup');
        $I->seeElement('main.recordlist');
        $I->see('4 Records');

        $I->switchTable('File mount');
        $I->seeElement('main.recordlist');
        $I->see('3 Records');
    }

    public function searchInputsArePersistedPerTable(AcceptanceTester $I): void
    {
        $I->wantTo('verify that search inputs are persisted on a per-table level');

        // Search in Backend user table
        $I->searchFor('admin');
        $I->seeInField('input[name="search_field"]', 'admin');
        $I->see('admin');

        // Switch to Backend usergroup table
        $I->switchTable('Backend usergroup');
        $I->seeElement('main.recordlist');

        // Verify search field is empty in the new table
        $I->click('.toggleSearchButton');
        $I->waitForElementVisible('input[name="search_field"]', 5);
        $I->seeInField('input[name="search_field"]', '');

        // Do a different search in Backend usergroup table
        $I->fillField('input[name="search_field"]', 'Editors');
        $I->click('button[type="submit"]');
        $I->wait(1);
        $I->seeInField('input[name="search_field"]', 'Editors');

        // Switch to File mount table
        $I->switchTable('File mount');
        $I->seeElement('main.recordlist');

        // Verify search field is empty in this table too
        $I->click('.toggleSearchButton');
        $I->waitForElementVisible('input[name="search_field"]', 5);
        $I->seeInField('input[name="search_field"]', '');

        // Switch back to Backend user table
        $I->switchTable('Backend user');
        $I->seeElement('main.recordlist');

        // Verify the original search is still there
        $I->seeInField('input[name="search_field"]', 'admin');
        $I->see('admin');

        // Switch back to Backend usergroup table
        $I->switchTable('Backend usergroup');
        $I->seeElement('main.recordlist');

        // Verify the Backend usergroup search is still there
        $I->seeInField('input[name="search_field"]', 'Editors');
    }
}

<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\BeUsers;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class BeUsersExportCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $I->openModule('example_beusers');
    }

    public function exportAsCsv(AcceptanceTester $I): void
    {
        $I->wantTo('export records as CSV');

        $I->exportRecords('csv');
    }

    public function exportAsJson(AcceptanceTester $I): void
    {
        $I->wantTo('export records as JSON');

        $I->exportRecords('json');
    }

    public function exportAsXlsx(AcceptanceTester $I): void
    {
        $I->wantTo('export records as XLSX');

        $I->exportRecords('xlsx');
    }
}

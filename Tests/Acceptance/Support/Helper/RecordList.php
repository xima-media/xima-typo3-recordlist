<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\Helper;

use Codeception\Module;
use Codeception\Module\WebDriver;

class RecordList extends Module
{
    protected function getWebDriver(): WebDriver
    {
        return $this->getModule('WebDriver');
    }

    public function openModule(string $moduleIdentifier): void
    {
        $I = $this->getWebDriver();
        $I->click('//a[@data-modulemenu-identifier="' . $moduleIdentifier . '"]');
        $I->switchToContentFrame();
        $I->waitForElementVisible('main.recordlist', 10);
    }

    public function searchFor(string $searchTerm): void
    {
        $I = $this->getWebDriver();
        $I->click('.toggleSearchButton');
        $I->waitForElementVisible('input[name="search_field"]', 5);
        $I->fillField('input[name="search_field"]', $searchTerm);
        $I->click('button[type="submit"]');
        $I->wait(1);
    }

    public function applyFilter(string $fieldName, string $value): void
    {
        $I = $this->getWebDriver();
        $I->click('.toggleSearchButton');
        $I->waitForElementVisible('input[name="filter[' . $fieldName . '][value]"]', 5);
        $I->fillField('input[name="filter[' . $fieldName . '][value]"]', $value);
        $I->click('button[type="submit"]');
        $I->wait(1);
    }

    public function sortBy(string $columnName, string $direction = 'ASC'): void
    {
        $I = $this->getWebDriver();
        $I->click('//thead//th[contains(., "' . $columnName . '")]//button');
        $I->wait(0.5);
        $sortDir = strtoupper($direction);
        $I->click('a[data-order-direction="' . $sortDir . '"]');
        $I->wait(1);
    }

    public function goToPage(int $pageNumber): void
    {
        $I = $this->getWebDriver();
        $I->click('//button[@data-page="' . $pageNumber . '"]');
        $I->wait(1);
    }

    public function inlineEdit(int $uid, string $fieldName, string $newValue): void
    {
        $I = $this->getWebDriver();
        $I->click('//tr[@data-uid="' . $uid . '"]//input[@data-field="' . $fieldName . '"]');
        $I->fillField('//tr[@data-uid="' . $uid . '"]//input[@data-field="' . $fieldName . '"]', $newValue);
        $I->pressKey('//tr[@data-uid="' . $uid . '"]//input[@data-field="' . $fieldName . '"]', \Facebook\WebDriver\WebDriverKeys::ENTER);
        $I->wait(1);
    }

    public function deleteRecord(int $uid): void
    {
        $I = $this->getWebDriver();
        $I->click('//tr[@data-uid="' . $uid . '"]//a[@data-delete2]');

        // Modal opens in main frame
        $I->switchToIFrame();
        $I->waitForElement('.modal', 5);
        $I->click('.modal button.btn-warning');
        $I->wait(1);

        // Switch back to content frame
        $I->switchToIFrame('list_frame');
    }

    public function exportRecords(string $format = 'csv', array $options = []): void
    {
        $I = $this->getWebDriver();
        $I->click('.recordlist-download-button');

        // Modal opens in main frame
        $I->switchToIFrame();
        $I->waitForElement('.modal', 5);
        $I->selectOption('select[name="format"]', $format);

        if ($format === 'csv' && isset($options['delimiter'])) {
            $I->selectOption('select[name="csv[delimiter]"]', $options['delimiter']);
        }

        $I->click('.modal button.btn-primary');
        $I->wait(2);

        // Switch back to content frame
        $I->switchToIFrame('list_frame');
    }

    public function publishRecord(int $uid): void
    {
        $I = $this->getWebDriver();
        $I->click('//tr[@data-uid="' . $uid . '"]//button[@data-action="publish"]');
        $I->waitForElement('.modal', 5);
        $I->click('.modal button.btn-success');
        $I->wait(2);
    }

    public function markReadyToPublish(int $uid): void
    {
        $I = $this->getWebDriver();
        $I->click('//tr[@data-uid="' . $uid . '"]//button[@data-action="ready-to-publish"]');
        $I->wait(1);
    }

    public function switchTable(string $tableName): void
    {
        $I = $this->getWebDriver();
        $I->selectOption('.table-selector', $tableName);
        $I->wait(1);
    }

    public function toggleColumn(string $columnName): void
    {
        $I = $this->getWebDriver();
        $I->click('.showColumnsButton');
        $I->switchToIFrame();
        $I->waitForElement('.modal', 5);
        $I->checkOption('input[name="columns[' . $columnName . ']"]');
        $I->click('.modal button.btn-primary');
        $I->wait(1);
        $I->switchToIFrame('list_frame');
    }

    public function seeRecordInTable(int $uid): void
    {
        $I = $this->getWebDriver();
        $I->seeElement('//tr[@data-uid="' . $uid . '"]');
    }

    public function dontSeeRecordInTable(int $uid): void
    {
        $I = $this->getWebDriver();
        $I->dontSeeElement('//tr[@data-uid="' . $uid . '"]');
    }

    public function seeRecordFieldValue(int $uid, string $fieldName, string $expectedValue): void
    {
        $I = $this->getWebDriver();
        $I->see($expectedValue, '//tr[@data-uid="' . $uid . '"]//td[@data-field="' . $fieldName . '"]');
    }
}

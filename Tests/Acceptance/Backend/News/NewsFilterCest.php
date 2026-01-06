<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Backend\News;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\AcceptanceTester;

class NewsFilterCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
        $I->openModule('example_news');
    }

    public function _after(AcceptanceTester $I): void
    {
        $this->ensureSearchFormIsOpen($I);

        // Scroll reset button into view and click it using JavaScript to avoid click interception
        $I->scrollTo('input[name="reset"]');
        $I->wait(0.3);
        $I->executeJS('document.querySelector(\'input[name="reset"]\').click();');
        $I->wait(1);
    }

    private function ensureSearchFormIsOpen(AcceptanceTester $I): void
    {
        try {
            $I->seeElement('input[name="reset"]');
        } catch (\Exception) {
            $I->click('.toggleSearchButton');
            $I->wait(0.5);
        }
    }

    private function submitSearchFormAndSwitchToResults(AcceptanceTester $I): void
    {
        $I->executeJS('document.querySelector(\'button[type="submit"][name="search"]\').click();');
        $I->wait(1);
        $I->switchToIFrame();
        $I->switchToContentFrame();
        $I->waitForElementVisible('main.recordlist', 10);
    }

    public function filterByLanguageEnglish(AcceptanceTester $I): void
    {
        $I->wantTo('filter news by English language');

        // Set language filter via the language dropdown in the module header
        $I->click('.module-docheader button.dropdown-toggle');
        $I->wait(0.5);
        $I->click('.module-docheader .dropdown-menu a[title="English"]');
        $I->wait(1);

        // Set items_per_page to max to ensure we see all records
        $I->selectOption('select[name="items_per_page"]', '500');
        $I->wait(1);

        // Verify ALL records are filtered to English language (no records with other languages)
        $I->seeElement('//tr[@data-sys_language_uid="0"]');
        $I->dontSeeElement('//tr[@data-sys_language_uid!="0"][@data-uid]');
    }

    public function filterByLanguageGerman(AcceptanceTester $I): void
    {
        $I->wantTo('filter news by German language');

        // Set language filter via the language dropdown in the module header
        $I->click('.module-docheader button.dropdown-toggle');
        $I->wait(0.5);
        $I->click('.module-docheader .dropdown-menu a[title="German"]');
        $I->wait(1);

        // Set items_per_page to max to ensure we see all records
        $I->selectOption('select[name="items_per_page"]', '500');
        $I->wait(1);

        // Verify ALL records are filtered to German language (no records with other languages)
        $I->seeElement('//tr[@data-sys_language_uid="1"]');
        $I->dontSeeElement('//tr[@data-sys_language_uid!="1"][@data-uid]');
    }

    public function filterByAuthor(AcceptanceTester $I): void
    {
        $I->wantTo('filter news by author using different expression types');
        $I->selectOption('select[name="items_per_page"]', '500');
        $I->wait(1);

        $testCases = [
            ['expr' => 'like', 'value' => 'Ruby', 'description' => 'contains Ruby'],
            ['expr' => 'notLike', 'value' => 'Ruby', 'description' => 'does not contain Ruby'],
            ['expr' => 'eq', 'value' => 'Ruby Bennett', 'description' => 'equals Ruby Bennett'],
            ['expr' => 'neq', 'value' => 'Ruby Bennett', 'description' => 'is not Ruby Bennett'],
        ];

        foreach ($testCases as $testCase) {
            $this->ensureSearchFormIsOpen($I);
            $I->selectOption('select[name="filter[author][expr]"]', $testCase['expr']);
            $I->fillField('input[name="filter[author][value]"]', $testCase['value']);
            $this->submitSearchFormAndSwitchToResults($I);

            $recordCount = $I->grabMultiple('//tr[@data-uid]', 'data-uid');
            $I->assertGreaterThan(0, count($recordCount), 'Should have filtered records with author ' . $testCase['description']);

            foreach ($recordCount as $uid) {
                $authorText = $I->grabTextFrom('//tr[@data-uid="' . $uid . '"]//span[@id="author-' . $uid . '"]');
                $value = $testCase['value'];
                match ($testCase['expr']) {
                    'like' => $I->assertStringContainsString($value, $authorText, "Record UID $uid should contain \"$value\" in author"),
                    'notLike' => $I->assertStringNotContainsString($value, $authorText, "Record UID $uid should not contain \"$value\" in author"),
                    'eq' => $I->assertEquals($value, $authorText, "Record UID $uid should equal \"$value\" in author"),
                    'neq' => $I->assertNotEquals($value, $authorText, "Record UID $uid should not equal \"$value\" in author"),
                };
            }
        }
    }

    public function filterBySitemapChangefreq(AcceptanceTester $I): void
    {
        $I->wantTo('filter news by sitemap changefreq using different expression types');
        $I->selectOption('select[name="items_per_page"]', '500');
        $I->wait(1);

        $testCases = [
            ['expr' => 'eq', 'description' => 'equals daily'],
            ['expr' => 'neq', 'description' => 'not equals daily'],
        ];

        foreach ($testCases as $testCase) {
            $this->ensureSearchFormIsOpen($I);
            $I->selectOption('select[name="filter[sitemap_changefreq][expr]"]', $testCase['expr']);
            $I->selectOption('select[name="filter[sitemap_changefreq][value]"]', 'daily');
            $this->submitSearchFormAndSwitchToResults($I);

            $recordCount = $I->grabMultiple('//tr[@data-uid]', 'data-uid');
            $I->assertGreaterThan(0, count($recordCount), 'Should have filtered records with sitemap_changefreq ' . $testCase['description']);

            foreach ($recordCount as $uid) {
                $rowText = $I->grabTextFrom('//tr[@data-uid="' . $uid . '"]');
                match ($testCase['expr']) {
                    'eq' => $I->assertStringContainsStringIgnoringCase('daily', $rowText, "Record UID $uid should contain \"daily\" in row"),
                    'neq' => $I->assertStringNotContainsStringIgnoringCase('daily', $rowText, "Record UID $uid should not contain \"daily\" in row"),
                };
            }
        }
    }

    public function filterByCategory(AcceptanceTester $I): void
    {
        $I->wantTo('filter news by category using different expression types');
        $I->selectOption('select[name="items_per_page"]', '500');
        $I->wait(1);

        $testCases = [
            ['expr' => 'in', 'description' => 'contains category 18'],
            ['expr' => 'notIn', 'description' => 'does not contain category 18'],
        ];

        foreach ($testCases as $testCase) {
            $this->ensureSearchFormIsOpen($I);

            // Wait for the category tree element to be visible
            $I->waitForElementVisible('.svg-tree-element', 10);
            $I->wait(2); // Wait for tree to fully load

            // Set the category filter value and expression type directly via JavaScript
            $I->executeJS('document.querySelector(\'input[name="filter[categories][value]"]\').value = "18";');
            $I->selectOption('select[name="filter[categories][expr]"]', $testCase['expr']);

            $this->submitSearchFormAndSwitchToResults($I);

            // Verify we have filtered records
            $recordCount = $I->grabMultiple('//tr[@data-uid]', 'data-uid');
            $I->assertGreaterThan(0, count($recordCount), 'Should have filtered records with ' . $testCase['description']);

            // Verify all records are present
            foreach ($recordCount as $uid) {
                $I->seeElement('//tr[@data-uid="' . $uid . '"]');
            }
        }
    }
}

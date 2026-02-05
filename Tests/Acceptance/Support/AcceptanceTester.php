<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Support;

use TYPO3\TestingFramework\Core\Acceptance\Step\FrameSteps;

/**
 * Inherited Methods
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    use FrameSteps;

    public function loginAsAdmin(): void
    {
        $I = $this;
        $I->amOnPage('/typo3');
        $I->waitForElement('#t3-username', 10);
        $I->fillField('#t3-username', 'admin');
        $I->fillField('#t3-password', 'Passw0rd!');
        $I->click('#t3-login-submit-section > button');
        $I->waitForElement('.scaffold-header', 10);
    }

    public function openModule(string $moduleIdentifier): void
    {
        $I = $this;
        $I->click('//a[@data-modulemenu-identifier="' . $moduleIdentifier . '"]');
        $I->wait(1);
        $I->switchToContentFrame();
        $I->waitForElementVisible('main.recordlist', 10);
    }

    public function searchFor(string $searchTerm): void
    {
        $I = $this;
        $I->click('.toggleSearchButton');
        $I->waitForElementVisible('input[name="search_field"]', 5);
        $I->fillField('input[name="search_field"]', $searchTerm);
        $I->click('button[type="submit"]');
        $I->wait(1);
    }

    public function sortBy(string $columnName, string $direction = 'ASC'): void
    {
        $I = $this;
        $I->click('//thead//th[contains(., "' . $columnName . '")]//button');
        $I->wait(0.5);
        $sortDir = strtoupper($direction);
        $I->executeJS('document.querySelector(\'th .dropdown-menu.show a[data-order-direction="' . $sortDir . '"]\').click();');
        $I->wait(1);
    }

    public function deleteRecord(int $uid): void
    {
        $I = $this;
        $I->click('//tr[@data-uid="' . $uid . '"]//a[@data-delete2]');

        // Modal opens in main frame
        $I->switchToIFrame();
        $I->waitForElement('.modal', 5);
        $I->click('.modal button.btn-warning');
        $I->wait(1);

        // Switch back to content frame
        $I->switchToIFrame('list_frame');
    }

    public function switchTable(string $tableName): void
    {
        $I = $this;
        $I->click('.btn-group .dropdown-toggle');
        $I->waitForElementVisible('.dropdown-menu', 2);
        $I->click('//a[@class="dropdown-item dropdown-item-spaced"][@title="' . $tableName . '"]');
        $I->wait(1);
    }

    public function openColumnsModal(): void
    {
        $I = $this;
        $I->click('View');
        $I->click('[data-doc-button="showColumnsButton"]');
        $I->switchToMainFrame();
        $I->waitForElement('.modal', 5);
    }

    public function toggleColumn(string $columnName): void
    {
        $I = $this;
        $I->openColumnsModal();
        $I->switchToIFrame();
        $I->waitForElement('.modal', 5);
        // Use JavaScript to click the checkbox since it might be intercepted by styled elements
        $I->executeJS("document.querySelector('input#select-column-$columnName').click();");
        $I->click('.modal button.btn-primary');
        $I->wait(1);
        $I->switchToContentFrame();
    }

    public function getFirstRecordUid(): int
    {
        $uid = $this->grabAttributeFrom('//tr[@data-uid]', 'data-uid');
        return (int)$uid;
    }
}

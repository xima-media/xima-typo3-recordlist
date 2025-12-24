<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\Helper;

use Codeception\Module;
use Codeception\Module\WebDriver;

class Backend extends Module
{
    protected function getWebDriver(): WebDriver
    {
        return $this->getModule('WebDriver');
    }

    public function switchToContentFrame(): void
    {
        $I = $this->getWebDriver();
        $I->switchToIFrame('list_frame');
    }

    public function switchToMainFrame(): void
    {
        $I = $this->getWebDriver();
        $I->switchToIFrame();
    }

    public function clearCaches(): void
    {
        $I = $this->getWebDriver();
        $I->click('.topbar-button-cache');
        $I->click('//a[@data-action="clearAllCache"]');
        $I->wait(2);
    }

    public function navigateToModule(string $moduleIdentifier): void
    {
        $I = $this->getWebDriver();
        $this->switchToMainFrame();
        $I->click('//a[@data-modulemenu-identifier="' . $moduleIdentifier . '"]');
        $I->wait(1);
        $this->switchToContentFrame();
    }
}

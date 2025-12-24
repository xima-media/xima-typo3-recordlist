<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\Tests\Acceptance\Support;

use Xima\XimaTypo3Recordlist\Tests\Acceptance\Support\_generated\AcceptanceTesterActions;

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
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends \Codeception\Actor
{
    use AcceptanceTesterActions;

    public function loginAsEditor(): void
    {
        $I = $this;
        $I->amOnPage('/typo3');
        $I->waitForElement('#t3-username', 10);
        $I->fillField('#t3-username', 'editor');
        $I->fillField('#t3-password', 'password');
        $I->click('#t3-login-submit-section > button');
        $I->waitForElement('.topbar-header', 10);
    }

    public function switchToContentFrame(): void
    {
        $this->switchToIFrame('typo3-contentIframe');
    }

    public function switchToMainFrame(): void
    {
        $this->switchToIFrame();
    }
}

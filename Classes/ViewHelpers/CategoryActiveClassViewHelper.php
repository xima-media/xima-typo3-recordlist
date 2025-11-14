<?php

declare(strict_types=1);

namespace Xima\XimaTypo3Recordlist\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class CategoryActiveClassViewHelper extends AbstractViewHelper
{
    /**
     * @var bool
     */
    protected $escapeChildren = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('value', 'string', 'The category uid to check');
        $this->registerArgument('activeCategories', 'string', 'Comma separated list of category uids', true);
    }

    public function render(): string
    {
        $value = $this->arguments['value'] ?? $this->renderChildren();
        if (!$value) {
            return '';
        }

        $activeCategories = $this->arguments['activeCategories'];
        if (!$activeCategories) {
            return '';
        }

        $activeCategories = GeneralUtility::intExplode(',', $activeCategories, true);
        $isActive = in_array((int)$value, $activeCategories, true);

        return $isActive ? 'badge-primary' : '';
    }
}

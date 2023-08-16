<?php

use TYPO3\CMS\Backend\History\RecordHistory;
use Xima\XimaTypo3Recordlist\XCLASS\RecordHistory as RecordHistoryAlias;

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][RecordHistory::class] = [
    'className' => RecordHistoryAlias::class,
];

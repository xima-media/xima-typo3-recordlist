<?php

use Xima\XimaTypo3Recordlist\Controller\Example\FeUsersController;
use Xima\XimaTypo3Recordlist\Controller\Example\NewsController;
use Xima\XimaTypo3Recordlist\Controller\Example\PagesController;

return [
    'example_pages' => [
        'parent' => 'web',
        'position' => ['after' => 'list'],
        'access' => 'user',
        'iconIdentifier' => 'module-cshmanual',
        'workspaces' => '*',
        'labels' => 'LLL:EXT:xima_typo3_recordlist/Resources/Private/Language/Example/locallang_pages_module.xlf',
        'extensionName' => 'XimaTypo3RecordlistExamples',
        'controllerActions' => [
            PagesController::class => [
                'processRequest',
            ],
        ],
        'inheritNavigationComponentFromMainModule' => false,
    ],
    'example_news' => [
        'parent' => 'web',
        'position' => ['after' => 'list'],
        'access' => 'user',
        'iconIdentifier' => 'module-cshmanual',
        'workspaces' => '*',
        'labels' => 'LLL:EXT:xima_typo3_recordlist/Resources/Private/Language/Example/locallang_news_module.xlf',
        'extensionName' => 'XimaTypo3RecordlistExamples',
        'controllerActions' => [
            NewsController::class => [
                'processRequest',
            ],
        ],
        'inheritNavigationComponentFromMainModule' => false,
    ],
    'example_feusers' => [
        'parent' => 'web',
        'position' => ['after' => 'list'],
        'access' => 'user',
        'iconIdentifier' => 'module-cshmanual',
        'workspaces' => '*',
        'labels' => 'LLL:EXT:xima_typo3_recordlist/Resources/Private/Language/Example/locallang_feusers_module.xlf',
        'extensionName' => 'XimaTypo3RecordlistExamples',
        'controllerActions' => [
            FeUsersController::class => [
                'processRequest',
            ],
        ],
        'inheritNavigationComponentFromMainModule' => false,
    ],
];

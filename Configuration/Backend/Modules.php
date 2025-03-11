<?php

use Xima\XimaTypo3Recordlist\Controller\Example\FeUsersController;
use Xima\XimaTypo3Recordlist\Controller\Example\FilesController;
use Xima\XimaTypo3Recordlist\Controller\Example\NewsController;
use Xima\XimaTypo3Recordlist\Controller\Example\PagesController;
use Xima\XimaTypo3Recordlist\Controller\Example\SysCategoryController;

return [
    'example' => [
        'labels' => 'LLL:EXT:xima_typo3_recordlist/Resources/Private/Language/Example/locallang_module_group.xlf',
        'iconIdentifier' => 'modulegroup-myextension',
    ],
    'example_pages' => [
        'parent' => 'example',
        'position' => ['after' => 'list'],
        'access' => 'user',
        'iconIdentifier' => 'module-example-4',
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
        'parent' => 'example',
        'position' => ['after' => 'list'],
        'access' => 'user',
        'iconIdentifier' => 'module-example-2',
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
        'parent' => 'example',
        'position' => ['after' => 'list'],
        'access' => 'user',
        'iconIdentifier' => 'module-example-3',
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
    'example_syscategory' => [
        'parent' => 'example',
        'position' => ['after' => 'list'],
        'access' => 'user',
        'iconIdentifier' => 'module-example-1',
        'workspaces' => '*',
        'labels' => 'LLL:EXT:xima_typo3_recordlist/Resources/Private/Language/Example/locallang_syscategory_module.xlf',
        'extensionName' => 'XimaTypo3RecordlistExamples',
        'controllerActions' => [
            SysCategoryController::class => [
                'processRequest',
            ],
        ],
        'inheritNavigationComponentFromMainModule' => false,
    ],
    'example_files' => [
        'parent' => 'example',
        'position' => ['after' => 'list'],
        'access' => 'user',
        'iconIdentifier' => 'module-example-5',
        'workspaces' => '*',
        'labels' => 'LLL:EXT:xima_typo3_recordlist/Resources/Private/Language/Example/locallang_files_module.xlf',
        'extensionName' => 'XimaTypo3RecordlistExamples',
        'controllerActions' => [
            FilesController::class => [
                'processRequest',
            ],
        ],
        'inheritNavigationComponentFromMainModule' => false,
    ],
];

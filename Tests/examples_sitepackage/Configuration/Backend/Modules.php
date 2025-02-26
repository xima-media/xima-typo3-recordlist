<?php

use Xima\XimaTypo3RecordlistExamples\Controller\PagesController;

return [
    'example_pages' => [
        'parent' => 'web',
        'position' => ['after' => 'list'],
        'access' => 'user',
        'iconIdentifier' => 'module-cshmanual',
        'workspaces' => '*',
        'labels' => 'LLL:EXT:xima_typo3_recordlist_examples/Resources/Private/Language/locallang_pages_module.xlf',
        'extensionName' => 'XimaTypo3RecordlistExamples',
        'controllerActions' => [
            PagesController::class => [
                'processRequest',
            ],
        ],
        'inheritNavigationComponentFromMainModule' => false,
    ],
];

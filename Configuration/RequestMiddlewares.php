<?php

return [
    'backend' => [
        'xima-typo3-recordlist/category-tree-manipulation' => [
            'target' => \Xima\XimaTypo3Recordlist\Middleware\CategoryTreeManipulation::class,
            'after' => [
                'typo3/cms-backend/backend-routing',
            ],
            'before' => [
                'typo3/cms-backend/output-compression',
            ],
        ],
        'xima-typo3-recordlist/current-workspace-manipulation' => [
            'target' => \Xima\XimaTypo3Recordlist\Middleware\CurrentWorkspaceManipulation::class,
            'after' => [
                'typo3/cms-core/response-propagation',
            ],
        ],
    ],
    'frontend' => [
        'xima-typo3-recordlist/current-workspace-manipulation' => [
            'target' => \Xima\XimaTypo3Recordlist\Middleware\CurrentFrontendWorkspaceManipulation::class,
            'after' => [
                'typo3/cms-core/response-propagation',
            ],
        ],
    ],
];

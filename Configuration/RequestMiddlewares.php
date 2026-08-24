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
            'target' => \Xima\XimaTypo3Recordlist\Middleware\CurrentBackendWorkspaceManipulation::class,
            'after' => [
                'typo3/cms-backend/authentication',
            ],
            'before' => [
                'typo3/cms-backend/backend-module-validator',
            ],
        ],
    ],
    'frontend' => [
        'xima-typo3-recordlist/current-workspace-manipulation' => [
            'target' => \Xima\XimaTypo3Recordlist\Middleware\CurrentFrontendWorkspaceManipulation::class,
            'after' => [
                // The backend user aspect must have been set up, it would otherwise overwrite our workspace aspect
                'typo3/cms-frontend/backend-user-authentication',
            ],
            'before' => [
                // The workspace aspect has to be in place before the page is resolved and before PreviewSimulator
                // evaluates it, otherwise neither the preview mode nor the cache bypass are activated
                'typo3/cms-frontend/page-resolver',
                'typo3/cms-frontend/preview-simulator',
            ],
        ],
    ],
];

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
    ],
];

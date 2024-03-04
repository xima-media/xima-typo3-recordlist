<?php

return [
    'backend' => [
        'xima-typo3-recordlist/workspace' => [
            'target' => \Xima\XimaTypo3Recordlist\Middleware\WorkspaceSwitch::class,
            'after' => [
                'typo3/cms-backend/backend-routing',
            ],
        ],
    ],
];

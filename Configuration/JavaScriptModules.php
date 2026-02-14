<?php

return [
    'dependencies' => [
        'core',
    ],
    'tags' => [
        'backend.module',
        'backend.form',
        'backend.navigation-component',
    ],
    'imports' => [
        '@xima/recordlist/' => 'EXT:xima_typo3_recordlist/Resources/Public/JavaScript/',
    ],
];

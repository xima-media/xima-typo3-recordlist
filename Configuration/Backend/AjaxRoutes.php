<?php

return [
    'xima_recordlist_usersetting' => [
        'path' => '/xima/recordlist/usersettings',
        'target' => \Xima\XimaTypo3Recordlist\Controller\AjaxController::class . '::userSettingsAction',
    ],
    'xima_recordlist_delete' => [
        'path' => '/xima/recordlist/delete',
        'target' => \Xima\XimaTypo3Recordlist\Controller\AjaxController::class . '::deleteRecord',
    ],
    'xima_recordlist_delete_file' => [
        'path' => '/xima/recordlist/delete-file',
        'target' => \Xima\XimaTypo3Recordlist\Controller\AjaxController::class . '::deleteFile',
    ],
    'xima_recordlist_inline_edit' => [
        'path' => '/xima/recordlist/edit',
        'target' => \Xima\XimaTypo3Recordlist\Controller\AjaxController::class . '::editRecord',
    ],
    'xima_categorytree_configuration' => [
        'path' => '/xima/recordlist/categorytree/configuration',
        'target' => \Xima\XimaTypo3Recordlist\Controller\CategoryTreeController::class . '::fetchConfigurationAction',
    ],
    'xima_categorytree_data' => [
        'path' => '/xima/recordlist/categorytree/data',
        'target' => \Xima\XimaTypo3Recordlist\Controller\CategoryTreeController::class . '::fetchDataAction',
    ],
];

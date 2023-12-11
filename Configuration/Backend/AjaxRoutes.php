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
];

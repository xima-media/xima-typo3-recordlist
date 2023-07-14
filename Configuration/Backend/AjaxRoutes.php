<?php

return [
    'xima_recordlist_usersetting' => [
        'path' => '/xima/recordlist/usersettings',
        'target' => \Xima\XimaTypo3Recordlist\Controller\AjaxController::class . '::userSettingsAction',
    ],
];

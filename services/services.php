<?php

return [
    'models'    => [
        'Process' => function () {
            if (class_exists('\App\Cron\Model\Process')) {
                return new \App\Cron\Model\Process();
            } else {
                return new \Nails\Cron\Model\Process();
            }
        },
    ],
    'resources' => [
        'Process' => function ($oObj) {
            if (class_exists('\App\Cron\Resource\Process')) {
                return new \App\Cron\Resource\Process($oObj);
            } else {
                return new \Nails\Cron\Resource\Process($oObj);
            }
        },
    ],
];

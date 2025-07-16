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
        'Process' => function ($resource, $model) {
            if (class_exists('\App\Cron\Resource\Process')) {
                return new \App\Cron\Resource\Process($resource, $model);
            } else {
                return new \Nails\Cron\Resource\Process($resource, $model);
            }
        },
    ],
];

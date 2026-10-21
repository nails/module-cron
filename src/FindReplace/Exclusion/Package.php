<?php

namespace Nails\Cron\FindReplace\Exclusion;

use Nails\FindReplace\Interfaces\Exclusion;

class Package implements Exclusion
{
    public function hidden(): array
    {
        return [
            NAILS_DB_PREFIX . 'cron_process',
        ];
    }

    public function readOnly(): array
    {
        return [];
    }
}

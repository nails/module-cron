<?php

/**
 * Migration: 2
 * Started:   17/05/2019
 */

namespace Nails\Database\Migration\Nails\ModuleCron;

use Nails\Common\Console\Migrate\Base;

class Migration2 extends Base
{
    /**
     * Execute the migration
     *
     * @return Void
     */
    public function execute()
    {
        $this->query('
            CREATE TABLE `{{NAILS_DB_PREFIX}}cron_process` (
                `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                `class` varchar(255) NOT NULL DEFAULT "",
                `started` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ');
    }
}

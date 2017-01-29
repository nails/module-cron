<?php

/**
 * Migration:   0
 * Started:     09/01/2015
 * Finalised:   09/01/2015
 */

namespace Nails\Database\Migration\Nailsapp\ModuleCron;

use Nails\Common\Console\Migrate\Base;

class Migration0 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        $this->query("
            CREATE TABLE `{{NAILS_DB_PREFIX}}log_cron` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `task` varchar(150) NOT NULL DEFAULT '',
                `duration` double NOT NULL,
                `message` varchar(500) DEFAULT NULL,
                `created` datetime NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;`
        ");
    }
}

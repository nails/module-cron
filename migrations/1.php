<?php

/**
 * Migration:   1
 * Started:     12/10/2015
 * Finalised:   12/10/2015
 */

namespace Nails\Database\Migration\Nails\ModuleCron;

use Nails\Common\Console\Migrate\Base;

class Migration1 extends Base
{
    /**
     * Execute the migration
     * @return Void
     */
    public function execute()
    {
        $this->query("DROP TABLE `{{NAILS_DB_PREFIX}}log_cron`;");
    }
}

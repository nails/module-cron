<?php

namespace Nails\Cron\Model;

use Nails\Common\Model\Base;

/**
 * Class Process
 *
 * @package Nails\Cron\Model
 */
class Process extends Base
{
    const TABLE              = NAILS_DB_PREFIX . 'cron_process';
    const RESOURCE_NAME      = 'Process';
    const RESOURCE_PROVIDER  = 'nails/module-cron';
    const AUTO_SET_USER      = false;
    const AUTO_SET_TIMESTAMP = false;
}

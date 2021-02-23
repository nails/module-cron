<?php

/**
 * Process Model
 *
 * @package     Nails
 * @subpackage  module-cron
 * @category    Models
 * @author      Nails Dev Team
 */

namespace Nails\Cron\Model;

use Nails\Common\Model\Base;
use Nails\Cron\Constants;

/**
 * Class Process
 *
 * @package Nails\Cron\Model
 */
class Process extends Base
{
    const TABLE              = NAILS_DB_PREFIX . 'cron_process';
    const RESOURCE_NAME      = 'Process';
    const RESOURCE_PROVIDER  = Constants::MODULE_SLUG;
    const AUTO_SET_USER      = false;
    const AUTO_SET_TIMESTAMP = false;
}

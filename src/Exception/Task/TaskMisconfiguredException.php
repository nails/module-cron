<?php

/**
 * Task Misconfigured Exception
 *
 * @package     Nails
 * @subpackage  module-cron
 * @category    Exceptions
 * @author      Nails Dev Team
 */

namespace Nails\Cron\Exception\Task;

use Nails\Cron\Exception\CronException;

/**
 * Class TaskMisconfiguredException
 *
 * @package Nails\Cron\Exception\Console
 */
class TaskMisconfiguredException extends CronException
{
    //  @todo (Pablo - 2019-05-17) - Set this
    const DOCUMENTATION_URL = '';
}

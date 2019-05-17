<?php

/**
 * Command Misconfigured Exception
 *
 * @package     Nails
 * @subpackage  module-cron
 * @category    Exceptions
 * @author      Nails Dev Team
 */

namespace Nails\Cron\Exception\Command;

use Nails\Cron\Exception\CronException;

/**
 * Class CommandMisconfiguredException
 *
 * @package Nails\Cron\Exception\Console
 */
class CommandMisconfiguredException extends CronException
{
    //  @todo (Pablo - 2019-05-17) - Set this
    const DOCUMENTATION_URL = '';
}

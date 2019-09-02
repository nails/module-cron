<?php

/**
 * The class is the basis of all cron tasks
 *
 * @package     Nails
 * @subpackage  module-common
 * @category    Tasks
 * @author      Nails Dev Team
 */

namespace Nails\Cron\Task;

use Nails\Cron\Console\Command\ListTasks;

/**
 * Class Base
 *
 * @package Nails\Cron\Task
 */
abstract class Base
{
    /**
     * Description of the task
     *
     * @var string
     */
    const DESCRIPTION = '';

    /**
     * The cron expression of when to run
     *
     * @var string
     */
    const CRON_EXPRESSION = null;

    /**
     * The console command to execute
     *
     * @var string
     */
    const CONSOLE_COMMAND = null;

    /**
     * The arguments to pass to the console command
     *
     * @var array
     */
    const CONSOLE_ARGUMENTS = [];

    /**
     * The maximum number of simultaneous processes which  will be executed
     *
     * @var int
     */
    const MAX_PROCESSES = INF;

    /**
     * Which environments to run the task on, leave empty to run on every environment
     *
     * @var string[]
     */
    const ENVIRONMENT = [];

    // --------------------------------------------------------------------------

    /**
     * Returns the task's description, delagating to the console command if necessary if blank
     *
     * @param ListTasks $oCommand The console process
     *
     * @return string
     */
    public static function getDescription(ListTasks $oConsole): string
    {
        $sDescription = static::DESCRIPTION;

        if (empty($sDescription) && !empty(static::CONSOLE_COMMAND)) {
            try {
                $oCommand     = $oConsole->getApplication()->find(static::CONSOLE_COMMAND);
                $sDescription = $oCommand->getDescription();
            } catch (\Exception $e) {
            }
        }

        return $sDescription;
    }
}

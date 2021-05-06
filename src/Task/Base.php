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
use Nails\Cron\Interfaces;

/**
 * Class Base
 *
 * @package Nails\Cron\Task
 */
abstract class Base implements Interfaces\Task
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
    const CRON_EXPRESSION = '';

    /**
     * The console command to execute
     *
     * @var string|null
     */
    const CONSOLE_COMMAND = null;

    /**
     * The arguments to pass to the console command
     *
     * @var string[]
     */
    const CONSOLE_ARGUMENTS = [];

    /**
     * The maximum number of simultaneous processes which  will be executed
     *
     * @var int
     */
    const MAX_PROCESSES = PHP_INT_MAX;

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
     * @param \Nails\Console\Command\Base $oConsole The console process
     *
     * @return string
     */
    public function getDescription(\Nails\Console\Command\Base $oConsole): string
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

    // --------------------------------------------------------------------------

    /**
     * Returns the cron expression to use
     *
     * @return string
     */
    public function getCronExpression(): string
    {
        return static::CRON_EXPRESSION;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the console command the task is bound to
     *
     * @return string|null
     */
    public function getConsoleCommand(): ?string
    {
        return static::CONSOLE_COMMAND;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the console arguments
     *
     * @return string[]
     */
    public function getConsoleArguments(): array
    {
        return static::CONSOLE_ARGUMENTS;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the maximum number of processes to spawn
     *
     * @return int
     */
    public function getMaxProcesses(): int
    {
        return static::MAX_PROCESSES;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the environments in which the task should run
     *
     * @return string[]
     */
    public function getEnvironments(): array
    {
        return static::ENVIRONMENT;
    }
}

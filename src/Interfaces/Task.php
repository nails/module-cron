<?php

namespace Nails\Cron\Interfaces;

use Nails\Cron\Console\Command\ListTasks;

interface Task
{
    /**
     * Returns the task's description
     *
     * @param \Nails\Console\Command\Base $oConsole The console process
     *
     * @return string
     */
    public function getDescription(\Nails\Console\Command\Base $oConsole): string;

    // --------------------------------------------------------------------------

    /**
     * Returns the cron expression to use
     *
     * @return string
     */
    public function getCronExpression(): string;

    // --------------------------------------------------------------------------

    /**
     * Returns the console command the task is bound to
     *
     * @return string|null
     */
    public function getConsoleCommand(): ?string;

    // --------------------------------------------------------------------------

    /**
     * Returns the console arguments
     *
     * @return string[]
     */
    public function getConsoleArguments(): array;

    // --------------------------------------------------------------------------

    /**
     * Returns the maximum number of processes to spawn
     *
     * @return int
     */
    public function getMaxProcesses(): int;

    // --------------------------------------------------------------------------

    /**
     * Returns the environments in which the task should run
     *
     * @return string[]
     */
    public function getEnvironments(): array;
}

<?php

namespace Nails\Cron\Command;

/**
 * Class Base
 *
 * @package Nails\Cron\Command
 */
abstract class Base
{
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
}

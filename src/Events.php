<?php

/**
 * The class provides a summary of the events fired by this module
 *
 * @package     Nails
 * @subpackage  module-common
 * @category    Events
 * @author      Nails Dev Team
 */

namespace Nails\Cron;

use Nails\Common\Events\Base;
use Nails\Cron\Event\Listener;

/**
 * Class Events
 *
 * @package Nails\Cron
 */
class Events extends Base
{
    /**
     * Fired when cron starts
     */
    const CRON_START = 'CRON:START';

    /**
     * Fired when cron is about to start executing tasks
     *
     * @param \Nails\Cron\Task\Base[] $aTasks The discovered tasks
     */
    const CRON_READY = 'CRON:READY';

    /**
     * Fired before each task
     *
     * @param \Nails\Cron\Task\Base $oTask The task about to be executed
     */
    const CRON_TASK_BEFORE = 'CRON:TASK:BEFORE';

    /**
     * Fired when a task errors
     *
     * @param \Nails\Cron\Task\Base $oTask      The task which errored
     * @param \Exception            $oException The exception which was caught
     */
    const CRON_TASK_ERROR = 'CRON:TASK:ERROR';

    /**
     * Fired after each task
     *
     * @param \Nails\Cron\Task\Base $oTask The task which was just executed
     */
    const CRON_TASK_AFTER = 'CRON:TASK:AFTER';

    /**
     * Fired when cron finishes
     */
    const CRON_FINISH = 'CRON:FINISH';

    // --------------------------------------------------------------------------

    /**
     * Autoload cron event listeners
     */
    public function autoload()
    {
        return [
            new Listener\Start(),
        ];
    }
}

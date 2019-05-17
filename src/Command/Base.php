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
     * The maximum number of simultaneous processes which cron will execute
     *
     * @var int
     */
    const MAX_PROCESSES = INF;

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

    // --------------------------------------------------------------------------

    /**
     * Helper function for specifying a command should run every minute
     *
     * @return bool
     */
    protected function shouldRunEveryMinute(): bool
    {
        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Helper function for specifying a command should run every hour
     *
     * @return bool
     */
    /**
     * @param \DateTime $oNow     The current time
     * @param int[]     $aMinutes The minutes on which this item should run
     *
     * @return bool
     */
    protected function shouldRunEveryHour(\DateTime $oNow, array $aMinutes = [0]): bool
    {
        $iNowMinute = (int) $oNow->format('i');
        foreach ($aMinutes as $iMinute) {
            if ($iNowMinute === $iMinute) {
                return true;
            }
        }
        return false;
    }

    // --------------------------------------------------------------------------

    //  @todo (Pablo - 2019-05-17) - shouldRunEveryDay($iHour, $iMinute)
    //  @todo (Pablo - 2019-05-17) - shouldRunEveryWeek($iDay, $iHour, $iMinute)
    //  @todo (Pablo - 2019-05-17) - shouldRunEveryMonth($iMonth, $iDay, $iHour, $iMinute)
}

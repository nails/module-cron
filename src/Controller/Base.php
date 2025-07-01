<?php

/**
 * This class provides some common cron controller functionality
 *
 * @package     Nails
 * @subpackage  module-cron
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

namespace Nails\Cron\Controller;

abstract class Base
{
    protected $oCronRouter;

    // --------------------------------------------------------------------------

    /**
     * Base constructor.
     *
     * @param $oCronRouter
     */
    public function __construct($oCronRouter)
    {
        $this->oCronRouter = $oCronRouter;

        //  By default, cron jobs should be long-lasting
        if (function_exists('set_time_limit')) {
            set_time_limit(0);
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Writes a line to the log
     *
     * @param string $sLine the line to write
     */
    protected function writeLog(string $sLine)
    {
        $this->oCronRouter->writeLog($sLine);
    }
}

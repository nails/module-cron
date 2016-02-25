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

class Base extends \MX_Controller
{
    protected $oCronRouter;

    // --------------------------------------------------------------------------

    /**
     * Construct the controller
     */
    public function __construct($oCronRouter)
    {
        parent::__construct();
        $this->oCronRouter = $oCronRouter;

        // --------------------------------------------------------------------------

        //  By default cron jobs should be long lasting
        if (function_exists('set_time_limit')) {
            set_time_limit(0);
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Writes a line to the log
     * @param string $sLine the line to write
     */
    protected function writeLog($sLine)
    {
        $this->oCronRouter->writeLog($sLine);
    }
}

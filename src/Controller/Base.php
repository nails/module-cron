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

use Nails\Cron\Events;
use Nails\Factory;

// --------------------------------------------------------------------------

/**
 * Allow the app to add functionality, if needed
 */
if (class_exists('\App\Cron\Controller\Base')) {
    abstract class BaseMiddle extends \App\Cron\Controller\Base
    {
    }
} else {
    abstract class BaseMiddle
    {
        public function __construct()
        {
        }
    }
}

// --------------------------------------------------------------------------

abstract class Base extends BaseMiddle
{
    protected $oCronRouter;

    // --------------------------------------------------------------------------

    /**
     * Construct the controller
     */
    public function __construct($oCronRouter)
    {
        parent::__construct();

        //  Setup Events
        $oEventService = Factory::service('Event');

        //  Call the CRON:STARTUP event, cron is constructing
        $oEventService->trigger(Events::CRON_STARTUP, Events::getEventNamespace());

        // --------------------------------------------------------------------------

        $this->oCronRouter = $oCronRouter;

        // --------------------------------------------------------------------------

        //  By default cron jobs should be long lasting
        if (function_exists('set_time_limit')) {
            set_time_limit(0);
        }

        // --------------------------------------------------------------------------

        //  Call the CRON:READY event, cron is all geared up and ready to go
        $oEventService->trigger(Events::CRON_READY, Events::getEventNamespace());
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

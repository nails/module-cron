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

use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\NailsException;
use ReflectionException;

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
     * Base constructor.
     *
     * @param $oCronRouter
     *
     * @throws FactoryException
     * @throws NailsException
     * @throws ReflectionException
     */
    public function __construct($oCronRouter)
    {
        parent::__construct();

        $this->oCronRouter = $oCronRouter;

        //  By default cron jobs should be long lasting
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
    protected function writeLog($sLine)
    {
        $this->oCronRouter->writeLog($sLine);
    }
}

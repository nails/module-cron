<?php

/**
 * Routes requests to Cron to the appropriate controllers
 *
 * @package     Nails
 * @subpackage  module-cron
 * @category    Controller
 * @author      Nails Dev Team
 * @link
 */

use Nails\Factory;
use Nails\Environment;
use App\Controller\Base;

class CronRouter extends Base
{
    private $sModuleName;
    private $sClassName;
    private $sMethod;
    private $aParams;
    private $oLogger;

    // --------------------------------------------------------------------------

    /**
     * Constructs the router, defining the request variables
     */
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        $sUri = uri_string();

        //  Remove the module prefix (i.e "cron/") then explode into segments
        //  Using regex as some systems will report a leading slash (e.g CLI)
        $sUri = preg_replace('#/?cron/#', '', $sUri);
        $aUri = explode('/', $sUri);

        //  Work out the moduleName, className and method
        $this->sModuleName = array_key_exists(0, $aUri) ? $aUri[0] : 'cron';
        $this->sClassName  = array_key_exists(1, $aUri) ? $aUri[1] : $this->sModuleName;
        $this->sMethod     = array_key_exists(2, $aUri) ? $aUri[2] : 'index';

        //  What's left of the array are the parameters to pass to the method
        $this->aParams = array_slice($aUri, 3);

        //  Configure logging
        $oDateTime     = Factory::factory('DateTime');
        $this->oLogger = Factory::service('Logger');
        $this->oLogger->setFile('cron-' . $oDateTime->format('y-m-d') . '.php');
    }

    // --------------------------------------------------------------------------

    /**
     * Route the call to the correct place
     * @return Void
     */
    public function index()
    {
        //  Command line only
        if (Environment::is('PRODUCTION') && ! $this->input->is_cli_request()) {

            header($this->input->server('SERVER_PROTOCOL') . ' 401 Unauthorized');
            echo '<h1>' . lang('unauthorised') . '</h1>';
            exit(401);
        }

        // --------------------------------------------------------------------------

        /**
         * Look for a controller, app version first then the module's cron controllers directory
         */
        $aControllerPaths = array(
            FCPATH . APPPATH . 'modules/cron/controllers/'
        );

        $nailsModules = _NAILS_GET_MODULES();

        foreach ($nailsModules as $oModule) {

            if ($oModule->moduleName === $this->sModuleName) {
                $aControllerPaths[] = $oModule->path . 'cron/controllers/';
                break;
            }
        }

        //  Look for a valid controller
        $sControllerName    = ucfirst($this->sClassName) . '.php';
        $bDidfindController = false;

        foreach ($aControllerPaths as $sPath) {

            $sControllerPath = $sPath . $sControllerName;

            if (is_file($sControllerPath)) {
                $bDidfindController = true;
                break;
            }
        }

        if (!empty($bDidfindController)) {

            //  Load the file and try and execute the method
            require_once $sControllerPath;

            $this->sModuleName = 'Nails\\Cron\\' . ucfirst($this->sModuleName) . '\\' . ucfirst($this->sClassName);

            if (class_exists($this->sModuleName)) {

                //  New instance of the controller
                $oInstance = new $this->sModuleName($this);

                if (is_callable(array($oInstance, $this->sMethod))) {

                    //  Begin timing
                    $this->writeLog('Starting job');

                    if (!empty($this->aParams)) {

                        $this->writeLog('Passed parameters');
                        $this->writeLog(print_r($this->aParams, true));
                    }

                    $iStart = microtime(true) * 10000;

                    call_user_func_array(array($oInstance, $this->sMethod), $this->aParams);

                    $iEnd      = microtime(true) * 10000;
                    $iDuration = ($iEnd - $iStart) / 10000;
                    $this->writeLog('Finished job');
                    $this->writeLog('Job took ' . number_format($iDuration, 5) . ' seconds');

                } else {

                    $this->writeLog('Cannot call method "' . $this->sMethod . '"');
                }

            } else {

                $this->writeLog(
                    '"' . $this->sModuleName . '" is incorrectly configured; could not find class ' . $this->sModuleName . '  at path ' . $sControllerPath
                );
            }

        } else {

            $this->writeLog(
                '"' . $this->sModuleName . '/' . $this->sClassName . '/' . $this->sMethod . '" is not a valid route.'
            );
        }
    }

    // --------------------------------------------------------------------------

    /**
     * Write a line to the cron log
     * @param string $sLine The line to write
     */
    public function writeLog($sLine)
    {
        $sLine  = ' [' . $this->sModuleName . '->' . $this->sMethod . '] ' . $sLine;
        $this->oLogger->line($sLine);
    }
}

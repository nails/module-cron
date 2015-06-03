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

class NAILS_Cron_Controller extends NAILS_Controller
{
    protected $iStart;
    protected $sTask;
    protected $sTaskId;

    // --------------------------------------------------------------------------

    /**
     * Construct the controller
     */
    public function __construct()
    {
        parent::__construct();

        // --------------------------------------------------------------------------

        //  Load language file
        $this->lang->load('cron');

        // --------------------------------------------------------------------------

        //  Command line only
        if (strtoupper(ENVIRONMENT) == 'PRODUCTION' && ! $this->input->is_cli_request()) {
            header($this->input->server('SERVER_PROTOCOL') . ' 401 Unauthorized');
            die('<h1>' . lang('unauthorised') . '</h1>');
        }

        // --------------------------------------------------------------------------

        //  E_ALL E_STRICT error reporting, for as error free code as possible
        error_reporting(E_ALL|E_STRICT);
    }

    // --------------------------------------------------------------------------

    /**
     * Kicks off cron jobs and specifies logging details
     * @param  string $sLogDir  The log [sub] directory
     * @param  string $sLogFile The log file
     * @param  string $sTask    The human description of the task
     * @param  string $sTaskId  An optional identifier for the task (useful for when multiple jobs of the same type might run in parallel)
     * @return void
     */
    protected function _start($sLogDir, $sLogFile, $sTask, $sTaskId)
    {
        //  Tick tock tick...
        $this->iStart  = microtime(true) * 10000;
        $this->sTask   = trim($sTask);
        $this->sTaskId = trim($sTaskId);

        //  Set log details
        _LOG_FILE('cron/' . $sLogDir . '/' . $sLogFile . '-' . date('Y-m-d') . '.php');
        $this->writeLog('Starting job [' . $this->sTask . ']...');
    }

    // --------------------------------------------------------------------------

    /**
     * Marks the end of a task
     * @param  string $sLogMessage An optional message to add
     * @return void
     */
    protected function _end($sLogMessage = null)
    {
        //  How'd we do?
        $iEnd      = microtime(true) * 10000;
        $iDuration = ($iEnd - $this->iStart) / 10000;

        // --------------------------------------------------------------------------

        $this->writeLog('Finished job [' . $this->sTask . ']');
        $this->writeLog('Job took ' . number_format($iDuration, 5) . ' seconds');

        // --------------------------------------------------------------------------

        //  Write this to the DB log
        $aData              = array();
        $aData['task']      = $this->sTask;
        $aData['duration']  = $iDuration;
        $aData['message']   = $sLogMessage;

        $this->db->set($aData);
        $this->db->set('created', 'NOW()', false);
        $this->db->insert(NAILS_DB_PREFIX . 'log_cron');
    }

    // --------------------------------------------------------------------------

    /**
     * Writes a line to the log file, prepends the task ID if there is one
     * @param  string $sLine The line to write
     * @return void
     */
    protected function writeLog($sLine)
    {
        if ($this->sTaskId) {
            $sLine = '[' . $this->sTaskId. '] ' . $sLine;
        }

        _LOG($sLine);
    }
}

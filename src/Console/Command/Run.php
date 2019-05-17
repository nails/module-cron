<?php

/**
 * The class is the cron runner
 *
 * @package     Nails
 * @subpackage  module-common
 * @category    Console
 * @author      Nails Dev Team
 */

namespace Nails\Cron\Console\Command;

use Cron\CronExpression;
use DateTime;
use Exception;
use Nails\Common\Exception\FactoryException;
use Nails\Common\Exception\ModelException;
use Nails\Common\Factory\Component;
use Nails\Common\Interfaces\ErrorHandlerDriver;
use Nails\Common\Service\Database;
use Nails\Common\Service\ErrorHandler;
use Nails\Components;
use Nails\Console\Command\Base;
use Nails\Cron\Exception\Command\CommandMisconfiguredException;
use Nails\Cron\Exception\CronException;
use Nails\Cron\Interfaces\Command;
use Nails\Cron\Model\Process;
use Nails\Factory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Run
 *
 * @package Nails\Cron\Console\Command
 */
class Run extends Base
{
    /**
     * Discovered commands
     *
     * @var array
     */
    private $aCommands = [];

    // --------------------------------------------------------------------------

    /**
     * Configure the command
     */
    protected function configure(): void
    {
        $this
            ->setName('cron:run')
            ->setDescription('The cron runner');
    }

    // --------------------------------------------------------------------------

    /**
     * Executes the app
     *
     * @param InputInterface  $oInput  The Input Interface provided by Symfony
     * @param OutputInterface $oOutput The Output Interface provided by Symfony
     *
     * @return int
     * @throws FactoryException
     * @throws ModelException
     */
    protected function execute(InputInterface $oInput, OutputInterface $oOutput): int
    {
        parent::execute($oInput, $oOutput);

        $this->banner('Nails Cron Runner');

        $this
            ->discoverCommands()
            ->runCommands();

        $this->banner('Finished processing all cron commands');

        return self::EXIT_CODE_SUCCESS;
    }

    // --------------------------------------------------------------------------

    /**
     * Looks for valid Cron definitions
     *
     * @return Run
     */
    protected function discoverCommands(): Run
    {
        $this->oOutput->write('Discovering commands... ');

        /** @var Component $oComponent */
        foreach (Components::available() as $oComponent) {

            $aNamespaceRoots = $oComponent->getNamespaceRootPaths();
            if (empty($aNamespaceRoots)) {
                continue;
            }

            foreach ($aNamespaceRoots as $sPath) {

                $sCronPath = $sPath . DIRECTORY_SEPARATOR . 'Cron' . DIRECTORY_SEPARATOR;

                if (is_dir($sCronPath)) {

                    $oDirectory = new RecursiveDirectoryIterator($sCronPath);
                    $oIterator  = new RecursiveIteratorIterator($oDirectory);
                    $oRegex     = new RegexIterator($oIterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

                    foreach ($oRegex as $aItem) {

                        $sCommandPath = reset($aItem);
                        $sClassName   = str_replace($sCronPath, '', $sCommandPath);
                        $sClassName   = $oComponent->namespace . 'Cron\\' . rtrim(str_replace(DIRECTORY_SEPARATOR, '\\', $sClassName), '.php');

                        if (class_exists($sClassName) && classExtends($sClassName, \Nails\Cron\Command\Base::class)) {
                            $this->aCommands[] = new $sClassName();
                        }
                    }
                }
            }
        }

        $this->oOutput->writeln('found <info>' . count($this->aCommands) . '</info> commands');

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Executes definitions which satisfy the timestamp
     *
     * @return Run
     * @throws FactoryException
     * @throws ModelException
     */
    protected function runCommands(): Run
    {
        /** @var DateTime $oNow */
        $oNow = Factory::factory('DateTime');
        /** @var Database $oDb */
        $oDb = Factory::service('Database');
        /** @var Process $oProcessModel */
        $oProcessModel = Factory::model('Process', 'nails/module-cron');

        //  Sort the processes for easy counting
        $aProcesses = $oProcessModel->getAll();
        /** @var Command[] $aActiveProcesses */
        $aActiveProcesses = [];

        /** @var \Nails\Cron\Resource\Process $oProcess */
        foreach ($aProcesses as $oProcess) {
            if (!array_key_exists($oProcess->class, $aActiveProcesses)) {
                $aActiveProcesses[$oProcess->class] = 0;
            }
            $aActiveProcesses[$oProcess->class]++;
        }

        /** @var \Nails\Cron\Command\Base $oCommand */
        foreach ($this->aCommands as $oCommand) {

            try {

                $sClass = get_class($oCommand);
                $this->oOutput->write('<info>' . $sClass . '</info>... ');

                //  Ensure that there is space for the process to run
                if (getFromArray($sClass, $aActiveProcesses, 0) >= $oCommand::MAX_PROCESSES) {
                    $this->oOutput->writeln('reached maximum allowed number of process');
                    continue;
                } elseif (empty($oCommand::CRON_EXPRESSION)) {
                    $this->oOutput->writeln('');
                    throw new CommandMisconfiguredException(
                        'Cron command "' . $sClass . '" misconfigured; CRON_EXPRESSION is empty'
                    );
                }

                $oExpression = CronExpression::factory($oCommand::CRON_EXPRESSION);
                if (!$oExpression->isDue($oNow)) {
                    $this->oOutput->writeln('not due to run');
                    continue;
                }

                $this->oOutput->writeln('due to run');
                $iTimerStart = microtime(true) * 10000;

                $iProcessId = $oProcessModel->create([
                    'class' => $sClass,
                ]);

                if (!empty($oCommand::CONSOLE_COMMAND)) {

                    $this->oOutput->writeln(
                        '↳ executing: <info>' . $oCommand::CONSOLE_COMMAND . ' ' . implode(' ', $oCommand::CONSOLE_ARGUMENTS) . '</info>'
                    );
                    $iResult = $this->callCommand(
                        $oCommand::CONSOLE_COMMAND,
                        $oCommand::CONSOLE_ARGUMENTS,
                        false
                    );

                    if ($iResult !== static::EXIT_CODE_SUCCESS) {
                        throw new CronException(
                            'Command failed with error code ' . $iResult
                        );
                    }

                } elseif (method_exists($oCommand, 'execute')) {

                    $this->oOutput->writeln(
                        '↳ executing: <info>' . $sClass . '->execute()</info>'
                    );
                    $oCommand->execute(
                        $this->oOutput
                    );

                } else {
                    throw new CommandMisconfiguredException(
                        'Cron command "' . $sClass . '" misconfigured; no task configured'
                    );
                }

            } catch (Exception $e) {

                $this->oOutput->writeln(
                    '↳ <error>Error: ' . $e->getMessage() . '</error>'
                );

                /** @var ErrorHandler $oErrorHandlerService */
                $oErrorHandlerService = Factory::service('ErrorHandler');
                /** @var ErrorHandlerDriver $sDriver */
                $sDriver = $oErrorHandlerService::getDriverClass();
                $sDriver::exception($e, false);

            } finally {
                if (!empty($iProcessId)) {
                    $oProcessModel->delete($iProcessId);
                }

                if (!empty($iTimerStart)) {
                    $iTimerEnd = microtime(true) * 10000;
                    $iDuration = ($iTimerEnd - $iTimerStart) / 10000;

                    $this->oOutput->writeln(
                        '↳ Job completed in <info>' . $iDuration . '</info> seconds'
                    );
                    $this->oOutput->writeln(
                        '↳ Memory usage: ' . formatBytes(memory_get_usage())
                    );
                }

                $oDb->flushCache();
            }
        }

        return $this;
    }
}

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
use Nails\Common\Exception\NailsException;
use Nails\Common\Factory\Component;
use Nails\Common\Interfaces\ErrorHandlerDriver;
use Nails\Common\Service\Database;
use Nails\Common\Service\ErrorHandler;
use Nails\Common\Service\Event;
use Nails\Components;
use Nails\Console\Command\Base;
use Nails\Cron\Console\Output\LoggerOutput;
use Nails\Cron\Events;
use Nails\Cron\Exception\CronException;
use Nails\Cron\Exception\Task\TaskMisconfiguredException;
use Nails\Cron\Model\Process;
use Nails\Environment;
use Nails\Factory;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use ReflectionException;
use RegexIterator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class Run
 *
 * @package Nails\Cron\Console\Command
 */
class Run extends Base
{
    /**
     * Discovered tasks
     *
     * @var array
     */
    private $aTasks = [];

    /**
     * The event service
     *
     * @var Event
     */
    private $oEventService;

    // --------------------------------------------------------------------------

    /**
     * Configure the command
     */
    protected function configure(): void
    {
        $this
            ->setName('cron:run')
            ->setDescription('Executes due cron tasks')
            ->addOption(
                'output',
                null,
                InputOption::VALUE_NONE,
                'Send output to the terminal (rather than the log file)'
            );
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
     * @throws NailsException
     * @throws ReflectionException
     */
    protected function execute(InputInterface $oInput, OutputInterface $oOutput): int
    {
        if (!$oInput->getOption('output')) {
            $oOutput = new LoggerOutput();
        }

        parent::execute($oInput, $oOutput);

        $this->banner('Nails Cron Runner');

        /** @var Event oEventService */
        $this->oEventService = Factory::service('Event');

        $this->oEventService->trigger(Events::CRON_START, Events::getEventNamespace());

        static::discoverTasks($oOutput, $this->aTasks);

        $this->oEventService->trigger(Events::CRON_READY, Events::getEventNamespace(), [$this->aTasks]);

        $this->runTasks();

        $this->oEventService->trigger(Events::CRON_FINISH, Events::getEventNamespace());

        $this->banner('Finished processing all cron tasks');

        if (!$oInput->getOption('output')) {
            $oOutput->writeln('Finished Cron Runner');
        }

        return self::EXIT_CODE_SUCCESS;
    }

    // --------------------------------------------------------------------------

    /**
     * Looks for valid Cron tasks
     *
     * @param OutputInterface $oOutput The output interface
     * @param array           $aTasks  The task array to populate
     *
     * @throws FactoryException
     */
    public static function discoverTasks(OutputInterface $oOutput, array &$aTasks): void
    {
        $oOutput->write('Discovering tasks... ');

        /** @var Component $oComponent */
        foreach (Components::available() as $oComponent) {

            $aClasses = $oComponent
                ->findClasses('Cron\\Task')
                ->whichExtend(\Nails\Cron\Task\Base::class);

            foreach ($aClasses as $sClass) {
                $aTasks[] = new $sClass();
            }
        }

        $iCount = count($aTasks);
        Factory::helper('inflector');
        $oOutput->writeln('found <info>' . $iCount . '</info> ' . pluralise($iCount, 'task'));
    }

    // --------------------------------------------------------------------------

    /**
     * Executes tasks which are due
     *
     * @return Run
     * @throws FactoryException
     * @throws ModelException
     * @throws NailsException
     * @throws ReflectionException
     */
    protected function runTasks(): Run
    {
        /** @var DateTime $oNow */
        $oNow = Factory::factory('DateTime');
        /** @var Database $oDb */
        $oDb = Factory::service('Database');
        /** @var Process $oProcessModel */
        $oProcessModel = Factory::model('Process', 'nails/module-cron');

        //  Sort the processes for easy counting
        /** @var \Nails\Cron\Resource\Process $aProcesses */
        $aProcesses = $oProcessModel->getAll();
        /** @var int[] $aActiveProcesses */
        $aActiveProcesses = [];

        /** @var \Nails\Cron\Resource\Process $oProcess */
        foreach ($aProcesses as $oProcess) {
            if (!array_key_exists($oProcess->class, $aActiveProcesses)) {
                $aActiveProcesses[$oProcess->class] = 0;
            }
            $aActiveProcesses[$oProcess->class]++;
        }

        /** @var \Nails\Cron\Task\Base $oTask */
        foreach ($this->aTasks as $oTask) {

            try {

                $this->oEventService->trigger(Events::CRON_TASK_BEFORE, Events::getEventNamespace(), [$oTask]);

                $sClass = get_class($oTask);
                $this->oOutput->write('<info>' . $sClass . '</info>... ');

                //  Ensure that there is space for the process to run
                if (getFromArray($sClass, $aActiveProcesses, 0) >= $oTask::MAX_PROCESSES) {
                    $this->oOutput->writeln('reached maximum allowed number of process for this task');
                    continue;
                } elseif (empty($oTask::CRON_EXPRESSION)) {
                    $this->oOutput->writeln('');
                    throw new TaskMisconfiguredException(
                        'Cron task "' . $sClass . '" misconfigured; static::CRON_EXPRESSION is empty'
                    );
                }

                $oExpression = CronExpression::factory($oTask::CRON_EXPRESSION);
                if (!$oExpression->isDue($oNow)) {
                    $this->oOutput->writeln('not due to run');
                    continue;
                } elseif (!empty($oTask::ENVIRONMENT) && !in_array(Environment::get(), $oTask::ENVIRONMENT)) {
                    $this->oOutput->writeln('due to run, but not on ' . Environment::get());
                    continue;
                }

                $this->oOutput->writeln('');
                $iTimerStart = microtime(true) * 10000;

                $iProcessId = $oProcessModel->create([
                    'class' => $sClass,
                ]);

                if (!empty($oTask::CONSOLE_COMMAND)) {

                    $oNow = Factory::factory('DateTime');
                    $this->oOutput->writeln(
                        '↳ started at: <info>' . $oNow->format('Y-m-d H:i:s') . '</info>'
                    );
                    $this->oOutput->writeln(
                        '↳ executing: <info>' . $oTask::CONSOLE_COMMAND . ' ' . implode(' ', $oTask::CONSOLE_ARGUMENTS) . '</info>'
                    );
                    $iResult = $this->callCommand(
                        $oTask::CONSOLE_COMMAND,
                        $oTask::CONSOLE_ARGUMENTS,
                        false
                    );

                    if ($iResult !== static::EXIT_CODE_SUCCESS) {
                        throw new CronException(
                            'Task failed with error code ' . $iResult
                        );
                    }

                } elseif (method_exists($oTask, 'execute')) {

                    $this->oOutput->writeln(
                        '↳ executing: <info>' . $sClass . '->execute()</info>'
                    );
                    $oTask->execute(
                        $this->oOutput
                    );

                } else {
                    throw new TaskMisconfiguredException(
                        'Cron task "' . $sClass . '" misconfigured; no task defined'
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

                $this->oEventService->trigger(
                    Events::CRON_TASK_ERROR,
                    Events::getEventNamespace(),
                    [$oTask, $e]
                );

            } finally {

                if (!empty($iProcessId)) {
                    $oProcessModel->delete($iProcessId);
                }

                if (!empty($iTimerStart)) {
                    $iTimerEnd = microtime(true) * 10000;
                    $iDuration = ($iTimerEnd - $iTimerStart) / 10000;
                    //  Reset the start timer
                    $iTimerStart = null;

                    $oNow = Factory::factory('DateTime');
                    $this->oOutput->writeln(
                        '↳ finished at: <info>' . $oNow->format('Y-m-d H:i:s') . '</info>'
                    );
                    $this->oOutput->writeln(
                        '↳ Job completed in <info>' . $iDuration . '</info> seconds'
                    );
                    $this->oOutput->writeln(
                        '↳ Memory usage: ' . formatBytes(memory_get_usage())
                    );
                }

                $oDb->flushCache();

                $this->oEventService->trigger(Events::CRON_TASK_AFTER, Events::getEventNamespace(), [$oTask]);
            }
        }

        return $this;
    }
}

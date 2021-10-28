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
use Nails\Config;
use Nails\Console\Command\Base;
use Nails\Cron\Constants;
use Nails\Cron\Console\Output\LoggerOutput;
use Nails\Cron\Events;
use Nails\Cron\Exception\CronException;
use Nails\Cron\Exception\Task\ProcessStalledException;
use Nails\Cron\Exception\Task\TaskMisconfiguredException;
use Nails\Cron\Interfaces;
use Nails\Cron\Model\Process;
use Nails\Environment;
use Nails\Factory;
use ReflectionException;
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
     * @var Interfaces\Task[]
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

        $this->banner('Cron: Run');

        /** @var Event oEventService */
        $this->oEventService = Factory::service('Event');

        $this->triggerEvent(Events::CRON_START);

        static::discoverTasks($oOutput, $this->aTasks);

        $this->triggerEvent(Events::CRON_READY, [$this->aTasks]);

        $this
            ->runTasks()
            ->flushStalledProcesses();

        $this->triggerEvent(Events::CRON_FINISH);

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
        $oOutput->writeln('Discovering tasks... ');

        /** @var Component $oComponent */
        foreach (Components::available() as $oComponent) {

            $aClasses = $oComponent
                ->findClasses('Cron\\Task')
                ->whichImplement(Interfaces\Task::class);

            foreach ($aClasses as $sClass) {
                $aTasks[] = new $sClass();
            }
        }

        $iCount = count($aTasks);
        Factory::helper('inflector');
        $oOutput->writeln('↳ found <info>' . $iCount . '</info> ' . pluralise($iCount, 'task'));
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
        /** @var Database $oDb */
        $oDb = Factory::service('Database');

        /** @var Interfaces\Task $oTask */
        foreach ($this->aTasks as $oTask) {

            try {

                $this->triggerEvent(Events::CRON_TASK_BEFORE, [$oTask]);

                $sClass = get_class($oTask);
                $this->oOutput->writeln('<info>' . $sClass . '</info>... ');

                if (!$this->taskCanRun($oTask) || !$this->taskDueToRun($oTask)) {
                    continue;
                }

                $oProcess = $this->spawnProcess($oTask);
                $this->oOutput->writeln('↳ Process ID is #' . $oProcess->id);

                $iTimerStart       = $this->startTimer();
                $sConsoleCommand   = $oTask->getConsoleCommand();
                $aConsoleArguments = $oTask->getConsoleArguments();

                if (!empty($sConsoleCommand)) {

                    /** @var DateTime $oNow */
                    $oNow = Factory::factory('DateTime');
                    $this->oOutput->writeln(
                        '↳ started at: <info>' . $oNow->format('Y-m-d H:i:s') . '</info>'
                    );
                    $this->oOutput->writeln(
                        '↳ executing: <info>' . $sConsoleCommand . ' ' . implode(' ', $aConsoleArguments) . '</info>'
                    );
                    $iResult = $this->callCommand(
                        $sConsoleCommand,
                        $aConsoleArguments,
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
                    throw new TaskMisconfiguredException(sprintf(
                        'Cron task "%s" is misconfigured, does not execute a console command or provide execute method',
                        get_class($oTask),
                    ));
                }

            } catch (Exception $e) {

                $this->oOutput->writeln(
                    '↳ <error>Error: ' . $e->getMessage() . '</error>'
                );

                $this
                    ->logException($e)
                    ->triggerEvent(Events::CRON_TASK_ERROR, [$oTask, $e]);

            } finally {

                $oDb->flushCache();

                $this
                    ->killProcess($oProcess ?? null)
                    ->finishTimer($iTimerStart ?? null)
                    ->triggerEvent(Events::CRON_TASK_AFTER, [$oTask]);
            }
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns the active processes with a counter showing the number of instances it has
     *
     * @return int[]
     * @throws FactoryException
     * @throws ModelException
     */
    protected function getActiveProcesses(): array
    {
        /** @var Process $oProcessModel */
        $oProcessModel = Factory::model('Process', Constants::MODULE_SLUG);

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

        return $aActiveProcesses;
    }

    // --------------------------------------------------------------------------

    /**
     * Determines whether a task can run
     *
     * @param Interfaces\Task $oTask The task being executed
     *
     * @return bool
     * @throws TaskMisconfiguredException
     */
    protected function taskCanRun(
        Interfaces\Task $oTask
    ): bool {

        $sClass           = get_class($oTask);
        $aActiveProcesses = $this->getActiveProcesses();

        if (getFromArray($sClass, $aActiveProcesses, 0) >= $oTask->getMaxProcesses()) {
            $this->oOutput->writeln('Reached maximum allowed number of process for this task');
            return false;

        } elseif (empty($oTask->getCronExpression())) {
            $this->oOutput->writeln('');
            throw new TaskMisconfiguredException(sprintf(
                'Cron task "%s" misconfigured; cron expression is empty',
                get_class($oTask),
            ));
        }

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Determines whether a task is due to run
     *
     * @param Interfaces\Task $oTask The task being executed
     *
     * @return bool
     * @throws FactoryException
     */
    protected function taskDueToRun(Interfaces\Task $oTask): bool
    {
        /** @var DateTime $oNow */
        $oNow        = Factory::factory('DateTime');
        $oExpression = CronExpression::factory($oTask->getCronExpression());

        if (!$oExpression->isDue($oNow)) {
            $this->oOutput->writeln('↳ not due to run');
            return false;

        } elseif (!empty($oTask->getEnvironments()) && !in_array(Environment::get(), $oTask->getEnvironments())) {
            $this->oOutput->writeln('↳ due to run, but not on ' . Environment::get());
            return false;
        }

        return true;
    }

    // --------------------------------------------------------------------------

    /**
     * Starts a timer
     *
     * @return float
     */
    protected function startTimer(): float
    {
        return microtime(true) * 10000;
    }

    // --------------------------------------------------------------------------

    /**
     * Finished a timer
     *
     * @param float|null $iTimerStart The timer's start time
     *
     * @return $this
     * @throws FactoryException
     */
    protected function finishTimer(?float $iTimerStart): Run
    {
        if (!empty($iTimerStart)) {

            $iTimerEnd = microtime(true) * 10000;
            $iDuration = ($iTimerEnd - $iTimerStart) / 10000;
            //  Reset the start timer
            $iTimerStart = null;

            /** @var DateTime $oNow */
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

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Spawns a new cron process for the task
     *
     * @param Interfaces\Task $oTask The task being execute
     *
     * @return \Nails\Cron\Resource\Process
     * @throws FactoryException
     * @throws ModelException
     */
    protected function spawnProcess(Interfaces\Task $oTask): \Nails\Cron\Resource\Process
    {
        /** @var Process $oProcessModel */
        $oProcessModel = Factory::model('Process', Constants::MODULE_SLUG);
        $sClass        = get_class($oTask);

        $oProcess = $oProcessModel->create([
            'class' => $sClass,
        ], true);

        return $oProcess;
    }

    // --------------------------------------------------------------------------

    /**
     * Kills a cron process
     *
     * @param \Nails\Cron\Resource\Process|null $oProcess The process to kill
     *
     * @return $this
     */
    protected function killProcess(?\Nails\Cron\Resource\Process $oProcess): Run
    {
        /** @var Process $oProcessModel */
        $oProcessModel = Factory::model('Process', Constants::MODULE_SLUG);

        if (!empty($oProcess)) {
            $oProcessModel->delete($oProcess->id);
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Triggers an event in the cron namespace
     *
     * @param string $sEvent   The event being triggered
     * @param array  $aPayload The payload to include
     *
     * @return $this
     * @throws NailsException
     * @throws ReflectionException
     */
    protected function triggerEvent(string $sEvent, array $aPayload = []): Run
    {
        $this->oEventService
            ->trigger(
                $sEvent,
                Events::getEventNamespace(),
                $aPayload
            );

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Flushes any stalled processes
     *
     * @return $this
     * @throws FactoryException
     * @throws ModelException
     */
    protected function flushStalledProcesses(): Run
    {
        $this->banner('Flushing stalled processes...');

        $aProcesses = $this->getStalledTasks();

        if (empty($aProcesses)) {
            $this->oOutput->writeln('No processes stalled');
        }

        foreach ($aProcesses as $oProcess) {

            $this->oOutput->writeln(sprintf(
                'Process #%s <info>%s</info> is stalled; killing... ',
                $oProcess->id,
                $oProcess->class,
            ));

            $this
                ->killProcess($oProcess)
                ->logException(new ProcessStalledException(
                    sprintf(
                        'Process #%s (%s) stalled; started at %s; removed at %s',
                        $oProcess->id,
                        $oProcess->class,
                        $oProcess->started,
                        Factory::factory('DateTime')->format('Y-m-d H:i:s')
                    )
                ));

            $this->oOutput->writeln('↳ <info>done</info>');
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Returns processes which are considered stalled
     *
     * @return \Nails\Cron\Resource\Process[]
     * @throws FactoryException
     * @throws ModelException
     */
    protected function getStalledTasks(): array
    {
        /** @var Process $oProcessModel */
        $oProcessModel = Factory::model('Process', Constants::MODULE_SLUG);

        $iTimeout = Config::get('CRON_STALLED_PROCESS_TIMEOUT', 6);

        return $oProcessModel->getAll([
            'where' => [
                ['started <', 'DATE_SUB(NOW(), INTERVAL ' . $iTimeout . ' HOUR)', false],
            ],
        ]);
    }

    // --------------------------------------------------------------------------

    /**
     * Logs an exception to the Error Handler without actually throwing it
     *
     * @param Exception $e The exception to log
     *
     * @return $this
     * @throws FactoryException
     */
    protected function logException(\Exception $e): Run
    {
        /** @var ErrorHandler $oErrorHandlerService */
        $oErrorHandlerService = Factory::service('ErrorHandler');

        /** @var ErrorHandlerDriver $sDriver */
        $sDriver = $oErrorHandlerService::getDriverClass();
        $sDriver::exception($e, false);

        return $this;
    }
}

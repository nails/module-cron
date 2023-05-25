<?php

/**
 * The class lists configured cron commands
 *
 * @package     Nails
 * @subpackage  module-common
 * @category    Console
 * @author      Nails Dev Team
 */

namespace Nails\Cron\Console\Command;

use Cron\CronExpression;
use Nails\Common\Exception\FactoryException;
use Nails\Components;
use Nails\Console\Command\Base;
use Nails\Cron\Exception\CronException;
use Nails\Cron\Interfaces;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ListTasks
 *
 * @package Nails\Cron\Console\Command
 */
class ListTasks extends Base
{
    /**
     * Configure the command
     */
    protected function configure(): void
    {
        $this
            ->setName('cron:list')
            ->setDescription('Lists discovered cron tasks')
            ->addArgument('component', InputArgument::OPTIONAL, 'Filter by component');;
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
     */
    protected function execute(InputInterface $oInput, OutputInterface $oOutput): int
    {
        parent::execute($oInput, $oOutput);

        $this->banner('Cron: List Tasks');

        /** @var Interfaces\Task[] $aTasks */
        $aTasks  = [];
        $sFilter = (string) $this->oInput->getArgument('component');
        Run::discoverTasks($oOutput, $aTasks);

        foreach ($aTasks as $oTask) {

            $oComponent = Components::detectClassComponent($oTask);
            $sPattern   = '/' . str_replace('/', '\/', $sFilter) . '/';
            if (!empty($sFilter) && (empty($oComponent) || !preg_match($sPattern, $oComponent->slug))) {
                continue;
            }

            $oOutput->writeln('');
            $oOutput->writeln('Task:        <info>' . get_class($oTask) . '</info>');
            $oOutput->writeln('Description: <info>' . $oTask->getDescription($this) . '</info>');
            $oOutput->writeln('Component:   <info>' . $oComponent->name . '</info>');

            $sCronExpression   = $oTask->getCronExpression();
            $sConsoleCommand   = $oTask->getConsoleCommand();
            $aConsoleArguments = $oTask->getConsoleArguments();

            if ($sCronExpression) {
                if (CronExpression::isValidExpression($sCronExpression)) {
                    $oOutput->writeln('Expression:  <info>' . $sCronExpression . '</info>');
                } else {
                    throw new CronException(sprintf(
                        'Cron task "%s" is misconfigured; "%s" is not a valid cron expression',
                        get_class($oTask),
                        $sCronExpression
                    ));
                }

            } else {
                throw new CronException(sprintf(
                    'Cron task "%s" misconfigured; cron expression is empty',
                    get_class($oTask),
                ));
            }

            if ($sConsoleCommand) {
                if ($this->isCommand($sConsoleCommand)) {
                    $oOutput->writeln('Executes:    <info>' . $sConsoleCommand . ' ' . implode(' ', $aConsoleArguments) . '</info>');
                } else {
                    throw new CronException(sprintf(
                        'Cron task "%s" is misconfigured; %s is not a valid console command',
                        get_class($oTask),
                        $sConsoleCommand
                    ));
                }

            } elseif (method_exists($oTask, 'execute')) {
                $oOutput->writeln('Executes:    <info>' . get_class($oTask) . '->execute()</info>');

            } else {
                throw new CronException(sprintf(
                    'Cron task "%s" is misconfigured, does not execute a console command or provide execute method',
                    get_class($oTask),
                ));
            }
        }
        $oOutput->writeln('');

        return self::EXIT_CODE_SUCCESS;
    }
}

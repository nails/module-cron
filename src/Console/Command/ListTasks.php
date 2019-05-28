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

use Nails\Common\Exception\FactoryException;
use Nails\Console\Command\Base;
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
            ->setDescription('Lists discovered cron tasks');
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

        $this->banner('Nails Cron Tasks');

        /** @var \Nails\Cron\Task\Base[] $aTasks */
        $aTasks = [];
        Run::discoverTasks($oOutput, $aTasks);

        foreach ($aTasks as $oTask) {

            $oOutput->writeln('');
            $oOutput->writeln('Task:        <info>' . get_class($oTask) . '</info>');
            $oOutput->writeln('Description: <info>' . $oTask::DESCRIPTION . '</info>');
            $oOutput->writeln('Expression:  <info>' . $oTask::CRON_EXPRESSION . '</info>');

            if ($oTask::CONSOLE_COMMAND) {
                if ($this->isCommand($oTask::CONSOLE_COMMAND)) {
                    $oOutput->writeln('Executes:    <info>' . $oTask::CONSOLE_COMMAND . ' ' . implode(' ', $oTask::CONSOLE_ARGUMENTS) . '</info>');
                } else {
                    $oOutput->writeln('<error>Command is misconfigured; ' . $oTask::CONSOLE_COMMAND . ' is not a valid console command</error>');
                }
            } elseif (method_exists($oTask, 'execute')) {
                $oOutput->writeln('Executes:    <info>' . get_class($oTask) . '->execute()</info>');
            } else {
                $oOutput->writeln('<error>Command is misconfigured</error>');
            }
        }
        $oOutput->writeln('');

        return self::EXIT_CODE_SUCCESS;
    }
}

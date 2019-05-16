<?php

namespace Nails\Cron\Console\Command;

use Nails\Common\Factory\Component;
use Nails\Components;
use Nails\Console\Command\Base;
use Nails\Cron\Interfaces\Command;
use Nails\Factory;
use Nails\Common\Exception\FactoryException;
use Symfony\Component\Console\Input\InputArgument;
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
     */
    protected function execute(InputInterface $oInput, OutputInterface $oOutput): int
    {
        parent::execute($oInput, $oOutput);

        $this
            ->discoverCommands()
            ->runCommands();

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
        /** @var Component $oComponent */
        foreach (Components::available() as $oComponent) {

            $aNamespaceRoots = $oComponent->getNamespaceRootPaths();
            if (empty($aNamespaceRoots)) {
                continue;
            }

            foreach ($aNamespaceRoots as $sPath) {

                $sCronPath = $sPath . DIRECTORY_SEPARATOR . 'Cron' . DIRECTORY_SEPARATOR;

                if (is_dir($sCronPath)) {

                    $oDirectory = new \RecursiveDirectoryIterator($sCronPath);
                    $oIterator  = new \RecursiveIteratorIterator($oDirectory);
                    $oRegex     = new \RegexIterator($oIterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);

                    foreach ($oRegex as $aItem) {

                        $sCommandPath = reset($aItem);
                        $sClassName   = str_replace($sCronPath, '', $sCommandPath);
                        $sClassName   = $oComponent->namespace . 'Cron\\' . rtrim(str_replace(DIRECTORY_SEPARATOR, '\\', $sClassName), '.php');

                        if (class_exists($sClassName) && classImplements($sClassName, Command::class)) {
                            $this->aCommands[] = new $sClassName();
                        }
                    }
                }
            }
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Executes definitons which satisfy the timestamp
     *
     * @return Run
     * @throws FactoryException
     */
    protected function runCommands(): Run
    {
        $oNow = Factory::factory('DateTime');

        /** @var Command $oCommand */
        foreach ($this->aCommands as $oCommand) {
            if ($oCommand->shouldRun($oNow)) {
                //  @todo (Pablo - 2019-05-16) - Execute the controlelr
                //  @todo (Pablo - 2019-05-16) - Support running console commands
                //  @todo (Pablo - 2019-05-16) - Support closures
            }
        }

        return $this;
    }
}

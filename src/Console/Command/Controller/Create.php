<?php

/**
 * The class provides the ability to create cron controllers
 *
 * @package     Nails
 * @subpackage  module-common
 * @category    Console
 * @author      Nails Dev Team
 */

namespace Nails\Cron\Console\Command\Controller;

use Nails\Common\Exception\NailsException;
use Nails\Console\Command\BaseMaker;
use Nails\Console\Exception\ConsoleException;
use Nails\Cron\Exception\Console\ControllerExistsException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;

/**
 * Class Create
 *
 * @package Nails\Cron\Console\Command\Controller
 */
class Create extends BaseMaker
{
    const RESOURCE_PATH   = NAILS_PATH . 'module-cron/resources/console/';
    const CONTROLLER_PATH = NAILS_APP_PATH . 'src/Cron/';

    // --------------------------------------------------------------------------

    /**
     * Configure the command
     */
    protected function configure(): void
    {
        $this
            ->setName('make:controller:cron')
            ->setDescription('Creates a new App cron controller')
            ->addArgument(
                'className',
                InputArgument::OPTIONAL,
                'Define the name of the cron controller'
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
     */
    protected function execute(InputInterface $oInput, OutputInterface $oOutput): int
    {
        parent::execute($oInput, $oOutput);

        // --------------------------------------------------------------------------

        try {
            $this
                ->createPath(self::CONTROLLER_PATH)
                ->createController();
        } catch (Exception $e) {
            return $this->abort(
                self::EXIT_CODE_FAILURE,
                [$e->getMessage()]
            );
        }

        // --------------------------------------------------------------------------

        //  Cleaning up
        $oOutput->writeln('');
        $oOutput->writeln('<comment>Cleaning up...</comment>');

        // --------------------------------------------------------------------------

        //  And we're done
        $oOutput->writeln('');
        $oOutput->writeln('Complete!');

        return self::EXIT_CODE_SUCCESS;
    }

    // --------------------------------------------------------------------------

    /**
     * Create the Controller
     *
     * @return $this
     * @throws ConsoleException
     * @throws NailsException
     */
    private function createController(): self
    {
        $aFields  = $this->getArguments();
        $aCreated = [];

        try {

            $aToCreate    = [];
            $aControllers = array_filter(
                array_map(function ($sController) {
                    return implode('/', array_map('ucfirst', explode('/', ucfirst(trim($sController)))));
                }, explode(',', $aFields['CLASS_NAME']))
            );

            sort($aControllers);

            foreach ($aControllers as $sController) {

                $aClassBits = explode('/', $sController);
                $aClassBits = array_map('ucfirst', $aClassBits);

                $sNamespace     = $this->generateNamespace($aClassBits);
                $sClassName     = $this->generateClassName($aClassBits);
                $sClassNameFull = $sNamespace . '\\' . $sClassName;
                $sFilePath      = $this->generateFilePath($aClassBits);

                //  Test it does not already exist
                if (file_exists($sFilePath)) {
                    throw new ControllerExistsException(
                        'A controller at "' . $sFilePath . '" already exists'
                    );
                }

                $aToCreate[] = [
                    'NAMESPACE'       => $sNamespace,
                    'CLASS_NAME'      => $sClassName,
                    'CLASS_NAME_FULL' => $sClassNameFull,
                    'FILE_PATH'       => $sFilePath,
                    'DIRECTORY'       => dirname($sFilePath) . DIRECTORY_SEPARATOR,
                ];
            }

            $this->oOutput->writeln('The following controller(s) will be created:');
            foreach ($aToCreate as $aConfig) {
                $this->oOutput->writeln('');
                $this->oOutput->writeln('Class: <info>' . $aConfig['CLASS_NAME_FULL'] . '</info>');
                $this->oOutput->writeln('Path:  <info>' . $aConfig['FILE_PATH'] . '</info>');
            }
            $this->oOutput->writeln('');

            if ($this->confirm('Continue?', true)) {
                $this->oOutput->writeln('');

                //  Generate Controllers
                foreach ($aToCreate as $aConfig) {
                    $this->oOutput->write('Creating controller <comment>' . $aConfig['CLASS_NAME_FULL'] . '</comment>... ');
                    $this->createPath($aConfig['DIRECTORY']);
                    $this->createFile(
                        $aConfig['FILE_PATH'],
                        $this->getResource('template/controller.php', $aConfig)
                    );
                    $aCreated[] = $aConfig['FILE_PATH'];
                    $this->oOutput->writeln('<info>done!</info>');
                }

                $this->oOutput->writeln('<info>done!</info>');
            }

        } catch (ConsoleException $e) {
            $this->oOutput->writeln('<error>failed!</error>');
            //  Clean up created controllers
            if (!empty($aCreated)) {
                $this->oOutput->writeln('<error>Cleaning up - removing newly created files</error>');
                foreach ($aCreated as $sPath) {
                    @unlink($sPath);
                }
            }
            throw new ConsoleException($e->getMessage());
        }

        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * Generate the class name
     *
     * @param array $aClassBits The supplied classname "bits"
     *
     * @return string
     */
    protected function generateClassName(array $aClassBits): string
    {
        return array_pop($aClassBits);
    }

    // --------------------------------------------------------------------------

    /**
     * Generate the class namespace
     *
     * @param array $aClassBits The supplied classname "bits"
     *
     * @return string
     */
    protected function generateNamespace(array $aClassBits): string
    {
        array_pop($aClassBits);
        return implode('\\', array_merge(['App', 'Cron'], $aClassBits));
    }

    // --------------------------------------------------------------------------

    /**
     * Generate the class file path
     *
     * @param array $aClassBits The supplied classname "bits"
     *
     * @return string
     */
    protected function generateFilePath(array $aClassBits): string
    {
        $sClassName = array_pop($aClassBits);
        return implode(
            DIRECTORY_SEPARATOR,
            array_map(
                function ($sItem) {
                    return rtrim($sItem, DIRECTORY_SEPARATOR);
                },
                array_merge(
                    [static::CONTROLLER_PATH],
                    $aClassBits,
                    [$sClassName . '.php']
                )
            )
        );
    }
}

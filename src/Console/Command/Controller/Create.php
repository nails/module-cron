<?php

namespace Nails\Cron\Console\Command\Controller;

use Nails\Console\Command\BaseMaker;
use Nails\Cron\Exception\Console\ControllerExistsException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Create extends BaseMaker
{
    const RESOURCE_PATH   = NAILS_PATH . 'module-cron/resources/console/';
    const CONTROLLER_PATH = NAILS_APP_PATH . 'application/modules/cron/controllers/';

    // --------------------------------------------------------------------------

    /**
     * Configure the command
     */
    protected function configure(): void
    {
        $this
            ->setName('make:controller:cron')
            ->setDescription('Creates a new Cron controller')
            ->addArgument(
                'className',
                InputArgument::OPTIONAL,
                'Define the name of the model on which to base the controller'
            );
    }

    // --------------------------------------------------------------------------

    /**
     * Executes the app
     *
     * @param  InputInterface  $oInput  The Input Interface provided by Symfony
     * @param  OutputInterface $oOutput The Output Interface provided by Symfony
     *
     * @return int
     */
    protected function execute(InputInterface $oInput, OutputInterface $oOutput): int
    {
        parent::execute($oInput, $oOutput);

        // --------------------------------------------------------------------------

        try {
            //  Ensure the paths exist
            $this->createPath(self::CONTROLLER_PATH);
            //  Create the controller
            $this->createController();
        } catch (\Exception $e) {
            return $this->abort(
                self::EXIT_CODE_FAILURE,
                $e->getMessage()
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
     * Create the Model
     *
     * @throws \Exception
     */
    private function createController(): void
    {
        $aFields  = $this->getArguments();
        $aCreated = [];

        try {

            $aClasses = array_filter(explode(',', $aFields['CLASS_NAME']));

            foreach ($aClasses as $sClass) {

                $aFields['CLASS_NAME'] = $sClass;
                $this->oOutput->write('Creating controller <comment>' . $sClass . '</comment>... ');

                //  Check for existing controller
                $sPath = static::CONTROLLER_PATH . $sClass . '.php';
                if (file_exists($sPath)) {
                    throw new ControllerExistsException(
                        'Controller "' . $sClass . '" exists already at path "' . $sPath . '"'
                    );
                }

                $this->createFile($sPath, $this->getResource('template/controller.php', $aFields));
                $aCreated[] = $sPath;
                $this->oOutput->writeln('<info>done!</info>');
            }

        } catch (\Exception $e) {
            $this->oOutput->writeln('<error>failed!</error>');
            //  Clean up created models
            if (!empty($aCreated)) {
                $this->oOutput->writeln('<error>Cleaning up - removing newly created controllers</error>');
                foreach ($aCreated as $sPath) {
                    @unlink($sPath);
                }
            }
            throw new \Exception($e->getMessage());
        }
    }
}

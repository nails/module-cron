<?php

namespace Nails\Cron\Console\Output;

use DateTime;
use Nails\Common\Factory\Logger;
use Nails\Factory;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * Class LoggerOutput
 *
 * @package Nails\Cron\Console\Output
 */
class LoggerOutput extends StreamOutput
{
    /**
     * LoggerOutput constructor.
     *
     * @param null                          $stream
     * @param int                           $verbosity
     * @param bool|null                     $decorated
     * @param OutputFormatterInterface|null $formatter
     *
     * @throws \Nails\Common\Exception\FactoryException
     */
    public function __construct(
        $stream = null,
        int $verbosity = self::VERBOSITY_NORMAL,
        bool $decorated = null,
        OutputFormatterInterface $formatter = null
    ) {
        /** @var DateTime $oNow */
        $oNow = Factory::factory('DateTime');
        /** @var Logger $oLogger */
        $oLogger = Factory::factory('Logger');

        //  Set a cron log file for today and write to it to ensure it exists
        $oLogger->setFile('cron-' . $oNow->format('Y-m-d') . '.php');
        $oLogger->line('Starting Cron Runner');

        parent::__construct($oLogger->getStream(), $verbosity, $decorated, $formatter);
    }

    // --------------------------------------------------------------------------

    /**
     * @param $message
     * @param $newline
     *
     * @throws \Nails\Common\Exception\FactoryException
     */
    protected function doWrite($message, $newline)
    {
        $oNow    = Factory::factory('DateTime');
        $message = 'INFO - ' . $oNow->format('Y-m-d H:i:s') . ' --> ' . $message;
        parent::doWrite($message, $newline);
    }
}

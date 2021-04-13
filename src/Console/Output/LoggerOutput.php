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
    protected string $sSessionId;

    // --------------------------------------------------------------------------

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
        OutputFormatterInterface $formatter = null,
        string $sSessionId = null
    ) {
        /** @var DateTime $oNow */
        $oNow = Factory::factory('DateTime');
        /** @var Logger $oLogger */
        $oLogger = Factory::factory('Logger');

        $this->setSessionId($sSessionId ?? uniqid());

        //  Set a cron log file for today and write to it to ensure it exists
        $oLogger->setFile('cron-' . $oNow->format('Y-m-d') . '.php');

        parent::__construct($oLogger->getStream(), $verbosity, $decorated, $formatter);

        $this->doWrite('Starting Cron Runner', true);
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
        /** @var DateTime $oNow */
        $oNow = Factory::factory('DateTime');

        $message = sprintf(
            'INFO - %s [%s] --> %s',
            $oNow->format('Y-m-d H:i:s'),
            $this->getSessionId(),
            $message
        );

        parent::doWrite($message, $newline);
    }

    // --------------------------------------------------------------------------

    public function setSessionId(string $sSessionId): self
    {
        $this->sSessionId = $sSessionId;
        return $this;
    }

    // --------------------------------------------------------------------------

    /**
     * @return mixed
     */
    public function getSessionId(): string
    {
        return $this->sSessionId;
    }
}

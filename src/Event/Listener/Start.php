<?php

namespace Nails\Cron\Event\Listener;

use Nails\Cron\Constants;
use Nails\Cron\Events;
use Nails\Common\Events\Subscription;
use Nails\Common\Exception\NailsException;
use Nails\Config;
use Nails\Factory;
use Nails\Functions;
use ReflectionException;

/**
 * Class Start
 *
 * @package Nails\Cron\Event\Listener
 */
class Start extends Subscription
{
    const SETTINGS_KEY = 'last_run';

    // --------------------------------------------------------------------------

    /**
     * Start constructor.
     *
     * @throws NailsException
     * @throws ReflectionException
     */
    public function __construct()
    {
        $this
            ->setEvent(Events::CRON_START)
            ->setNamespace(Events::getEventNamespace())
            ->setCallback([$this, 'execute']);
    }

    // --------------------------------------------------------------------------

    /**
     * Define email constants
     */
    public function execute()
    {
        /** @var \DateTime $oNow */
        $oNow = Factory::factory('DateTime');
        setAppSetting(static::SETTINGS_KEY, Constants::MODULE_SLUG, $oNow->format('Y-m-d H:i:s'));
    }
}

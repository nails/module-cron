<?php

namespace Nails\Cron\Admin\Dashboard\Alert;

use Nails\Admin\Interfaces\Dashboard\Alert;
use Nails\Cron\Constants;
use Nails\Cron\Event\Listener;
use Nails\Factory;

/**
 * Class NotRunning
 *
 * @package Nails\Cron\Admin\Dashboard\Alert
 */
class NotRunning implements Alert
{
    const TIMEOUT = 90;

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function getTitle(): ?string
    {
        return 'Cron is not running';
    }

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function getBody(): ?string
    {
        return sprintf(
            'Cron jobs have not been run within the last %d seconds.',
            static::TIMEOUT
        );
    }

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function getSeverity(): string
    {
        return static::SEVERITY_WARNING;
    }

    // --------------------------------------------------------------------------

    /**
     * @inheritDoc
     */
    public function isAlerting(): bool
    {
        /** @var \DateTime $oNow */
        $oNow  = Factory::factory('DateTime');
        $sThen = appSetting(Listener\Start::SETTINGS_KEY, Constants::MODULE_SLUG);

        if (empty($sThen)) {
            return true;
        }

        $oThen = new \DateTime($sThen);
        $iDiff = $oNow->getTimestamp() - $oThen->getTimestamp();

        return $iDiff > static::TIMEOUT;
    }
}

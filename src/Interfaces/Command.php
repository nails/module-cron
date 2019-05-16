<?php

namespace Nails\Cron\Interfaces;

/**
 * Interface Command
 *
 * @package Nails\Cron\Interfaces
 */
interface Command
{
    /**
     * Determines whether the cron job should run for a given DateTime
     *
     * @param \DateTime $oNow
     *
     * @return bool
     */
    public function shouldRun(\DateTime $oNow): bool;
}

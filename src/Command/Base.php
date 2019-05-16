<?php

namespace Nails\Cron\Command;

/**
 * Class Base
 *
 * @package Nails\Cron\Command
 */
abstract class Base
{
    /**
     * Helper function for specifying a command should run every minute
     *
     * @return bool
     */
    protected function shouldRunEveryMinute(): bool
    {
        return true;
    }
}
